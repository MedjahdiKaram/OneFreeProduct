<?php
/**
 * Plugin Name: One Free product per user
 * Plugin URI:  https://www.linkedin.com/in/medjahdikaram/
 * Description: Blocks the purchase of more than one free WooCommerce product per user and IP address.
 * Version:     1.0.0
 * Author:      M. Karam MEDJAHDI
 * Author URI:  https://www.linkedin.com/in/medjahdikaram/
 * Text Domain: one-free-product-per-user
 * Domain Path: /languages
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Load plugin textdomain for translations.
 */
function onefp_load_textdomain() {
    load_plugin_textdomain( 'one-free-product-per-user', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'onefp_load_textdomain' );

/**
 * Check if a product is free (price zero).
 *
 * @param WC_Product $product WooCommerce product instance.
 *
 * @return bool True when product price is zero.
 */
function onefp_is_free_product( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return false;
    }
    return (float) $product->get_price() <= 0;
}

/**
 * Retrieve the current user's IP address.
 *
 * @return string
 */
function onefp_get_user_ip() {
    return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
}

/**
 * Determine if the current user already ordered a free product.
 *
 * @return bool
 */
function onefp_user_has_free_purchase() {
    if ( is_user_logged_in() ) {
        return (bool) get_user_meta( get_current_user_id(), 'onefp_free_purchased', true );
    }
    return false;
}

/**
 * Mark the current logged-in user as having ordered a free product.
 */
function onefp_mark_user_purchased() {
    if ( is_user_logged_in() ) {
        update_user_meta( get_current_user_id(), 'onefp_free_purchased', 1 );
    }
}

/**
 * Determine if the current IP address already ordered a free product.
 *
 * @return bool
 */
function onefp_ip_has_free_purchase() {
    $ips = get_option( 'onefp_free_ips', array() );
    $ip  = onefp_get_user_ip();
    return in_array( $ip, $ips, true );
}

/**
 * Mark the current IP address as having ordered a free product.
 */
function onefp_mark_ip_purchased() {
    $ips = get_option( 'onefp_free_ips', array() );
    $ip  = onefp_get_user_ip();
    if ( ! in_array( $ip, $ips, true ) ) {
        $ips[] = $ip;
        update_option( 'onefp_free_ips', $ips );
    }
}

/**
 * Validate free product restrictions when adding to cart.
 *
 * @param bool     $passed     Whether the product can be added to the cart.
 * @param int      $product_id The product ID being added.
 * @param int      $quantity   Quantity being added.
 *
 * @return bool
 */
function onefp_validate_free_product( $passed, $product_id, $quantity ) {
    $product = wc_get_product( $product_id );
    if ( ! onefp_is_free_product( $product ) ) {
        return $passed;
    }

    if ( $quantity > 1 ) {
        wc_add_notice( __( 'Only one free product is allowed.', 'one-free-product-per-user' ), 'error' );
        return false;
    }

    foreach ( WC()->cart->get_cart() as $item ) {
        if ( onefp_is_free_product( $item['data'] ) ) {
            wc_add_notice( __( 'Only one free product is allowed per order.', 'one-free-product-per-user' ), 'error' );
            return false;
        }
    }

    if ( onefp_user_has_free_purchase() || onefp_ip_has_free_purchase() ) {
        wc_add_notice( __( 'A free product has already been ordered from this user or IP.', 'one-free-product-per-user' ), 'error' );
        return false;
    }

    return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'onefp_validate_free_product', 10, 3 );

/**
 * Final validation during checkout to ensure cart restrictions.
 */
function onefp_checkout_validation() {
    $free_count = 0;

    foreach ( WC()->cart->get_cart() as $item ) {
        if ( onefp_is_free_product( $item['data'] ) ) {
            $free_count += $item['quantity'];
            if ( $item['quantity'] > 1 ) {
                wc_add_notice( __( 'Only one free product quantity of one is allowed.', 'one-free-product-per-user' ), 'error' );
            }
        }
    }

    if ( $free_count > 1 ) {
        wc_add_notice( __( 'Only one free product is allowed in the cart.', 'one-free-product-per-user' ), 'error' );
    }

    if ( $free_count > 0 && ( onefp_user_has_free_purchase() || onefp_ip_has_free_purchase() ) ) {
        wc_add_notice( __( 'A free product has already been ordered from this user or IP.', 'one-free-product-per-user' ), 'error' );
    }
}
add_action( 'woocommerce_checkout_process', 'onefp_checkout_validation' );

/**
 * Mark user and IP when an order containing a free product is completed.
 *
 * @param int $order_id The order ID.
 */
function onefp_mark_order_free_product( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( onefp_is_free_product( $product ) ) {
            onefp_mark_user_purchased();
            onefp_mark_ip_purchased();
            break;
        }
    }
}
add_action( 'woocommerce_order_status_completed', 'onefp_mark_order_free_product' );
