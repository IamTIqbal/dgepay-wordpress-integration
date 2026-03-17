<?php
// DgePay API integration class
class DgePay_API {
    private array $config;
    private bool $debug;

    public function __construct(array $config, bool $debug = false) {
        $this->config = $config;
        $this->debug  = $debug;
    }

    private function get_client(): \DgePay\DgePay {
        $client = new \DgePay\DgePay([
            'client_id'      => trim( (string) ( $this->config['client_id'] ?? '' ) ),
            'client_secret'  => trim( (string) ( $this->config['client_secret'] ?? '' ) ),
            'client_api_key' => trim( (string) ( $this->config['client_api_key'] ?? '' ) ),
            'base_url'       => trim( (string) ( $this->config['base_url'] ?? '' ) ),
        ]);

        if ( $this->debug && function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            $client->setLogger( function( $level, $message, $context ) use ( $logger ) {
                $logger->log( $level, $message, array( 'source' => 'dgepay' ) + (array) $context );
            } );
        }

        return $client;
    }

    public function initiate_payment( WC_Order $order, string $redirect_url, string $payment_method = '' ): array {
        try {
            $client = $this->get_client();
        } catch ( \Throwable $e ) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }

        $amount = (float) $order->get_total();

        $unique_txn_id = $order->get_meta( '_dgepay_unique_txn_id', true );
        if ( empty( $unique_txn_id ) && class_exists( '\DgePay\DgePay' ) ) {
            $unique_txn_id = \DgePay\DgePay::generateTransactionId( 'DG' );
            $order->update_meta_data( '_dgepay_unique_txn_id', $unique_txn_id );
            $order->save();
        }

        $customer_id    = (string) $order->get_user_id();
        $billing_email  = (string) $order->get_billing_email();
        $order_number   = (string) $order->get_order_number();

        $payment_data = array(
            'amount'               => $amount,
            'redirectUrl'          => $redirect_url,
            'orderId'              => $unique_txn_id ? (string) $unique_txn_id : (string) $order->get_id(),
            'description'          => sprintf( 'Order #%s', $order_number ),
            'unique_user_reference'=> $customer_id !== '' ? $customer_id : $billing_email,
            'meta_data'            => array(
                'custom_field_1' => $order_number,
                'custom_field_2' => $billing_email,
                'custom_field_3' => $customer_id,
            ),
        );

        if ( ! empty( $payment_method ) ) {
            $payment_data['payment_method'] = $payment_method;
        }

        $result = $client->initiatePayment( $payment_data );

        if ( empty( $result['success'] ) ) {
            return array(
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate DgePay payment.',
            );
        }

        return array(
            'success'        => true,
            'payment_url'    => $result['payment_url'] ?? '',
            'transaction_id' => $result['transaction_id'] ?? (string) $order->get_id(),
        );
    }

    public function parse_callback( string $raw_data = '' ): ?array {
        try {
            $client = $this->get_client();
        } catch ( \Throwable $e ) {
            return null;
        }

        if ( $raw_data === '' ) {
            return null;
        }

        return $client->decryptCallbackData( $raw_data );
    }

    public function normalize_callback_result( array $params ): array {
        try {
            $client = $this->get_client();
        } catch ( \Throwable $e ) {
            return array(
                'is_success'    => false,
                'is_cancelled'  => false,
                'unique_txn_id' => '',
                'message'       => $e->getMessage(),
            );
        }

        return $client->parseCallbackResult( $params );
    }
}
