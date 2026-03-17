<?php

class Bkash_Payment_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'bkash';
        $this->method_title       = esc_html__( 'bKash', 'bkash' );
        $this->method_description = esc_html__( 'bKash Payment Gateway for WooCommerce (Classic + Blocks checkout supported).', 'bkash' );
        $this->has_fields         = false;
        $this->supports           = array( 'products' );
        $this->icon               = plugins_url( 'assets/logo/bKash-logo.png', dirname( __FILE__ ) );

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_bkash_callback', array( $this, 'handle_callback' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__( 'Enable/Disable', 'bkash' ),
                'type'    => 'checkbox',
                'label'   => esc_html__( 'Enable bKash Payment Gateway', 'bkash' ),
                'default' => 'yes',
            ),
            'title' => array(
                'title'   => esc_html__( 'Title', 'bkash' ),
                'type'    => 'text',
                'default' => esc_html__( 'bKash', 'bkash' ),
            ),
            'description' => array(
                'title'   => esc_html__( 'Description', 'bkash' ),
                'type'    => 'textarea',
                'default' => esc_html__( 'Pay securely using bKash.', 'bkash' ),
            ),
            'base_url' => array(
                'title'       => esc_html__( 'Base API URL', 'bkash' ),
                'type'        => 'text',
                'default'     => 'https://tokenized.pay.bka.sh/v1.2.0-beta',
            ),
            'username' => array(
                'title'   => esc_html__( 'Username', 'bkash' ),
                'type'    => 'text',
                'default' => '',
            ),
            'password' => array(
                'title'   => esc_html__( 'Password', 'bkash' ),
                'type'    => 'password',
                'default' => '',
            ),
            'app_key' => array(
                'title'   => esc_html__( 'App Key', 'bkash' ),
                'type'    => 'text',
                'default' => '',
            ),
            'app_secret' => array(
                'title'   => esc_html__( 'App Secret', 'bkash' ),
                'type'    => 'password',
                'default' => '',
            ),
            'debug' => array(
                'title'   => esc_html__( 'Debug Log', 'bkash' ),
                'type'    => 'checkbox',
                'label'   => esc_html__( 'Enable logging', 'bkash' ),
                'default' => 'no',
            ),
        );
    }

    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( wp_kses_post( $this->description ) );
        }
    }

    public function is_available() {
        if ( $this->get_option( 'enabled' ) !== 'yes' ) {
            return false;
        }

        return ! empty( $this->get_option( 'username' ) )
            && ! empty( $this->get_option( 'password' ) )
            && ! empty( $this->get_option( 'app_key' ) )
            && ! empty( $this->get_option( 'app_secret' ) )
            && parent::is_available();
    }

    private function get_api(): Bkash_API {
        return new Bkash_API(
            array(
                'base_url'   => $this->get_option( 'base_url' ),
                'username'   => $this->get_option( 'username' ),
                'password'   => $this->get_option( 'password' ),
                'app_key'    => $this->get_option( 'app_key' ),
                'app_secret' => $this->get_option( 'app_secret' ),
            ),
            $this->get_option( 'debug' ) === 'yes'
        );
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return array( 'result' => 'fail' );
        }

        $api = $this->get_api();
        $grant = $api->get_id_token();
        if ( empty( $grant['success'] ) ) {
            wc_add_notice( esc_html__( 'bKash authentication failed.', 'bkash' ), 'error' );
            return array( 'result' => 'fail' );
        }

        $invoice = 'INV' . date( 'YmdHis' ) . $order->get_id();
        $phone   = preg_replace( '/[^0-9A-Za-z]/', '', (string) $order->get_billing_phone() );
        if ( $phone === '' ) {
            $phone = substr( $invoice, -10 );
        }

        $callback_url = add_query_arg(
            array(
                'order_id' => $order->get_id(),
            ),
            WC()->api_request_url( 'bkash_callback' )
        );

        $payload = array(
            'mode'                  => '0011',
            'payerReference'        => substr( $phone, 0, 20 ),
            'callbackURL'           => $callback_url,
            'amount'                => number_format( (float) $order->get_total(), 2, '.', '' ),
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $invoice,
        );

        $created = $api->create_payment( $grant['id_token'], $payload );
        if ( empty( $created['success'] ) ) {
            wc_add_notice( esc_html( $created['message'] ?? 'Failed to create bKash payment.' ), 'error' );
            return array( 'result' => 'fail' );
        }

        $order->update_status( 'pending', esc_html__( 'Redirected to bKash for payment.', 'bkash' ) );
        $order->update_meta_data( '_bkash_payment_id', sanitize_text_field( (string) ( $created['payment_id'] ?? '' ) ) );
        $order->update_meta_data( '_bkash_invoice', sanitize_text_field( $invoice ) );
        $order->save();

        return array(
            'result'   => 'success',
            'redirect' => esc_url_raw( $created['bkash_url'] ),
        );
    }

    public function handle_callback() {
        $status    = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $payment_id = isset( $_GET['paymentID'] ) ? sanitize_text_field( wp_unslash( $_GET['paymentID'] ) ) : '';

        $order_id = isset( $_GET['order_id'] ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : '';
        $order    = $order_id ? wc_get_order( $order_id ) : false;

        if ( ! $order && $payment_id !== '' ) {
            $orders = wc_get_orders( array(
                'limit'      => 1,
                'meta_key'   => '_bkash_payment_id',
                'meta_value' => $payment_id,
                'status'     => array_keys( wc_get_order_statuses() ),
            ) );
            if ( ! empty( $orders ) ) {
                $order = $orders[0];
            }
        }

        if ( ! $order ) {
            wp_die( esc_html__( 'Invalid order.', 'bkash' ) );
        }

        if ( $status !== 'success' || $payment_id === '' ) {
            $order->update_status( 'failed', esc_html__( 'bKash payment failed or cancelled.', 'bkash' ) );
            wp_safe_redirect( $this->get_return_url( $order ) );
            exit;
        }

        $api = $this->get_api();
        $grant = $api->get_id_token();
        if ( empty( $grant['success'] ) ) {
            $order->update_status( 'failed', esc_html__( 'bKash authentication failed during callback.', 'bkash' ) );
            wp_safe_redirect( $this->get_return_url( $order ) );
            exit;
        }

        $executed = $api->execute_payment( $grant['id_token'], $payment_id );
        if ( empty( $executed['success'] ) ) {
            $order->update_status( 'failed', esc_html( $executed['message'] ?? 'bKash payment execution failed.' ) );
            wp_safe_redirect( $this->get_return_url( $order ) );
            exit;
        }

        $payment_data = $executed['data'];
        $trx_id = $payment_data['trxID'] ?? '';

        $order->payment_complete( $trx_id );
        $order->add_order_note( esc_html__( 'bKash payment successful.', 'bkash' ) );
        $order->update_meta_data( '_bkash_trx_id', sanitize_text_field( (string) $trx_id ) );
        $order->save();

        wp_safe_redirect( $this->get_return_url( $order ) );
        exit;
    }
}
