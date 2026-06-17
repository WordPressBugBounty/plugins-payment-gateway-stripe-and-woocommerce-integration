<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Centralized Stripe Webhook Handler
 * Registered independently — does not depend on EH_Sepa_Stripe_Gateway being loaded
 * 
 * @since 5.0.1
 */
class EH_Stripe_Webhook_Handler {

    /**
     * Init — called from main plugin file, not from any gateway constructor
     */
    public static function init() {
        add_action( 'woocommerce_api_wt_stripe', array( __CLASS__, 'handle' ) );
        add_action( 'admin_notices', array( __CLASS__, 'maybe_show_webhook_secret_notice' ) );
    }

    /**
     * Show an admin notice when the webhook secret is not configured.
     * Webhook requests are rejected until the secret is configured.
     */
    public static function maybe_show_webhook_secret_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $eh_stripe_option = get_option( 'woocommerce_eh_stripe_pay_settings' );
        $endpoint_secret  = isset( $eh_stripe_option['eh_stripe_webhook_secret'] )
            ? $eh_stripe_option['eh_stripe_webhook_secret']
            : '';

        if ( ! empty( $endpoint_secret ) ) {
            return;
        }

        $settings_url = admin_url( 'admin.php?page=wt_stripe_menu' );
        printf(
            '<div class="notice notice-error" style="border-left-color:#cc0000;background-color:#fff0f0;">'
            . '<p style="font-size:14px;">'
            . '<span style="color:#cc0000;font-size:18px;">&#9888;</span> '
            . '<strong style="color:#cc0000;">%s</strong> %s <a href="%s">%s</a>'
            . '</p></div>',
            esc_html__( 'Stripe Gateway — Security Warning:', 'payment-gateway-stripe-and-woocommerce-integration' ),
            esc_html__( 'Webhook requests are currently blocked because no webhook secret has been configured. To receive Stripe webhook events securely, please set your Stripe webhook secret in', 'payment-gateway-stripe-and-woocommerce-integration' ),
            esc_url( $settings_url ),
            esc_html__( 'Stripe Payment Settings.', 'payment-gateway-stripe-and-woocommerce-integration' )
        );
    }

    /**
     * Main webhook entry point
     * Replaces EH_Sepa_Stripe_Gateway::eh_callback_handler()
     */
    public static function handle() {
        global $wpdb;

        // Ensure Stripe SDK is loaded
        if ( ! class_exists( 'Stripe\Stripe' ) ) {
            include EH_STRIPE_MAIN_PATH . 'vendor/autoload.php';
        }

        $raw_post = file_get_contents('php://input');

        if ( empty( $raw_post ) ) {

            EH_Stripe_Log::log_update(
                'dead',
                array(
                    'message' => 'php://input returned empty',
                    'content_type' => isset($_SERVER['CONTENT_TYPE']) ? sanitize_text_field(wp_unslash($_SERVER['CONTENT_TYPE'])) : 'missing'
                ),
                'Webhook Debug: RAW BODY EMPTY'
            );

            http_response_code(400);
            exit;
        }

        $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) 
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])) 
            : '';
        $eh_stripe_option = get_option("woocommerce_eh_stripe_pay_settings");
        $endpoint_secret = isset($eh_stripe_option["eh_stripe_webhook_secret"]) 
            ? trim( $eh_stripe_option["eh_stripe_webhook_secret"] )
            : '';

        if ( empty( $endpoint_secret ) ) {
            EH_Stripe_Log::log_update(
                'dead',
                array(
                    'message' => 'Webhook secret is not configured.',
                ),
                'Webhook Debug: Signature secret missing'
            );

            status_header( 400 );
            exit();
        }

        if ( empty( $sig_header ) ) {
            EH_Stripe_Log::log_update(
                'dead',
                array(
                    'message' => 'Stripe-Signature header is missing.',
                ),
                'Webhook Debug: Signature header missing'
            );

            status_header( 400 );
            exit();
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $raw_post,
                $sig_header,
                $endpoint_secret,
                1000
            );

            if ( empty( $event ) ) {
                throw new Exception( 'Error Processing Request', 1 );
            }
        } catch ( \Stripe\Exception\SignatureVerificationException $e ) {

            EH_Stripe_Log::log_update(
                'dead',
                array(
                    'error' => $e->getMessage(),
                    'sig_header_sample' => substr($sig_header, 0, 50),
                ),
                'Webhook Debug: Signature FAILED'
            );

            status_header( 400 );
            exit();
        } catch ( \Stripe\Exception\UnexpectedValueException $e ) {

            EH_Stripe_Log::log_update(
                'dead',
                array(
                    'error' => $e->getMessage(),
                ),
                'Webhook Debug: Invalid payload'
            );

            status_header( 400 );
            exit();
        } catch ( Exception $e ) {

            EH_Stripe_Log::log_update(
                'dead',
                array(
                    'error' => $e->getMessage(),
                ),
                'Webhook Debug: Event construction failed'
            );

            status_header( 400 );
            exit();
        }

        $decoded = $event->toArray();
        if ( empty( $decoded ) ) {
            http_response_code( 200 );
            die;
        }

        // Multisite URL match check
        $check_site_url_match = (bool) apply_filters( 'eh_enable_site_miss_match_multisite', false );
        if ( $check_site_url_match ) {
            if ( isset( $decoded['data']['object']['metadata']['site_url'] ) ) {
                $home_url      = home_url();
                $meta_site_url = $decoded['data']['object']['metadata']['site_url'];
                if ( strpos( $meta_site_url, $home_url ) === false ) {
                    wc_get_logger()->debug( 'url miss match', array( 'source' => 'eh_stripe_debug_url_log' ) );
                    http_response_code( 200 );
                    die;
                }

                $check_site_url_match = apply_filters( 'eh_check_site_miss_match_multisite', false, $decoded );
                if($check_site_url_match){
                    wc_get_logger()->debug('url miss match',array('source' => 'eh_stripe_debug_url_log'));
                    exit;
                }
            }
        }

        EH_Stripe_Log::log_update( 'live', $decoded, 'debug code : 1131  ' . get_bloginfo( 'blogname' ) . ' - WebHook event' );

        $sleep_time_interval = (int) abs( apply_filters( 'wtst_webhook_sleep_time', 0 ) );

        $wh_event_type = strtolower( $decoded['type'] );
        $wh_order_id   = isset( $decoded['data']['object']['metadata']['order_id'] )
            ? absint( $decoded['data']['object']['metadata']['order_id'] )
            : 0;

        // Per-order mutex lock — prevents duplicate processing
        $lockable_events = array(
            'charge.succeeded', 'charge.failed',
            'charge.refunded',
            'payment_intent.succeeded', 'payment_intent.payment_failed',
        );

        if ( $wh_order_id && in_array( $wh_event_type, $lockable_events, true ) ) {
            $lock_key = 'wt_stripe_wh_lock_' . $wh_order_id;
            if ( get_transient( $lock_key ) ) {
                // Already being processed
                EH_Stripe_Log::log_update( 'live',
                    array( 'order_id' => $wh_order_id, 'event' => $wh_event_type ),
                    get_bloginfo( 'blogname' ) . ' - Webhook skipped (locked)' );
                http_response_code( 200 );
                die;
            }
            set_transient( $lock_key, $wh_event_type, 2 * MINUTE_IN_SECONDS );
            register_shutdown_function( function() use ( $lock_key ) {
                delete_transient( $lock_key );
            } );
        }

        switch ( $wh_event_type ) {

            case 'charge.succeeded':
            case 'charge.failed':
                self::handle_charge_event( $decoded, $sleep_time_interval );
                break;

            case 'charge.dispute.created':
                self::handle_dispute( $decoded );
                break;

            case 'charge.refunded':
                self::handle_refund( $decoded, $sleep_time_interval );
                break;

            case 'payment_intent.succeeded':
            case 'payment_intent.payment_failed':
                self::handle_payment_intent( $decoded, $wh_event_type, $sleep_time_interval );
                break;

            case 'source.chargeable':
                self::handle_source_chargeable( $decoded );
                break;

            case 'checkout.session.async_payment_succeeded':
            case 'checkout.session.async_payment_failed':
                self::handle_checkout_session( $decoded );
                break;
            case 'checkout.session.expired':
                self::handle_checkout_session_expired( $decoded );
                break;

            default:
                break;
        }

        http_response_code( 200 );
        die;
    }

    // =========================================================================
    // charge.succeeded / charge.failed
    // =========================================================================

    private static function handle_charge_event( $decoded, $sleep_time_interval ) {
        global $wpdb;
        $order_need_processing = apply_filters('wt_stripe_order_need_processing_on_charge',true, $decoded);
        if ( $sleep_time_interval > 0 ) { sleep( $sleep_time_interval ); }

        if ( empty( $decoded['data']['object']['metadata']['order_id'] ) || ! $order_need_processing ) {
            return;
        }

        $order_id       = $decoded['data']['object']['metadata']['order_id'];
        $transaction_id = sanitize_text_field( $decoded['data']['object']['id'] );

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            // HPOS fallback by transaction ID
            // if ( true === EH_Stripe_Payment::wt_stripe_is_HPOS_compatibile() ) {
            //     $meta = $wpdb->get_results( $wpdb->prepare(
            //         "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = '_transaction_id' AND meta_value = %s",
            //         $transaction_id
            //     ) );
            //     if ( ! empty( $meta ) ) {
            //         $order_id = $meta[0]->order_id;
            //         $order    = wc_get_order( $order_id );
            //     }
            // } else {
            //     $meta = $wpdb->get_results( $wpdb->prepare(
            //         "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_transaction_id' AND meta_value = %s",
            //         $transaction_id
            //     ) );
            //     if ( ! empty( $meta ) ) {
            //         $order_id = $meta[0]->post_id;
            //         $order    = wc_get_order( $order_id );
            //     }
            // }

            $order_id = self::get_order_id_by_meta( '_transaction_id', $transaction_id );
            if ( $order_id ) {
                $order = wc_get_order( $order_id );
            }
        }

        // WeChat fallback by payment_intent meta
        if ( ! $order && isset( $decoded['data']['object']['payment_intent'] ) ) {
            $payment_intent_id = sanitize_text_field( $decoded['data']['object']['payment_intent'] );
            // if ( true === EH_Stripe_Payment::wt_stripe_is_HPOS_compatibile() ) {
            //     $meta = $wpdb->get_results( $wpdb->prepare(
            //         "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = '_eh_stripe_payment_intent' AND meta_value = %s",
            //         $payment_intent_id
            //     ) );
            //     if ( ! empty( $meta ) ) { $order_id = $meta[0]->order_id; $order = wc_get_order( $order_id ); }
            // } else {
            //     $meta = $wpdb->get_results( $wpdb->prepare(
            //         "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_eh_stripe_payment_intent' AND meta_value = %s",
            //         $payment_intent_id
            //     ) );
            //     if ( ! empty( $meta ) ) { $order_id = $meta[0]->post_id; $order = wc_get_order( $order_id ); }
            // }

            $order_id = self::get_order_id_by_meta( '_eh_stripe_payment_intent', $payment_intent_id );
            if ( $order_id ) {
                $order = wc_get_order( $order_id );
            }
        }

        if ( ! $order ) { return; }

        $order_status    = $order->get_status();
        $allowed_methods = array( 'eh_eps_stripe', 'eh_klarna_stripe', 'eh_multibanco_stripe', 'eh_p24_stripe', 'eh_sepa_stripe' );
        if ( ! in_array( $order->get_payment_method(), $allowed_methods ) ) { return; }

        $obj1        = new EH_Stripe_Payment();
        $charge_param = $obj1->make_charge_params( $decoded['data']['object'], $order_id );
        EH_Helper_Class::wt_stripe_order_db_operations( $order_id, $order, 'update', '_eh_stripe_payment_charge', $charge_param, false );

        if ( ! in_array( $order_status, array( 'on-hold', 'pending', 'failed' ) ) &&
             ! apply_filters( 'wtst_process_order_on_other_status', false, $order_status ) ) {
            return;
        }

        if ( isset( $decoded['data']['object']['status'] ) && 'succeeded' === $decoded['data']['object']['status'] ) {
            $order->set_transaction_id( $transaction_id );
            //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            $order_time            = date( 'Y-m-d H:i:s', time() + get_option( 'gmt_offset' ) * 3600 );
            $source_type           = $decoded['data']['object']['payment_method_details']['type']
                                     ?? $decoded['data']['object']['source']['type']
                                     ?? 'unknown';
            $balance_transaction_id = ( is_array( $decoded['data']['object']['balance_transaction'] ) && isset( $decoded['data']['object']['balance_transaction']['id'] ) )
                ? $decoded['data']['object']['balance_transaction']['id']
                : ( $decoded['data']['object']['balance_transaction'] ?? 'unknown' );

            if ( true === $decoded['data']['object']['captured'] ) {
                $captured = 'Captured';
                if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
                    $order->payment_complete( $transaction_id );
                }
            } else {
                $captured = 'Uncaptured';
                if ( ! $order->has_status( 'on-hold' ) ) {
                    $order->update_status( 'on-hold' );
                }
            }

            $order->add_order_note(
                __( 'Payment Status : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . ucfirst( $decoded['data']['object']['status'] ) .
                ' [ ' . $order_time . ' ] . ' .
                __( 'Source : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . $source_type .
                '. ' . __( 'Charge Status :', 'payment-gateway-stripe-and-woocommerce-integration' ) . $captured .
                __( '. Transaction ID : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . $balance_transaction_id .
                __( '. via webhook - ', 'payment-gateway-stripe-and-woocommerce-integration' ) . $decoded['type']
            );
        } else {
            $order->update_status( 'failed', __( 'Payment failed.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
        }
    }

    // =========================================================================
    // charge.dispute.created
    // =========================================================================

    private static function handle_dispute( $decoded ) {
        global $wpdb;

        if ( empty( $decoded['data']['object']['charge'] ) ) { return; }

        $charge_id = sanitize_text_field( $decoded['data']['object']['charge'] );

        // if ( true === EH_Stripe_Payment::wt_stripe_is_HPOS_compatibile() ) {
        //     $meta = $wpdb->get_results( $wpdb->prepare(
        //         "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = '_transaction_id' AND meta_value = %s",
        //         $charge_id
        //     ) );
        //     $order_id = ! empty( $meta ) ? $meta[0]->order_id : 0;
        // } else {
        //     $meta = $wpdb->get_results( $wpdb->prepare(
        //         "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_transaction_id' AND meta_value = %s",
        //         $charge_id
        //     ) );
        //     $order_id = ! empty( $meta ) ? $meta[0]->post_id : 0;
        // }

        $order_id = self::get_order_id_by_meta( '_transaction_id', $charge_id );

        if ( ! $order_id ) { return; }

        $order = wc_get_order( $order_id );
        if ( ! $order ) { return; }

        $order->add_order_note(
            __( 'A dispute was created for this order : ', 'payment-gateway-stripe-and-woocommerce-integration' ) .
            $decoded['data']['object']['charge'] .
            __( '. via webhook', 'payment-gateway-stripe-and-woocommerce-integration' )
        );
        $order->update_status( 'failed', __( 'Payment failed.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
    }

    // =========================================================================
    // charge.refunded
    // =========================================================================

    private static function handle_refund( $decoded, $sleep_time_interval ) {
        global $wpdb;

        if ( $sleep_time_interval > 0 ) { sleep( $sleep_time_interval ); }

        if ( ! class_exists( 'Stripe\Stripe' ) ) {
            include EH_STRIPE_MAIN_PATH . 'vendor/autoload.php';
        }

        // Always retrieve refund data from Stripe so webhook payload data is not trusted directly.
        $refund_data = array();
        if ( isset( $decoded['data']['object']['id'] ) ) {
            try {
                $expanded_charge = \Stripe\Charge::retrieve( array(
                    'id'     => $decoded['data']['object']['id'],
                    'expand' => array( 'refunds' ),
                ) );
                $refund_data     = isset( $expanded_charge->refunds )
                    ? json_decode( json_encode( $expanded_charge->refunds ), true )
                    : array();
            } catch ( Exception $e ) {
                EH_Stripe_Log::log_update(
                    'dead',
                    array(
                        'charge_id' => $decoded['data']['object']['id'],
                        'message'   => $e->getMessage(),
                    ),
                    'Webhook Debug: Charge retrieve failed'
                );

                status_header( 500 );
                exit();
            }
        }
        $order_need_processing = apply_filters('wt_stripe_order_need_processing_on_charge_refunded',true, $decoded);
        if ( empty( $refund_data ) || ! $order_need_processing ) {
            return; 
        }

        $charge_id = isset( $refund_data['data']['0']['charge'] )
            ? sanitize_text_field( $refund_data['data']['0']['charge'] )
            : null;

        if ( empty( $charge_id ) ) { return; }

        $order_id = $decoded['data']['object']['metadata']['order_id'] ?? '';

        if ( empty( $order_id ) && isset( $decoded['data']['object']['payment_intent'] ) ) {
            $payment_intent_id = sanitize_text_field( $decoded['data']['object']['payment_intent'] );

            // if ( true === EH_Stripe_Payment::wt_stripe_is_HPOS_compatibile() ) {
            //     $meta = $wpdb->get_results( $wpdb->prepare(
            //         "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = '_eh_stripe_payment_intent' AND meta_value = %s",
            //         $payment_intent_id
            //     ) );
            //     if ( ! empty( $meta ) ) { $order_id = $meta[0]->order_id; }
            // } else {
            //     $meta = $wpdb->get_results( $wpdb->prepare(
            //         "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_eh_stripe_payment_intent' AND meta_value = %s",
            //         $payment_intent_id
            //     ) );
            //     if ( ! empty( $meta ) && ! empty( $meta[0]->post_id ) ) { $order_id = $meta[0]->post_id; }
            //     else { return; }
            // }
            $order_id = self::get_order_id_by_meta( '_eh_stripe_payment_intent', $payment_intent_id );
        }

        $order = wc_get_order( $order_id );
        if ( empty( $order ) || 'refunded' === $order->get_status() ) { return; }

        $auto_process_refund_status = $order->get_meta( 'auto_process_refund_status' );
        $no_wpadmin_flag            = isset( $refund_data['data']['0']['metadata'] ) && ! isset( $refund_data['data']['0']['metadata']['refund_initiated_from'] );

        if ( ! $no_wpadmin_flag && 'yes' !== $auto_process_refund_status ) { return; }

        $obj_stripe_payment  = new EH_Stripe_Payment();
        $amount_to_be_refund = isset( $refund_data['data'][0]['amount'] )
            ? EH_Stripe_Payment::reset_stripe_amount( $refund_data['data'][0]['amount'], $order->get_currency() )
            : EH_Stripe_Payment::reset_stripe_amount( $decoded['data']['object']['amount_refunded'], $order->get_currency() );

        $data   = $obj_stripe_payment->make_refund_params( $decoded['data']['object'], $amount_to_be_refund, $order->get_currency(), $order_id );
        $refund = wc_create_refund( array(
            'amount'     => $amount_to_be_refund,
            'reason'     => $data['reason'],
            'order_id'   => $order_id,
            'line_items' => array(),
        ) );

        if ( is_wp_error( $refund ) ) {
            return;
        }

        do_action( 'woocommerce_refund_processed', $refund, true );
        $refund_id = $refund->get_id();

        if ( $order->get_remaining_refund_amount() > 0 || ( $order->has_free_item() && $order->get_remaining_refund_items() > 0 ) ) {
            do_action( 'woocommerce_order_partially_refunded', $order_id, $refund_id, $refund_id );
        } else {
            do_action( 'woocommerce_order_fully_refunded', $order_id, $refund_id );
            $order->update_status( apply_filters( 'woocommerce_order_fully_refunded_status', 'refunded', $order_id, $refund_id ) );
            if ( isset( $decoded['data']['object']['status'] ) &&
                 'succeeded' === $decoded['data']['object']['status'] &&
                 $decoded['data']['object']['amount_refunded'] == $decoded['data']['object']['amount'] ) {
                $order->update_status( 'refunded', __( 'Refunded.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
            }
        }

        do_action( 'woocommerce_order_refunded', $order_id, $refund_id );
        EH_Helper_Class::wt_stripe_order_db_operations( $order_id, $order, 'add', '_eh_stripe_payment_refund', $data, false );
        $order->add_order_note(
            __( 'Reason : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . $data['reason'] . '.<br>' .
            __( 'Amount : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . get_woocommerce_currency_symbol() . $amount_to_be_refund . '.<br>' .
            __( 'Status : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . ( 'succeeded' === $data['status'] ? 'Success' : 'Failed' ) .
            ' [ ' . $data['created'] . ' ] ' .
            ( is_null( $data['transaction_id'] ) ? '' : '<br>' . __( 'Transaction ID : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . $data['transaction_id'] . __( '. via webhook', 'payment-gateway-stripe-and-woocommerce-integration' ) )
        );
    }

    // =========================================================================
    // payment_intent.succeeded / payment_intent.payment_failed
    // =========================================================================

    private static function handle_payment_intent( $decoded, $wh_event_type, $sleep_time_interval ) {

        if ( $sleep_time_interval > 0 ) { sleep( $sleep_time_interval ); }

        $order_need_processing = apply_filters('wt_stripe_order_need_processing_on_payment_intent',true, $decoded);

        if ( empty( $decoded['data']['object']['id'] ) || ! $order_need_processing ) { 
            return; 
        }

        $intent_id = sanitize_text_field( $decoded['data']['object']['id'] );

        // ── Find order ────────────────────────────────────────────────
        $order = self::get_order_from_intent( $decoded, $intent_id );

        if ( ! $order ) {
            EH_Stripe_Log::log_update( 'dead',
                array( 'intent_id' => $intent_id ),
                'debug code : 1170C  ' . get_bloginfo( 'blogname' ) . ' - payment_intent webhook: order not found' );
            return;
        }

        $order_id       = $order->get_id();
        $order_status   = $order->get_status();
        $payment_method = $order->get_payment_method();

        EH_Stripe_Log::log_update( 'live',
            array(
                'intent_id'      => $intent_id,
                'order_id'       => $order_id,
                'order_status'   => $order_status,
                'payment_method' => $payment_method,
                'event_type'     => $wh_event_type,
            ),
            'debug code : 1170A  ' . get_bloginfo( 'blogname' ) . ' - payment_intent webhook - Order #' . $order_id );

        // ── SEPA/redirect methods — handled by charge.succeeded ───────
        $redirect_methods = array( 'eh_eps_stripe', 'eh_klarna_stripe', 'eh_multibanco_stripe', 'eh_p24_stripe', 'eh_sepa_stripe' );
        if ( in_array( $payment_method, $redirect_methods ) ) {
            if ( 'succeeded' !== ( $decoded['data']['object']['status'] ?? '' ) ) {
                if ( 'failed' !== $order_status ) {
                    $order->update_status( 'failed', __( 'Payment failed.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
                }
            }
            return;
        }

        // ── Guard: skip already-processed orders ──────────────────────
        if ( ! in_array( $order_status, array( 'pending', 'on-hold', 'failed' ) ) &&
             ! apply_filters( 'wtst_process_order_on_other_status', false, $order_status ) ) {
            EH_Stripe_Log::log_update( 'live',
                array( 'order_id' => $order_id, 'order_status' => $order_status ),
                'debug code : 1170D  ' . get_bloginfo( 'blogname' ) . ' - payment_intent webhook: already processed - Order #' . $order_id );
            return;
        }

        // ── payment_intent.payment_failed ─────────────────────────────
        if ( 'payment_intent.payment_failed' === $wh_event_type ) {
            if ( 'failed' !== $order_status ) {
                $order->update_status( 'failed', __( 'Payment failed.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
            }
            EH_Stripe_Log::log_update( 'dead',
                array( 'order_id' => $order_id ),
                'debug code : 1171A  ' . get_bloginfo( 'blogname' ) . ' - payment_intent.payment_failed - Order #' . $order_id );
            return;
        }

        // ── payment_intent.succeeded ──────────────────────────────────
        if ( ( $decoded['data']['object']['status'] ?? '' ) !== 'succeeded' ) { return; }

        try {
            // ✅ Basil: always use latest_charge expand
            $expanded_intent = \Stripe\PaymentIntent::retrieve( array(
                'id'     => $intent_id,
                'expand' => array( 'latest_charge.balance_transaction' ),
            ) );
        } catch ( Exception $e ) {
            EH_Stripe_Log::log_update( 'dead',
                array( 'intent_id' => $intent_id, 'message' => $e->getMessage() ),
                'debug code : 1170E  ' . get_bloginfo( 'blogname' ) . ' - payment_intent: retrieve failed - Order #' . $order_id );
            return;
        }

        $latest_charge = $expanded_intent->latest_charge;

        EH_Stripe_Log::log_update( 'live',
            array(
                'charge_id'   => $latest_charge ? $latest_charge->id   : 'null',
                'charge_paid' => $latest_charge ? $latest_charge->paid  : 'null',
            ),
            'debug code : 1170B  ' . get_bloginfo( 'blogname' ) . ' - payment_intent latest_charge - Order #' . $order_id );

        if ( ! $latest_charge ) {
            EH_Stripe_Log::log_update( 'dead',
                array( 'intent_id' => $intent_id ),
                'debug code : 1170F  ' . get_bloginfo( 'blogname' ) . ' - payment_intent: no latest_charge - Order #' . $order_id );
            return;
        }

        $obj          = new EH_Stripe_Payment();
        $charge_param = $obj->make_charge_params( $latest_charge, $order_id );
        EH_Helper_Class::wt_stripe_order_db_operations( $order_id, $order, 'update', '_eh_stripe_payment_charge', $charge_param, false );
        //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $order_time = date( 'Y-m-d H:i:s', time() + get_option( 'gmt_offset' ) * 3600 );

        if ( true === $latest_charge->paid ) {
            if ( true === $latest_charge->captured ) {
                if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
                    $order->payment_complete( $charge_param['id'] );
                }
            } elseif ( 'on-hold' !== $order->get_status() ){
                $order->update_status( 'on-hold' );
            }
            $order->add_order_note(
                __( 'Payment Status : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . ucfirst( $charge_param['status'] ) .
                ' [ ' . $order_time . ' ] . ' .
                __( 'Source : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . $charge_param['source_type'] .
                '. ' . __( 'Charge Status :', 'payment-gateway-stripe-and-woocommerce-integration' ) . $charge_param['captured'] .
                ( is_null( $charge_param['transaction_id'] ) ? '' : '. <br>' . __( 'Transaction ID : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . $charge_param['transaction_id'] ) .
                __( '. via webhook - ', 'payment-gateway-stripe-and-woocommerce-integration' ) . 'payment_intent.succeeded'
            );
            if ( ! is_null( WC()->cart ) ) {
                WC()->cart->empty_cart();
            }
            EH_Stripe_Log::log_update( 'live', $charge_param,
                'debug code : 1132  ' . get_bloginfo( 'blogname' ) . ' - Charge - Order #' . $order->get_order_number() );
        } else {
            EH_Stripe_Log::log_update( 'dead', $latest_charge,
                'debug code : 1133  ' . get_bloginfo( 'blogname' ) . ' - Charge not paid - Order #' . $order->get_order_number() );
        }
    }

    // =========================================================================
    // source.chargeable
    // =========================================================================

    private static function handle_source_chargeable( $decoded ) {

        $order_id = $decoded['data']['object']['metadata']['order_id'] ?? '';
        $order_need_processing = apply_filters('wt_stripe_order_need_processing_on_source',true, $decoded);

        if ( empty( $order_id ) && isset( $decoded['data']['object']['redirect']['return_url'] ) ) {
            $arr_parts = wp_parse_url( $decoded['data']['object']['redirect']['return_url'] );
            if ( ! empty( $arr_parts['query'] ) ) {
                wp_parse_str( $arr_parts['query'], $arr_params );
                $order_id = $arr_params['order_id'] ?? '';
            }
        }

        if ( empty( $order_id ) || ! $order_need_processing ) {
            return; 
        }

        $source_id = sanitize_text_field( $decoded['data']['object']['id'] );
        $order     = wc_get_order( $order_id );

        if ( ! $order || ( ! $order->has_status( 'on-hold' ) && ! $order->has_status( 'pending' ) ) ) { return; }

        if ( ! class_exists( 'Stripe\Stripe' ) ) {
            include EH_STRIPE_MAIN_PATH . 'vendor/autoload.php';
        }

        $source_response = \Stripe\Source::retrieve( $source_id );
        if ( ! isset( $source_response->status ) || 'chargeable' !== $source_response->status ) { return; }

        $objKlarna      = new EH_Klarna_Gateway();
        $charge_response = \Stripe\Charge::create(
            $objKlarna->eh_make_charge_params( $order, $source_response->id ),
            array( 'idempotency_key' => $order->get_order_key() )
        );
        $objKlarna->eh_process_payment_response( $charge_response, $order, true );
    }

    // =========================================================================
    // checkout.session.async_payment_succeeded / failed
    // =========================================================================

    private static function handle_checkout_session( $decoded ) {

        $order_id = $decoded['data']['object']['metadata']['order_id'] ?? '';
        $order_need_processing = apply_filters( 'wt_stripe_order_need_processing_on_checkout', true, $decoded );
        if ( empty( $order_id ) && isset( $decoded['data']['object']['cancel_url'] ) ) {
            $arr_parts = wp_parse_url( $decoded['data']['object']['cancel_url'] );
            if ( ! empty( $arr_parts['query'] ) ) {
                wp_parse_str( $arr_parts['query'], $arr_params );
                $order_id = $arr_params['order_id'] ?? '';
            }
        }

        if ( empty( $order_id ) || ! $order_need_processing ) { return; }

        $order = wc_get_order( $order_id );
        if ( ! $order ) { return; }

        $order_status = $order->get_status();
        if ( ! in_array( $order_status, array( 'on-hold', 'pending', 'failed' ) ) ) { return; }

        if ( ! empty( $decoded['data']['object']['payment_intent'] ) ) {
            $obj_checkout = new Eh_Stripe_Checkout();
            $obj_checkout->wt_process_payment_intent( $order_id, $order, $decoded['data']['object']['payment_intent'] );
        }
    }

    // =========================================================================
    // checkout.session.expired
    // =========================================================================

    private static function handle_checkout_session_expired( $decoded ) {

        $order_need_processing = apply_filters('wt_stripe_order_need_processing_on_checkout',true, $decoded);
        $order_id = null;

        if (isset($decoded['data']['object']['metadata']['order_id']) && !empty($decoded['data']['object']['metadata']['order_id']) && $order_need_processing) {
            $order_id = $decoded['data']['object']['metadata']['order_id'];
        }
        elseif(isset($decoded['data']['object']['success_url']) && !empty($decoded['data']['object']['success_url'])){ 
            $arr_parts = wp_parse_url($decoded['data']['object']['success_url']);
            if(isset($arr_parts) && !empty($arr_parts) && isset($arr_parts['query']) && !empty($arr_parts['query'])){ 
                wp_parse_str($arr_parts['query'], $arr_params);
                if(!empty($arr_params) && isset($arr_params['order_id']) && !empty($arr_params['order_id'])){
                        $order_id = $arr_params['order_id'];
                }
            }
        }
        if(isset($order_id) && !empty($order_id)){ 
            $order = wc_get_order( $order_id );
            if($order){   
                if('eh_stripe_checkout' === $order->get_payment_method() && ! $order->has_status( array( 'processing', 'completed' ) ) ){
                    $session_id = ( isset( $decoded['data']['object']['id'] ) && ! empty( $decoded['data']['object']['id'] ) )
                    ? $decoded['data']['object']['id']
                    : '';
                    if(empty($session_id)){
                        return;
                    }                                                  
                    $session = \Stripe\Checkout\Session::retrieve($session_id);

                    $intent = $order->get_meta( '_eh_stripe_payment_intent' );                                     
                    if($intent == $session->payment_intent){
                        $order->update_status( 'cancelled', __( 'Stripe checkout abandoned.', 'payment-gateway-stripe-and-woocommerce-integration' ));

                    }
                }
            }
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Find WC order from webhook payload.
     * Tries metadata order_id first, then meta lookup by intent ID.
     */
    public static function get_order_from_intent( $decoded, $intent_id ) {
        global $wpdb;

        // 1. Try metadata order_id
        if ( ! empty( $decoded['data']['object']['metadata']['order_id'] ) ) {
            $order_id = absint( $decoded['data']['object']['metadata']['order_id'] );
            $order    = wc_get_order( $order_id );
            if ( ! $order && class_exists( 'Wt_Advanced_Order_Number' ) ) {
                // $query = new WP_Query( array(
                //     'post_type'   => 'shop_order',
                //     'post_status' => 'any',
                //     'meta_query'  => array( array(
                //         'key'     => '_order_number',
                //         'value'   => $order_id,
                //         'compare' => '=',
                //     ) ),
                // ) );
                // if ( ! empty( $query->posts ) ) {
                //     $order = wc_get_order( $query->posts[0]->ID );
                // }
                // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WebToffee Sequential Order Numbers for WooCommerce plugin compatibility
                $orders = wc_get_orders( array(
                    'limit'      => 1,
                    'meta_key'   => '_order_number',
                    'meta_value' => $order_id,
                ) );
                // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                if ( ! empty( $orders ) ) {
                    $order = $orders[0];
                }
            }

            if ( $order ) { return $order; }
        }

        // 2. Fallback: lookup by _eh_stripe_payment_intent meta
        // if ( true === EH_Stripe_Payment::wt_stripe_is_HPOS_compatibile() ) {
        //     $meta = $wpdb->get_results( $wpdb->prepare(
        //         "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = '_eh_stripe_payment_intent' AND meta_value = %s",
        //         $intent_id
        //     ) );
        //     $order_id = ! empty( $meta ) ? absint( $meta[0]->order_id ) : 0;
        // } else {
        //     $meta = $wpdb->get_results( $wpdb->prepare(
        //         "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_eh_stripe_payment_intent' AND meta_value = %s",
        //         $intent_id
        //     ) );
        //     $order_id = ! empty( $meta ) ? absint( $meta[0]->post_id ) : 0;
        // }

        $order_id = self::get_order_id_by_meta( '_eh_stripe_payment_intent', $intent_id );

        return $order_id ? wc_get_order( $order_id ) : false;
    }

    private static function get_order_id_by_meta( $meta_key, $meta_value ) {
        
         global $wpdb;

        if ( true === EH_Stripe_Payment::wt_stripe_is_HPOS_compatibile() ) {
            //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- No WC API for HPOS meta lookup by value.
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                $meta_key,
                $meta_value
            ) );
        } else {
            //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- No WC API for postmeta lookup by value
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                $meta_key,
                $meta_value
            ) );
        }

        return $result ? absint( $result ) : 0;
    }
}
