<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Bkash_Blocks_Payment_Method extends AbstractPaymentMethodType {
    protected $name = 'bkash';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_bkash_settings', array() );
    }

    public function is_active() {
        $enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
        if ( $enabled !== 'yes' ) {
            return false;
        }

        return ! empty( $this->settings['username'] )
            && ! empty( $this->settings['password'] )
            && ! empty( $this->settings['app_key'] )
            && ! empty( $this->settings['app_secret'] );
    }

    public function get_payment_method_script_handles() {
        $handle = 'bkash-blocks';

        wp_register_script(
            $handle,
            plugins_url( 'assets/js/bkash-blocks.js', dirname( __FILE__ ) ),
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
        $title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'bKash', 'bkash' );
        $description = isset( $this->settings['description'] ) ? $this->settings['description'] : __( 'Pay securely using bKash.', 'bkash' );

        return array(
            'title'       => $title,
            'description' => $description,
        );
    }
}
