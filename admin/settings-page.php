<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        esc_html__( 'DgePay', 'dgepay' ),
        esc_html__( 'DgePay', 'dgepay' ),
        'manage_woocommerce',
        'dgepay-info',
        'dgepay_render_info_page'
    );
} );

function dgepay_render_info_page() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'DgePay Gateway', 'dgepay' ) . '</h1>';
    echo '<div class="notice notice-info"><p>' . esc_html__( 'DgePay supports Classic Checkout and WooCommerce Blocks checkout. Use the [woocommerce_checkout] shortcode for Classic, or the Checkout block for Blocks.', 'dgepay' ) . '</p></div>';

    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<p>' . esc_html__( 'WooCommerce is not active. Please install and activate WooCommerce to use DgePay.', 'dgepay' ) . '</p>';
        echo '</div>';
        return;
    }

    echo '<p>' . esc_html__( 'Configure your credentials and enable the gateway from the WooCommerce payments settings.', 'dgepay' ) . '</p>';

    $settings_url_legacy = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dgepay' );
    $settings_url_modern = admin_url( 'admin.php?page=wc-settings&tab=payments&section=dgepay' );

    echo '<p><a class="button button-primary" href="' . esc_url( $settings_url_legacy ) . '">' . esc_html__( 'Open WooCommerce Settings (Checkout tab)', 'dgepay' ) . '</a></p>';
    echo '<p><a class="button" href="' . esc_url( $settings_url_modern ) . '">' . esc_html__( 'Open WooCommerce Settings (Payments tab)', 'dgepay' ) . '</a></p>';
    echo '</div>';
}
