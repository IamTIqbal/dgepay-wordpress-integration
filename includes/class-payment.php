<?php
// DgePay Payment Gateway class for WooCommerce
class DgePay_Payment_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'dgepay';
        $this->method_title = esc_html__('DgePay', 'dgepay');
        $this->method_description = esc_html__(
            'DgePay Payment Gateway for WooCommerce (Classic + Blocks checkout supported).',
            'dgepay'
        );
        $this->has_fields = true;
        $this->supports = array('products');
        $this->icon = plugins_url( 'assets/logo/dgepay-logo.png', dirname( __FILE__ ) );
        $this->has_fields = false;

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Save settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Handle DgePay callback
        add_action( 'woocommerce_api_dgepay_callback', array( $this, 'handle_callback' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => esc_html__('Enable/Disable', 'dgepay'),
                'type' => 'checkbox',
                'label' => esc_html__('Enable DgePay Payment Gateway', 'dgepay'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => esc_html__('Title', 'dgepay'),
                'type' => 'text',
                'default' => esc_html__('DgePay', 'dgepay')
            ),
            'description' => array(
                'title' => esc_html__('Description', 'dgepay'),
                'type' => 'textarea',
                'default' => esc_html__('Pay securely using DgePay.', 'dgepay')
            ),
            'client_id' => array(
                'title' => esc_html__('Client ID', 'dgepay'),
                'type' => 'text',
                'default' => ''
            ),
            'client_secret' => array(
                'title' => esc_html__('Client Secret', 'dgepay'),
                'type' => 'password',
                'default' => ''
            ),
            'client_api_key' => array(
                'title' => esc_html__('Client API Key', 'dgepay'),
                'type' => 'password',
                'default' => ''
            ),
            'base_url' => array(
                'title' => esc_html__('Base API URL', 'dgepay'),
                'type' => 'text',
                'default' => 'https://apiv2.dgepay.net/dipon/v3',
            ),
            'payment_method' => array(
                'title' => esc_html__('Default Payment Method', 'dgepay'),
                'type' => 'text',
                'description' => esc_html__('Optional. Example: bKash, Nagad. Leave blank to allow user choice.', 'dgepay'),
                'default' => ''
            ),
            'debug' => array(
                'title' => esc_html__('Debug Log', 'dgepay'),
                'type' => 'checkbox',
                'label' => esc_html__('Enable logging', 'dgepay'),
                'default' => 'no'
            ),
        );
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
    }

    public function is_available() {
        if ( $this->get_option( 'enabled' ) !== 'yes' ) {
            return false;
        }

        $client_id      = $this->get_option( 'client_id' );
        $client_secret  = $this->get_option( 'client_secret' );
        $client_api_key = $this->get_option( 'client_api_key' );

        if ( empty( $client_id ) || empty( $client_secret ) || empty( $client_api_key ) ) {
            return false;
        }

        return parent::is_available();
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if ( ! $order ) {
            return array('result' => 'fail');
        }

        $redirect_url = add_query_arg(
            array(
                'order_id' => $order->get_id(),
            ),
            WC()->api_request_url( 'dgepay_callback' )
        );

        $api = new DgePay_API(
            array(
                'client_id'      => $this->get_option( 'client_id' ),
                'client_secret'  => $this->get_option( 'client_secret' ),
                'client_api_key' => $this->get_option( 'client_api_key' ),
                'base_url'       => $this->get_option( 'base_url' ),
            ),
            $this->get_option( 'debug' ) === 'yes'
        );

        $response = $api->initiate_payment(
            $order,
            $redirect_url,
            sanitize_text_field( $this->get_option( 'payment_method' ) )
        );

        if (!empty($response['success']) && $response['success']) {
            $order->update_status( 'pending', esc_html__( 'Redirected to DgePay for payment.', 'dgepay' ) );
            $order->update_meta_data( '_dgepay_transaction_id', sanitize_text_field( $response['transaction_id'] ?? '' ) );
            $order->save();
            return array(
                'result' => 'success',
                'redirect' => esc_url_raw( $response['payment_url'] )
            );
        } else {
            wc_add_notice(esc_html__('Payment error:', 'dgepay') . ' ' . esc_html($response['message']), 'error');
            return array('result' => 'fail');
        }
    }

    public function admin_options() {
        echo '<h2>' . esc_html($this->get_method_title()) . '</h2>';
        echo wpautop(esc_html($this->get_method_description()));
        parent::admin_options();
    }

    public function handle_callback() {
        $raw_data = isset( $_GET['data'] ) ? (string) wp_unslash( $_GET['data'] ) : '';
        $raw_data = str_replace( ' ', '+', $raw_data );

        $api = new DgePay_API(
            array(
                'client_id'      => $this->get_option( 'client_id' ),
                'client_secret'  => $this->get_option( 'client_secret' ),
                'client_api_key' => $this->get_option( 'client_api_key' ),
                'base_url'       => $this->get_option( 'base_url' ),
            ),
            $this->get_option( 'debug' ) === 'yes'
        );

        $decoded = $raw_data ? $api->parse_callback( $raw_data ) : null;
        $params  = is_array( $decoded ) ? $decoded : array_map( 'sanitize_text_field', wp_unslash( $_GET ) );
        $result  = $api->normalize_callback_result( $params );

        $order_id = $result['unique_txn_id'] ?? '';
        if ( ! $order_id && isset( $_GET['order_id'] ) ) {
            $order_id = sanitize_text_field( wp_unslash( $_GET['order_id'] ) );
        }

        $order = $order_id ? wc_get_order( $order_id ) : false;
        if ( ! $order && $order_id ) {
            $orders = wc_get_orders( array(
                'limit'      => 1,
                'meta_key'   => '_dgepay_unique_txn_id',
                'meta_value' => $order_id,
                'status'     => array_keys( wc_get_order_statuses() ),
            ) );
            if ( ! empty( $orders ) ) {
                $order = $orders[0];
            }
        }

        if ( ! $order ) {
            wp_die( esc_html__( 'Invalid order.', 'dgepay' ) );
        }

        if ( ! empty( $result['is_success'] ) ) {
            $transaction_id = $result['txn_number'] ?? '';
            $order->payment_complete( $transaction_id );
            $order->add_order_note( esc_html__( 'DgePay payment successful.', 'dgepay' ) );
        } elseif ( ! empty( $result['is_cancelled'] ) ) {
            $order->update_status( 'cancelled', esc_html__( 'DgePay payment cancelled.', 'dgepay' ) );
        } else {
            $message = isset( $result['message'] ) ? $result['message'] : esc_html__( 'Payment failed or unknown status.', 'dgepay' );
            $order->update_status( 'failed', $message );
        }

        $redirect = $this->get_return_url( $order );
        wp_safe_redirect( $redirect );
        exit;
    }
}
