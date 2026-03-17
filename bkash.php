<?php
/*
Plugin Name: bKash Payment Gateway (Unofficial)
Description: Unofficial bKash payment gateway for WordPress/WooCommerce (Classic + Blocks checkout).
Version: 1.0.0
Author: Tamim Iqbal
Author URI: https://tamimiqbal.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bkash_register_gateway() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-api.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-payment.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-blocks.php';

    add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
        $gateways[] = 'Bkash_Payment_Gateway';
        return $gateways;
    } );
}

add_action( 'woocommerce_loaded', 'bkash_register_gateway' );
add_action( 'plugins_loaded', 'bkash_register_gateway', 20 );

add_action( 'woocommerce_blocks_loaded', function() {
    if ( class_exists( 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-blocks.php';
    }
} );

add_action( 'woocommerce_blocks_payment_method_type_registration', function( $payment_method_registry ) {
    if ( class_exists( 'Bkash_Blocks_Payment_Method' ) ) {
        $payment_method_registry->register( new Bkash_Blocks_Payment_Method() );
    }
} );
