<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class DgePay_Blocks_Payment_Method extends AbstractPaymentMethodType {
    protected $name = 'dgepay';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_dgepay_settings', array() );
    }

    public function is_active() {
        $enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
        if ( $enabled !== 'yes' ) {
            return false;
        }

        return ! empty( $this->settings['client_id'] )
            && ! empty( $this->settings['client_secret'] )
            && ! empty( $this->settings['client_api_key'] );
    }

    public function get_payment_method_script_handles() {
        $handle = 'dgepay-blocks';

        wp_register_script(
            $handle,
            plugins_url( 'assets/js/dgepay-blocks.js', dirname( __FILE__ ) ),
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ),
            '1.0.0',
            true
        );

        return array( $handle );
    }

    public function get_payment_method_data() {
        $title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'DgePay', 'dgepay' );
        $description = isset( $this->settings['description'] ) ? $this->settings['description'] : __( 'Pay securely using DgePay.', 'dgepay' );

        return array(
            'title'       => $title,
            'description' => $description,
        );
    }
}
