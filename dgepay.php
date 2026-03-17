<?php
/*
Plugin Name: DgePay Payment Gateway (Unofficial)
Description: Unofficial DgePay payment gateway for WordPress/WooCommerce (Classic + Blocks checkout).
Version: 1.0.0
Author: Tamim Iqbal
Role: IT Manager and AI Developer
Author URI: https://tamimiqbal.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enqueue assets
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'dgepay-css', plugin_dir_url( __FILE__ ) . 'assets/css/dgepay.css' );
    wp_enqueue_script( 'dgepay-js', plugin_dir_url( __FILE__ ) . 'assets/js/dgepay.js', array('jquery'), null, true );
});

// No custom admin submenu needed; configure via WooCommerce > Settings > Payments.

function dgepay_register_gateway() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    // Include core files
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-dgepay-sdk.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-api.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-payment.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-blocks.php';

    // Register payment gateway (for WooCommerce)
    add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
        $gateways[] = 'DgePay_Payment_Gateway';
        return $gateways;
    } );
}

// Ensure WooCommerce is fully loaded before registering the gateway.
add_action( 'woocommerce_loaded', 'dgepay_register_gateway' );

// Fallback if WooCommerce is already loaded when this plugin runs.
add_action( 'plugins_loaded', 'dgepay_register_gateway', 20 );

// Ensure the gateway remains available at checkout if enabled.
add_filter( 'woocommerce_available_payment_gateways', function( $gateways ) {
    if ( is_admin() ) {
        return $gateways;
    }

    if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
        return $gateways;
    }

    $all_gateways = WC()->payment_gateways()->payment_gateways();
    if ( isset( $all_gateways['dgepay'] ) ) {
        $instance = $all_gateways['dgepay'];
        if ( $instance instanceof WC_Payment_Gateway && $instance->is_available() ) {
            $gateways['dgepay'] = $instance;
        }
    }

    return $gateways;
}, 50 );

// Register WooCommerce Blocks integration if available.
add_action( 'woocommerce_blocks_loaded', function() {
    if ( class_exists( 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-blocks.php';
    }
} );

add_action( 'woocommerce_blocks_payment_method_type_registration', function( $payment_method_registry ) {
    if ( class_exists( 'DgePay_Blocks_Payment_Method' ) ) {
        $payment_method_registry->register( new DgePay_Blocks_Payment_Method() );
    }
} );
