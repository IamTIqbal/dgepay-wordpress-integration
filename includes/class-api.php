<?php

class Bkash_API {
    private array $config;
    private bool $debug;

    public function __construct( array $config, bool $debug = false ) {
        $this->config = $config;
        $this->debug  = $debug;
    }

    private function log( string $level, string $message, array $context = array() ): void {
        if ( ! $this->debug || ! function_exists( 'wc_get_logger' ) ) {
            return;
        }

        $logger = wc_get_logger();
        $logger->log( $level, $message, array( 'source' => 'bkash' ) + $context );
    }

    private function get_config( string $key ): string {
        return trim( (string) ( $this->config[ $key ] ?? '' ) );
    }

    private function post_json( string $url, array $headers, array $payload ): array {
        $ch = curl_init( $url );
        curl_setopt_array( $ch, array(
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => wp_json_encode( $payload ),
            CURLOPT_HTTPHEADER     => array_merge( array( 'Content-Type: application/json' ), $headers ),
            CURLOPT_TIMEOUT        => 25,
        ) );

        $result   = curl_exec( $ch );
        $httpCode = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error    = curl_error( $ch );
        curl_close( $ch );

        $decoded = is_string( $result ) ? json_decode( $result, true ) : null;

        return array(
            'http_code' => $httpCode,
            'raw'       => $result,
            'data'      => is_array( $decoded ) ? $decoded : null,
            'error'     => $error,
        );
    }

    public function get_id_token(): array {
        $base_url = $this->get_config( 'base_url' );
        $response = $this->post_json(
            $base_url . '/tokenized/checkout/token/grant',
            array(
                'username: ' . $this->get_config( 'username' ),
                'password: ' . $this->get_config( 'password' ),
            ),
            array(
                'app_key'    => $this->get_config( 'app_key' ),
                'app_secret' => $this->get_config( 'app_secret' ),
            )
        );

        $id_token = $response['data']['id_token'] ?? null;
        if ( $response['http_code'] !== 200 || ! $id_token ) {
            $this->log( 'error', 'bKash authentication failed.', array( 'response' => $response ) );
            return array(
                'success' => false,
                'message' => 'bKash authentication failed.',
                'debug'   => $response,
            );
        }

        return array( 'success' => true, 'id_token' => $id_token );
    }

    public function create_payment( string $id_token, array $payment_payload ): array {
        $base_url = $this->get_config( 'base_url' );
        $response = $this->post_json(
            $base_url . '/tokenized/checkout/create',
            array(
                'Authorization: ' . $id_token,
                'x-app-key: ' . $this->get_config( 'app_key' ),
            ),
            $payment_payload
        );

        $bkash_url = $response['data']['bkashURL'] ?? null;
        if ( $response['http_code'] !== 200 || ! $bkash_url ) {
            $this->log( 'error', 'bKash create payment failed.', array( 'response' => $response ) );
            return array(
                'success' => false,
                'message' => $response['data']['statusMessage'] ?? 'Failed to create bKash payment.',
                'debug'   => $response,
            );
        }

        return array(
            'success'    => true,
            'bkash_url'  => $bkash_url,
            'payment_id' => $response['data']['paymentID'] ?? null,
            'raw'        => $response['data'],
        );
    }

    public function execute_payment( string $id_token, string $payment_id ): array {
        $base_url = $this->get_config( 'base_url' );
        $response = $this->post_json(
            $base_url . '/tokenized/checkout/execute',
            array(
                'Authorization: ' . $id_token,
                'x-app-key: ' . $this->get_config( 'app_key' ),
            ),
            array( 'paymentID' => $payment_id )
        );

        $tx_status = $response['data']['transactionStatus'] ?? null;
        if ( $response['http_code'] !== 200 || $tx_status !== 'Completed' ) {
            $this->log( 'error', 'bKash execute payment failed.', array( 'response' => $response ) );
            return array(
                'success' => false,
                'message' => $response['data']['statusMessage'] ?? 'Payment execution failed.',
                'debug'   => $response,
            );
        }

        return array(
            'success' => true,
            'data'    => $response['data'],
        );
    }
}
