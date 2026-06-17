<?php

if (!defined('ABSPATH')) {
    exit;
}  

/**
 * EH_Stripe_Token_Handler class handling token initialisation.
 * @since 4.0.4
 *
 */
 
class EH_Stripe_Token_Handler {
    private static $instance = null;
    private static $is_initialized = false;

    private function __construct() {
        add_action( 'admin_notices', array( __CLASS__, 'wtst_oauth_refresh_admin_notice' ) );
        add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'wtst_maybe_disable_stripe_gateways' ), 1000 );
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function init_stripe_api() {
        // Only initialize once
        if (!self::$is_initialized) { 
            $api_key = self::get_stripe_api_key(); // Get current token
            if (empty($api_key)) {
                EH_Stripe_Log::log_update('oauth', 'Empty API key', 'Stripe init error');
                return;
            }
            \Stripe\Stripe::setApiKey($api_key);
            \Stripe\Stripe::setApiVersion(self::wt_get_api_version());
            \Stripe\Stripe::setAppInfo(
                'WordPress Stripe Payment Gateway for WooCommerce', 
                EH_STRIPE_VERSION, 
                'https://www.themehigh.com/product/woocommerce-stripe-payment-gateway/',
                'pp_partner_KHip9dhhenLx0S'
            );
    
            self::$is_initialized = true;
        }
    }


    /**
     * function to get stripe api key.
     */
    private static function get_stripe_api_key(){
        
        $stripe_settings  = get_option( 'woocommerce_eh_stripe_pay_settings');
        if(!$stripe_settings){
            return false;
        }        
        $mode = isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live';
        $test_mode = EH_Stripe_Token_Handler::get_stripe_test_mode_type();
        if(!empty($mode)){
            if(Eh_Stripe_Admin_Handler::wtst_oauth_compatible($mode)){ 
                if(!self::wtst_get_oauth_expired($mode)){
                    //$wt_stripe_access_token = $mode === 'test' ? 'wt_stripe_access_token_test' : 'wt_stripe_access_token_live';
                    if ( 'test' === $mode ) {
                        $wt_stripe_access_token = ( 'sandbox' === $test_mode )
                            ? 'wt_stripe_access_token_sandbox'
                            : 'wt_stripe_access_token_test';
                    } else {
                        $wt_stripe_access_token = 'wt_stripe_access_token_live';
                    }
                    return base64_decode(self::wtst_get_site_option('get', array('name' => $wt_stripe_access_token)));
                } else {
                    if ( self::wtst_is_refresh_cooldown_active( $mode, $test_mode ) ) {
                        self::wtst_schedule_oauth_refresh( $mode, $test_mode );
                        return self::get_current_access_token($mode, $test_mode);
                    }

                    $api_key = self::wtst_refresh_token();
                    return $api_key ? $api_key : self::get_current_access_token($mode, $test_mode);
                }
            }
    
            else{ 
                //if oauth is not compatible then return the secret key
                if ('test' === $mode) {
                    $secret_key = isset($stripe_settings['eh_stripe_test_secret_key']) ? $stripe_settings['eh_stripe_test_secret_key'] : null;
                    return $secret_key;
                } else {
                    $secret_key = isset($stripe_settings['eh_stripe_live_secret_key']) ? $stripe_settings['eh_stripe_live_secret_key'] : null;

                    return $secret_key;
                }
            }
        }
    }
    

    public static function wt_get_api_version(){
       //return apply_filters('wt_stripe_api_version', '2022-08-01');
       // Updated for New - Basil 17.5
        return apply_filters('wt_stripe_api_version', '2025-03-31.basil');
    }

    /**
     * Function calling Refresh token API.
     * @return refresh token and access token
     * @since 4.0.4
     * 
     */
   /* private static function wtst_refresh_token($force = false)
    {
        $lock_folder_path = self::get_temp_dir();
        $lock_file_path = $lock_folder_path . '/stripe_token_refresh.lock';
        $retry_count = 0;
        $max_retries = 3;
        $retry_delay = 2; // seconds

        while ($retry_count < $max_retries) {
            //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
            $lock_handle = fopen($lock_file_path, 'w+');
            if ($lock_handle === false) {
                throw new Exception('Could not open lock file.');
            }

            $stripe_settings = get_option("woocommerce_eh_stripe_pay_settings", array());
            $stripe_settings["eh_stripe_mode"] = (isset($stripe_settings["eh_stripe_mode"]) && !empty($stripe_settings["eh_stripe_mode"])) ? $stripe_settings["eh_stripe_mode"] : 'live';
            $test_mode = EH_Stripe_Token_Handler::get_stripe_test_mode_type();

            if(!self::wtst_get_oauth_expired($stripe_settings["eh_stripe_mode"]) && !$force){
                $wt_stripe_access_token =  $stripe_settings["eh_stripe_mode"] === 'test' ? 'wt_stripe_access_token_test' : 'wt_stripe_access_token_live';
                $wt_stripe_access_token = ($stripe_settings["eh_stripe_mode"] === 'test' && $test_mode === 'sandbox') ? 'wt_stripe_access_token_sandbox' : $wt_stripe_access_token;
                return base64_decode(self::wtst_get_site_option('get', array('name' => $wt_stripe_access_token)));
            }

            $app_author = get_option('eh_stripe_connected_app_author');

            // Ensure the file handle is a valid resource before attempting to lock
            if (is_resource($lock_handle) && flock($lock_handle, LOCK_EX | LOCK_NB)) {
                try {
                    $access_token_url = EH_STRIPE_OAUTH_TH_URL . 'get-access-token';
                    if(!$app_author || $app_author !== 'themehigh'){
                        $access_token_url = EH_STRIPE_OAUTH_WT_URL . 'get-access-token';
                    }
                   
                    $instance = self::get_instance();

                    if('test' === $stripe_settings["eh_stripe_mode"]){ 
                        //Clear cache for the tokens to get the newly updated values
                        if($test_mode === 'sandbox'){
                            $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_sandbox', 'wt_stripe_account_id_sandbox'));

                            $refresh_token = base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_sandbox')));
                            $account_id = self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_sandbox'));

                        }else{
                            $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_test', 'wt_stripe_account_id_test'));

                            $refresh_token = base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_test')));
                            $account_id = self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_test'));
                        }
                    }
                    else{ 
                        //Clear cache for the tokens to get the newly updated values
                        $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_live', 'wt_stripe_account_id_live'));

                        $refresh_token = base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_live')));
                        $account_id = self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_live'));

                    }

                    if(!$refresh_token){
                        require_once(EH_STRIPE_MAIN_PATH . 'includes/class-stripe-oauth.php');
                        EH_Stripe_Oauth::wtst_oauth_disconnect(true);
                        throw new Exception('Refresh token not found!');
                    }
                    // JSON data to send in the POST request body.
                    $access_token_req_data = array(
                        'refresh_token' => sanitize_text_field($refresh_token),
                        'mode' => sanitize_text_field($stripe_settings["eh_stripe_mode"]),
                        'test_mode_type' => sanitize_text_field($test_mode),
                        'account_id' => sanitize_text_field($account_id),

                    );

                    EH_Stripe_Log::log_update('oauth', $access_token_req_data,'Refresh token API request');

                    // Convert the data to JSON format.
                    $access_token_json_data = wp_json_encode( $access_token_req_data );

                    // Arguments for the POST request.
                    $access_token_args = array(
                        'body'    => $access_token_json_data,
                        'headers' => array(
                            'Content-Type' => 'application/json', // Tell the server it's JSON.
                            'User-Agent' => self::wt_get_api_user_agent(), 
                        ),
                        'timeout' => apply_filters("wtst_refresh_token_timeout", 60), // Optional: Set a timeout for the request.
                        'connect_timeout' => apply_filters("wtst_refresh_token_connect_timeout", 25), // Connection timeout
                    );

                    // Make the POST request.
                    $access_token_response = wp_safe_remote_post( $access_token_url, $access_token_args );

                    EH_Stripe_Log::log_update('oauth', $access_token_response,'Refresh token API response');
                    
                    // Handle the response.
                    if ( is_wp_error( $access_token_response ) ) {
                        // There was an error in the request.
                        $error_message = $access_token_response->get_error_message();
                        throw new Exception('WP error - ' . $error_message);
                    } else {
                        // Process the response body.
                        if(is_array($access_token_response) && isset($access_token_response['body'])){
                            $response_body = json_decode($access_token_response['body'], true);
                            $issue_with_refresh_token = false;
                            
                            if (isset($response_body['error'])) {
                                $error_message = $response_body['error'];
                                if (strpos($error_message, 'invalid_grant') !== false) {
                                    $issue_with_refresh_token = true;
                                } elseif (strpos($error_message, 'empty string for \'refresh_token\'') !== false) {
                                    $issue_with_refresh_token = true;
                                }
                            }
                            
                            if($issue_with_refresh_token){
                                require_once(EH_STRIPE_MAIN_PATH . 'includes/class-stripe-oauth.php');
                                EH_Stripe_Oauth::wtst_oauth_disconnect(true);   
                            }
                        }
                        
                        $decoded_response = json_decode(wp_remote_retrieve_body($access_token_response), true);
                        EH_Stripe_Log::log_update('oauth', $decoded_response,'Refresh token API response parsed');

                        // Check if response contains any error
                        if (isset($decoded_response['error'])) {
                            self::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));
                            throw new Exception('Error: ' . (isset($decoded_response['error']) ? $decoded_response['error'] . ' - ' : '') . (isset($decoded_response['error_description']) ? $decoded_response['error_description'] : ''));
                        } elseif(isset($decoded_response['access_token']) && isset($decoded_response['refresh_token'])) { 
                            $access_token = sanitize_text_field($decoded_response['access_token']);
                            $refresh_token = (isset($decoded_response['refresh_token']) ? sanitize_text_field($decoded_response['refresh_token'])  : '');
                            $account_id = (isset($decoded_response['stripe_user_id']) ? sanitize_text_field($decoded_response['stripe_user_id'])  : '');
                            $stripe_publishable_key = (isset($decoded_response['stripe_publishable_key']) ? sanitize_text_field($decoded_response['stripe_publishable_key'])  : '');
                            $expiry_time = (isset($decoded_response['transient_expiry']) ? sanitize_text_field($decoded_response['transient_expiry'])  : '');

                        
                            $mode_prefix = ('test' === $stripe_settings["eh_stripe_mode"]) ? 'test' : 'live';
                            $mode_prefix = ('test' === $stripe_settings ["eh_stripe_mode"] && $test_mode === 'sandbox'] ? 'sandbox' : $mode_prefix;
                            $option_names = [
                                'wtst_oauth_expriy_' . $mode_prefix => time(),
                                'wt_stripe_access_token_' . $mode_prefix => base64_encode($access_token),
                                'wt_stripe_refresh_token_' . $mode_prefix => base64_encode($refresh_token),
                                'wt_stripe_' . $mode_prefix . '_publishable_key' => $stripe_publishable_key
                            ];

                            foreach ($option_names as $name => $value) {
                                self::wtst_get_site_option('update', [
                                    'name' => $name,
                                    'value' => $value
                                ]);
                            }

                            if (function_exists('as_unschedule_all_actions')) {
                                as_unschedule_all_actions('eh_stripe_refresh_oauth_token', null);
                            }
                            if (!as_next_scheduled_action('eh_stripe_refresh_oauth_token')) {
                                as_schedule_recurring_action(time(), 50 * MINUTE_IN_SECONDS, 'eh_stripe_refresh_oauth_token');
                            }

                            return $access_token;

                        }
                        else{
                            throw new Exception('Unknown response!');
                        }       

                    }
                        
                }
                catch (Exception $e) {
                    if (is_resource($lock_handle)) {
                        flock($lock_handle, LOCK_UN);
                        //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                        fclose($lock_handle);

                    }
                    if(file_exists($lock_file_path)){
                        //phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                        unlink($lock_file_path);
                    }
                    EH_Stripe_Log::log_update('oauth', $e->getMessage(),'Refresh token API error');
                   
                    if(!is_admin()){
                        if (function_exists('wc_add_notice')) {
                            // translators: Error message asking user to try again later 
                            wc_add_notice(__('Please try again after some time', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
                        }
                    }
                }
                finally {
                    if (is_resource($lock_handle)) {
                        flock($lock_handle, LOCK_UN);
                        //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                        fclose($lock_handle);
                    }
                    if(file_exists($lock_file_path)){
                        //phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                        unlink($lock_file_path);
                    }
                }
                break; // Exit the retry loop if successful
            } else {
                //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($lock_handle);
                $retry_count++;
                if ($retry_count < $max_retries) {    
                    sleep($retry_delay+1);
                } else {
                    EH_Stripe_Log::log_update('oauth', 'Failed to acquire lock after multiple attempts.','Refresh token API error');
                 
                    if(!is_admin()){
                        if (function_exists('wc_add_notice')) {
                            // translators: Error message asking user to try again later 
                            wc_add_notice(__('Please try again after some time', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
                        }
                    }
                }
            }
        }
    }*/

    /*************  Refresh token function -START ***********/

    private static function wtst_refresh_token( $force = false, $mode = null, $test_mode = null ) {

        $lock_file_path = self::get_temp_dir() . '/stripe_token_refresh.lock';

        // Resolve settings safely
        $stripe_settings = get_option( 'woocommerce_eh_stripe_pay_settings', array() );
        $mode = $mode ?: ( ! empty( $stripe_settings['eh_stripe_mode'] ) ? $stripe_settings['eh_stripe_mode'] : 'live' );
        $test_mode = $test_mode ?: EH_Stripe_Token_Handler::get_stripe_test_mode_type();

        // Fast-path: token still valid and no force refresh
        if ( ! self::wtst_get_oauth_expired( $mode ) && ! $force ) {
            return self::get_current_access_token($mode, $test_mode);
        }

        if ( ! $force && self::wtst_is_refresh_cooldown_active( $mode, $test_mode ) ) {
            EH_Stripe_Log::log_update( 'oauth', self::wtst_get_refresh_failure_state( $mode, $test_mode ), 'Refresh token skipped during cooldown' );
            return self::get_current_access_token($mode, $test_mode);
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Needed for file locking with flock()
        $lock_handle = fopen( $lock_file_path, 'c+' );
        if ( ! $lock_handle ) {
            EH_Stripe_Log::log_update( 'oauth', 'Unable to open lock file', 'Refresh token error' );
            self::wtst_record_refresh_failure( $mode, $test_mode, 'Unable to open lock file', 'retryable' );
            return self::get_current_access_token($mode, $test_mode);
        }

        if ( ! flock( $lock_handle, LOCK_EX | LOCK_NB ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing lock file
            fclose( $lock_handle );
            EH_Stripe_Log::log_update( 'oauth', 'Refresh lock already held; failing fast', 'Refresh token lock busy' );
            return self::get_current_access_token($mode, $test_mode);
        }

        try {
            // Double-check expiry after acquiring lock (another process may have refreshed)
            if ( ! self::wtst_get_oauth_expired( $mode ) && ! $force ) {
                self::wtst_clear_refresh_failure_state( $mode, $test_mode );
                return self::get_current_access_token($mode, $test_mode);
            }

            $access_token = self::execute_token_refresh($mode, $test_mode, $force);
            self::wtst_clear_refresh_failure_state( $mode, $test_mode );
            return $access_token;
        } catch ( Exception $e ) {
            $error_type = self::wtst_classify_refresh_error( $e->getMessage() );
            EH_Stripe_Log::log_update( 'oauth', array( 'message' => $e->getMessage(), 'type' => $error_type ), 'Refresh token error' );
            self::wtst_record_refresh_failure( $mode, $test_mode, $e->getMessage(), $error_type );
            self::handle_refresh_error();
            return self::get_current_access_token($mode, $test_mode);
        } finally {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_flock -- Required for atomic locking
            flock( $lock_handle, LOCK_UN );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing lock file
            fclose( $lock_handle );
        }
    }

    /**
     * Get current valid access token
     */
    private static function get_current_access_token($mode, $test_mode){
        $token_option = ('test' === $mode)
            ? ('sandbox' === $test_mode ? 'wt_stripe_access_token_sandbox' : 'wt_stripe_access_token_test')
            : 'wt_stripe_access_token_live';
        
        $token = self::wtst_get_site_option('get', array('name' => $token_option));
        return $token ? base64_decode($token) : false;
    }

    private static function wtst_schedule_oauth_refresh( $mode, $test_mode ) {
        $state = self::wtst_get_refresh_failure_state( $mode, $test_mode );
        $schedule_time = ( ! empty( $state['cooldown_until'] ) && time() < absint( $state['cooldown_until'] ) )
            ? absint( $state['cooldown_until'] ) + 1
            : time();
        $queue_key = 'wtst_oauth_refresh_queued_' . self::wtst_get_mode_prefix( $mode, $test_mode );

        if ( get_transient( $queue_key ) ) {
            return;
        }

        set_transient( $queue_key, 'yes', max( 5 * MINUTE_IN_SECONDS, $schedule_time - time() + MINUTE_IN_SECONDS ) );
        $args = array(
            'mode'      => $mode,
            'test_mode' => $test_mode,
        );

        if ( function_exists('as_schedule_single_action') ) {
            as_schedule_single_action($schedule_time, 'eh_stripe_refresh_oauth_token', $args);
        } elseif ( ! wp_next_scheduled( 'eh_stripe_refresh_oauth_token', $args ) ) {
            wp_schedule_single_event($schedule_time, 'eh_stripe_refresh_oauth_token', $args);
        }
    }

    private static function wtst_get_mode_prefix($mode, $test_mode){
        if ( 'test' === $mode ) {
            return ( 'sandbox' === $test_mode ) ? 'sandbox' : 'test';
        }

        return 'live';
    }

    private static function wtst_get_refresh_failure_option($mode, $test_mode){
        return 'wtst_oauth_refresh_failure_' . self::wtst_get_mode_prefix($mode, $test_mode);
    }

    private static function wtst_get_refresh_failure_state($mode, $test_mode){
        $state = self::wtst_get_site_option('get', array('name' => self::wtst_get_refresh_failure_option($mode, $test_mode)));
        return is_array($state) ? $state : array();
    }

    private static function wtst_is_refresh_cooldown_active($mode, $test_mode){
        $state = self::wtst_get_refresh_failure_state($mode, $test_mode);
        return ! empty($state['cooldown_until']) && time() < absint($state['cooldown_until']);
    }

    private static function wtst_record_refresh_failure($mode, $test_mode, $message, $type = 'retryable'){
        $state = self::wtst_get_refresh_failure_state($mode, $test_mode);
        $count = isset($state['count']) ? absint($state['count']) + 1 : 1;
        $cooldown = ( 'terminal' === $type )
            ? apply_filters('wtst_oauth_terminal_error_cooldown', HOUR_IN_SECONDS)
            : apply_filters('wtst_oauth_retryable_error_cooldown', 10 * MINUTE_IN_SECONDS);

        self::wtst_get_site_option('update', array(
            'name'  => self::wtst_get_refresh_failure_option($mode, $test_mode),
            'value' => array(
                'count'          => $count,
                'type'           => $type,
                'message'        => sanitize_text_field($message),
                'last_failed_at' => time(),
                'cooldown_until' => time() + absint($cooldown),
            ),
        ));
    }

    private static function wtst_clear_refresh_failure_state($mode, $test_mode){
        self::wtst_get_site_option('delete', array('name' => self::wtst_get_refresh_failure_option($mode, $test_mode)));
    }

    private static function wtst_classify_refresh_error($message){
        $message = strtolower($message);
        $terminal_markers = array(
            'invalid_grant',
            'refresh token missing',
            'refresh_token revoked',
            'empty string for \'refresh_token\'',
        );

        foreach ( $terminal_markers as $marker ) {
            if ( false !== strpos($message, $marker) ) {
                return 'terminal';
            }
        }

        return 'retryable';
    }

    public static function wtst_oauth_refresh_admin_notice(){
        if ( ! current_user_can('manage_woocommerce') ) {
            return;
        }

        $stripe_settings = get_option( 'woocommerce_eh_stripe_pay_settings', array() );
        $mode = ! empty( $stripe_settings['eh_stripe_mode'] ) ? $stripe_settings['eh_stripe_mode'] : 'live';
        $test_mode = EH_Stripe_Token_Handler::get_stripe_test_mode_type();

        $state = self::wtst_get_refresh_failure_state($mode, $test_mode);
        if ( empty($state['message']) ) {
            return;
        }

        echo '<div class="notice notice-error"><p>' . esc_html__('Stripe OAuth token refresh failed. Stripe payment methods may be unavailable until the connection is refreshed or reconnected.', 'payment-gateway-stripe-and-woocommerce-integration') . '</p><p><code>' . esc_html($state['message']) . '</code></p></div>';
    }

    public static function wtst_maybe_disable_stripe_gateways($gateways){
        $stripe_settings = get_option( 'woocommerce_eh_stripe_pay_settings', array() );
        $mode = $stripe_settings['eh_stripe_mode'] ?? 'live';
        $test_mode = EH_Stripe_Token_Handler::get_stripe_test_mode_type();
        $current_token = self::get_current_access_token($mode, $test_mode);

        if ( ! Eh_Stripe_Admin_Handler::wtst_oauth_compatible($mode) || ( $current_token && ! empty(trim($current_token)) ) ) {
            return $gateways;
        }

        foreach ( self::wtst_get_stripe_gateway_ids() as $gateway_id ) {
            unset($gateways[$gateway_id]);
        }

        return $gateways;
    }

    private static function wtst_get_stripe_gateway_ids(){
        return array('eh_multibanco_stripe', 'eh_grabpay_stripe', 'eh_oxxo_stripe', 'eh_boleto_stripe', 'eh_fpx_stripe', 'eh_becs_stripe', 'eh_bacs', 'eh_giropay_stripe', 'eh_p24_stripe', 'eh_eps_stripe', 'eh_bancontact_stripe', 'eh_ideal_stripe', 'eh_sofort_stripe', 'eh_wechat_stripe', 'eh_afterpay_stripe', 'eh_klarna_stripe', 'eh_sepa_stripe', 'eh_stripe_pay', 'eh_alipay_stripe', 'eh_stripe_checkout', 'eh_affirm_stripe');
    }

    private static function wtst_is_background_context(){
        return ( function_exists('wp_doing_cron') && wp_doing_cron() ) || ( defined('WP_CLI') && WP_CLI );
    }
    /**
     * Execute the actual token refresh logic
    */
    private static function execute_token_refresh($mode, $test_mode, $force = false) {

        $instance   = self::get_instance();
        $app_author = get_option( 'eh_stripe_connected_app_author' );

        $access_token_url = ( $app_author === 'themehigh' )
            ? EH_STRIPE_OAUTH_TH_URL . 'get-access-token'
            : EH_STRIPE_OAUTH_WT_URL . 'get-access-token';

        // Resolve refresh token + account ID
        if ( 'test' === $mode ) {

            if ( 'sandbox' === $test_mode ) {
                $instance->wtst_clear_cache_for_options(
                    array( 'wt_stripe_refresh_token_sandbox', 'wt_stripe_account_id_sandbox' )
                );
                $refresh_token = base64_decode(
                    self::wtst_get_site_option( 'get', array( 'name' => 'wt_stripe_refresh_token_sandbox' ) )
                );
                $account_id = self::wtst_get_site_option( 'get', array( 'name' => 'wt_stripe_account_id_sandbox' ) );
                $mode_prefix = 'sandbox';
            } else {
                $instance->wtst_clear_cache_for_options(
                    array( 'wt_stripe_refresh_token_test', 'wt_stripe_account_id_test' )
                );
                $refresh_token = base64_decode(
                    self::wtst_get_site_option( 'get', array( 'name' => 'wt_stripe_refresh_token_test' ) )
                );
                $account_id = self::wtst_get_site_option( 'get', array( 'name' => 'wt_stripe_account_id_test' ) );
                $mode_prefix = 'test';
            }
        } else {
            $instance->wtst_clear_cache_for_options(
                array( 'wt_stripe_refresh_token_live', 'wt_stripe_account_id_live' )
            );
            $refresh_token = base64_decode(
                self::wtst_get_site_option( 'get', array( 'name' => 'wt_stripe_refresh_token_live' ) )
            );
            $account_id = self::wtst_get_site_option( 'get', array( 'name' => 'wt_stripe_account_id_live' ) );
            $mode_prefix = 'live';
        }

        if ( ! $refresh_token ) {
            require_once EH_STRIPE_MAIN_PATH . 'includes/class-stripe-oauth.php';
            EH_Stripe_Oauth::wtst_oauth_disconnect( true );
            throw new Exception( 'Refresh token missing' );
        }
        // Make API request
        $response = self::make_refresh_request($access_token_url, $refresh_token, $mode, $test_mode, $account_id, $force);
        // Process and store response
        return self::process_refresh_response($response, $mode_prefix);

    }

    private static function make_refresh_request($url, $refresh_token, $mode, $test_mode, $account_id, $force = false){

        $request_body = wp_json_encode(
            array(
                'refresh_token'  => sanitize_text_field( $refresh_token ),
                'mode'           => $mode,
                'test_mode_type' => $test_mode,
                'account_id'     => sanitize_text_field( $account_id ),
            )
        );

        EH_Stripe_Log::log_update(
            'oauth',
            array(
                'body_type' => gettype($request_body),
                'body_preview' => substr($request_body, 0, 120),
                'url' => $url,
            ),
            'Refresh token API request debug'
        );

        $is_background_context = $force || self::wtst_is_background_context();
        $timeout = $is_background_context
            ? apply_filters("wtst_refresh_token_timeout", 60)
            : apply_filters("wtst_refresh_token_web_timeout", 8);
        $connect_timeout = $is_background_context
            ? apply_filters("wtst_refresh_token_connect_timeout", 25)
            : apply_filters("wtst_refresh_token_web_connect_timeout", 5);

        $response = wp_safe_remote_post(
            $url,
            array(
                'method' => 'POST',
                'body'    => $request_body,
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept'       => 'application/json',
                    'User-Agent'   => self::wt_get_api_user_agent(),
                ),
                /* IMPORTANT FIX */
                'data_format' => 'body',
                'timeout' => $timeout,
                'connect_timeout' => $connect_timeout,
            )
        );

        EH_Stripe_Log::log_update('oauth', $response,'Refresh token API response');

        if ( is_wp_error( $response ) ) {
            // There was an error in the request.
            throw new Exception(
                'WP error - ' . esc_html( $response->get_error_message() )
            );
        }
        return $response;
    }

    /**
     * Process the refresh token response
     */
    private static function process_refresh_response($response, $mode_prefix){

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            throw new Exception( 'Empty OAuth response body' );
        }

        $response_body = json_decode( $body, true );
        if ( ! is_array( $response_body ) ) {
            throw new Exception( 'Invalid OAuth JSON response' );
        }

        $should_disconnect = false;

        if ( isset( $response_body['error'] ) ) {

            if ( $response_body['error'] === 'invalid_grant' ) {
                $should_disconnect = true;
            }

            if (
                isset( $response_body['error_description'] ) &&
                stripos( $response_body['error_description'], 'invalid_grant' ) !== false
            ) {
                $should_disconnect = true;
            }

            if (
                isset( $response_body['error_description'] ) &&
                stripos( $response_body['error_description'], 'refresh_token' ) !== false &&
                stripos( $response_body['error_description'], 'empty' ) !== false
            ) {
                $should_disconnect = true;
            }
        }

        if ( $should_disconnect ) {
            require_once EH_STRIPE_MAIN_PATH . 'includes/class-stripe-oauth.php';
            EH_Stripe_Oauth::wtst_oauth_disconnect( true );
            throw new Exception( 'OAuth refresh token revoked' );
        }

        // reuse parsed response
        $decoded = $response_body;
        EH_Stripe_Log::log_update('oauth', $decoded,'Refresh token API response parsed');

        if ( isset( $decoded['error'] ) ) {
           
            throw new Exception(
                'Error: ' .
                esc_html( $decoded['error'] ?? '' ) . ' ' .
                esc_html( $decoded['error_description'] ?? '' )
            );
        }
        if ( empty( $decoded['access_token'] ) || empty( $decoded['refresh_token'] ) ) {
            throw new Exception( 'Invalid refresh response' );
        }

        $option_updates = array(
            'wtst_oauth_expriy_' . $mode_prefix        => time(),
            'wt_stripe_access_token_' . $mode_prefix  => base64_encode( sanitize_text_field( $decoded['access_token'] ) ),
            'wt_stripe_refresh_token_' . $mode_prefix => base64_encode( sanitize_text_field( $decoded['refresh_token'] ) ),
            'wt_stripe_' . $mode_prefix . '_publishable_key'
                => sanitize_text_field( $decoded['stripe_publishable_key'] ?? '' ),
        );

        foreach ( $option_updates as $name => $value ) {
            self::wtst_get_site_option( 'update', array( 'name' => $name, 'value' => $value ) );
        }

        $refresh_interval = max( MINUTE_IN_SECONDS, absint( apply_filters( 'wtst_oauth_token_refresh_interval', 40 * MINUTE_IN_SECONDS ) ) );

        // Schedule recurring refresh
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('eh_stripe_refresh_oauth_token', null);
        }
        if (!as_next_scheduled_action('eh_stripe_refresh_oauth_token')) {
            as_schedule_recurring_action(
                time() + $refresh_interval,
                $refresh_interval,
                'eh_stripe_refresh_oauth_token'
            );
        }

        return sanitize_text_field( $decoded['access_token'] );

    }

    /**
     * Handle refresh errors consistently
     */
    private static function handle_refresh_error(){

        // Log the error for debugging
        EH_Stripe_Log::log_update('oauth', 'Token refresh failed - user notified if applicable', 'Refresh error handling');

        // Check request contexts safely (functions may not exist in older WP versions)
        $doing_cron = function_exists('wp_doing_cron') && wp_doing_cron();
        $doing_ajax = function_exists('wp_doing_ajax') && wp_doing_ajax();
        $doing_rest = function_exists('wp_doing_rest') && wp_doing_rest();

        // Only add notice in appropriate contexts (frontend, not admin/cron/ajax/rest)
        if (!is_admin() && !$doing_cron && !$doing_ajax && !$doing_rest && function_exists('wc_add_notice')) {
            wc_add_notice(
                __('Please try again after some time', 'payment-gateway-stripe-and-woocommerce-integration'),
                'error'
            );
        }
    }

    /*************  Refresh token function - End ***********/

    /**
     * function to get stripe  token.
     * @param $mode string current payment mode
     * @since 4.0.4
     * 
     */
    // public static function wtst_get_stripe_tokens($mode, $test_mode = false) {
    //     if(!empty($mode)) {
    //         $instance = self::get_instance();
            
    //         if ('test' === $mode) {

    //             $test_mode =  !$test_mode ? EH_Stripe_Token_Handler::get_stripe_test_mode_type() : $test_mode;
    //             if($test_mode === 'sandbox'){
    //                 //Clear cache for the tokens to get the newly updated values
    //                 $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_sandbox', 'wt_stripe_account_id_sandbox', 'wt_stripe_access_token_sandbox', 'wt_stripe_sandbox_publishable_key'));

    //                 return array(
    //                    "wt_stripe_account_id" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_sandbox')),
    //                    "wt_stripe_access_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_sandbox'))),
    //                    "wt_stripe_refresh_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_sandbox'))),
    //                    "wt_stripe_publishable_key" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_sandbox_publishable_key')),
    //                 );
    //             }
    //             //Clear cache for the tokens to get the newly updated values
    //             $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_test', 'wt_stripe_account_id_test', 'wt_stripe_access_token_test', 'wt_stripe_test_publishable_key'));

    //             return array(
    //                "wt_stripe_account_id" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_test')),
    //                "wt_stripe_access_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_test'))),
    //                "wt_stripe_refresh_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_test'))),
    //                "wt_stripe_publishable_key" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_test_publishable_key')),
    //             );
    //         } else {
    //             //Clear cache for the tokens to get the newly updated values
    //             $instance->wtst_clear_cache_for_options(array('wt_stripe_account_id_live', 'wt_stripe_access_token_live', 'wt_stripe_refresh_token_live', 'wt_stripe_live_publishable_key'));

    //             return array(
    //                "wt_stripe_account_id" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_live')),
    //                "wt_stripe_access_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_live'))),
    //                "wt_stripe_refresh_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_live'))),
    //                "wt_stripe_publishable_key" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_live_publishable_key')),
    //             );             
    //         }
    //     }
    // } 

    public static function wtst_get_stripe_tokens( $mode, $test_mode = null ) {

        if ( empty( $mode ) ) {
            return false;
        }

        $instance = self::get_instance();

        if ( 'test' === $mode ) {

            $test_mode = $test_mode ?: EH_Stripe_Token_Handler::get_stripe_test_mode_type();
            $prefix    = ( 'sandbox' === $test_mode ) ? 'sandbox' : 'test';

        } else {
            $prefix = 'live';
        }

        // Clear cache (if required)
        $instance->wtst_clear_cache_for_options( array(
            "wt_stripe_account_id_{$prefix}",
            "wt_stripe_access_token_{$prefix}",
            "wt_stripe_refresh_token_{$prefix}",
            "wt_stripe_{$prefix}_publishable_key",
        ) );

        $account_id     = self::wtst_get_site_option( 'get', array( 'name' => "wt_stripe_account_id_{$prefix}" ) );
        $access_token   = self::wtst_get_site_option( 'get', array( 'name' => "wt_stripe_access_token_{$prefix}" ) );
        $refresh_token  = self::wtst_get_site_option( 'get', array( 'name' => "wt_stripe_refresh_token_{$prefix}" ) );
        $publishable_key = self::wtst_get_site_option( 'get', array( 'name' => "wt_stripe_{$prefix}_publishable_key" ) );

        return array(
            'wt_stripe_account_id'      => $account_id ?: '',
            'wt_stripe_access_token'    => $access_token ? base64_decode( $access_token ) : '',
            'wt_stripe_refresh_token'   => $refresh_token ? base64_decode( $refresh_token ) : '',
            'wt_stripe_publishable_key' => $publishable_key ?: '',
        );
    }


    public static  function wtst_is_valid( $tokens)
    {     
       
        return isset($tokens['wt_stripe_publishable_key'], $tokens['wt_stripe_access_token'], $tokens['wt_stripe_refresh_token'], $tokens['wt_stripe_account_id']);
        
    }  

    /**
     * Clears the WordPress object cache for specific options
     * 
     * @param string|array $option_names Single option name or array of option names to clear cache for
     * @return bool True if cache was cleared, false on failure
     * @since 4.0.4
     */
    public function wtst_clear_cache_for_options($option_names) {
        // Handle both single option name or array of names
        $option_names = (array)$option_names;
        
        if (empty($option_names)) {
            return false;
        }

        foreach ($option_names as $option_name) {
            if (!is_string($option_name) || empty($option_name)) {
                continue;
            }

            // Clear specific option cache
            wp_cache_delete($option_name, 'options');
            // Multisite (network) options
            wp_cache_delete($option_name, 'site-options');

        }

        return;
    }  
    
    /**
     * Helper function to get, update or delete site option or transient
     * @param string $method The operation to perform ('get', 'update', or 'delete')
     * @param array|null $option_data Array containing option data with 'name' and 'value' keys
     * @param array|null $transient_data Array containing transient data with 'name', 'value' and optional 'expiry' keys
     * @return mixed The value of the option/transient for 'get', operation success for 'update'/'delete', or false on failure
     * @since 4.0.4
     */
    public static function wtst_get_site_option($method = 'get', $option_data = null, $transient_data = null) {
        //if multisite is enabled and using same stripe account for all sites then use site wide options and transients
        switch ($method) {
            case 'get':
                if(is_multisite() && apply_filters('wt_stripe_same_account_for_all_sites', false)){
                    if($option_data && isset($option_data['name'])){
                        return get_site_option($option_data['name']);
                    }
                    elseif($transient_data && isset($transient_data['name'])){
                        return get_site_transient($transient_data['name']);
                    }
                }
                else{
                    if($option_data && isset($option_data['name'])){
                        return get_option($option_data['name']);
                    }
                    elseif($transient_data && isset($transient_data['name'])){
                        return get_transient($transient_data['name']);
                    }
                }
                break;

            case 'update':
                if (is_multisite() && apply_filters('wt_stripe_same_account_for_all_sites', false)) {
                    if ($option_data && isset($option_data['name'])) {
                        $updated =update_site_option($option_data['name'], $option_data['value']);
                        if ( !$updated || self::wtst_get_site_option('get', array('name' => $option_data['name'])) !== $option_data['value']) {  //extra check to ensure the value is updated
                            $updated = update_site_option($option_data['name'], $option_data['value']);
                            if(!$updated){
                                EH_Stripe_Log::log_update('oauth', $option_data['name'],'Update site option failed');
                            }
                        } 
                    } elseif ($transient_data && isset($transient_data['name'])) {
                        return set_site_transient(
                            $transient_data['name'],
                            $transient_data['value'],
                            isset($transient_data['expiry']) ? (int)$transient_data['expiry'] : 0
                        );
                    }
                } else {
                    if ($option_data && isset($option_data['name'])) {
                        $updated = update_option($option_data['name'], $option_data['value']);
                        if ( !$updated || self::wtst_get_site_option('get', array('name' => $option_data['name'])) !== $option_data['value']) {  //extra check to ensure the value is updated
                            $updated = update_option($option_data['name'], $option_data['value'], false);
                            if(!$updated){
                                EH_Stripe_Log::log_update('oauth', $option_data['name'],'Update option failed');
                            }  
                        } 
                    } elseif ($transient_data && isset($transient_data['name'])) {
                        return set_transient(
                            $transient_data['name'],
                            $transient_data['value'],
                            isset($transient_data['expiry']) ? (int)$transient_data['expiry'] : 0
                        );
                    }
                }
                break;

            case 'delete':
                if(is_multisite() && apply_filters('wt_stripe_same_account_for_all_sites', false)){
                    if($option_data && isset($option_data['name'])){
                        return delete_site_option($option_data['name']);
                    }
                    elseif($transient_data && isset($transient_data['name'])){
                        return delete_site_transient($transient_data['name']);
                    }
                }
                else{
                    if($option_data && isset($option_data['name'])){
                        return delete_option($option_data['name']);
                    }
                    elseif($transient_data && isset($transient_data['name'])){
                        return delete_transient($transient_data['name']);
                    }
                }
                break;
            
            default:
                return false;
        }
        
        return false;
    }
    
    /**
     * Checks if the OAuth token has expired for the given mode.
     *
     * This function determines whether the OAuth token for the specified mode ('test' or 'live')
     * has expired by comparing the current time with the stored expiry time.
     *
     * @param string $mode The mode for which to check the token expiry. Accepts 'test' or 'live'.
     * @return bool Returns true if the token has expired, false otherwise.
     */
    public static function wtst_get_oauth_expired($mode){
        
        $test_mode = EH_Stripe_Token_Handler::get_stripe_test_mode_type();
        if ( 'test' === $mode ) {
            $option = ( 'sandbox' === $test_mode )
                ? 'wtst_oauth_expriy_sandbox'
                : 'wtst_oauth_expriy_test';
        } else {
            $option = 'wtst_oauth_expriy_live';
        }
        $expiry_time     = self::wtst_get_site_option('get', array('name' => $option ));
        $expiry_interval = max( MINUTE_IN_SECONDS, absint( apply_filters( 'wtst_oauth_token_expiry_interval', 50 * MINUTE_IN_SECONDS ) ) );
        if ($expiry_time && (time() - $expiry_time) <= $expiry_interval) {
            return false;
        }
        else{  
            return true;
        }
    }

    public static function get_temp_dir()
    {
        $uploads_dir = wp_upload_dir();
        $folder_name = 'wt-stripe-oauth-refresh-token-lock';

        // Construct the full path for the new folder
        $folder_path = $uploads_dir['basedir'] . '/' . $folder_name;

        // Check if the folder already exists, if not, attempt to create it
        if (!file_exists($folder_path)) {
            if (!wp_mkdir_p($folder_path)) {
                // Log the error and notify the user if the directory cannot be created
                EH_Stripe_Log::log_update('oauth', 'Failed to create lock folder: ' . $folder_path, 'Directory creation error');
                
                // Check request contexts safely (functions may not exist in older WP versions)
                $doing_cron = function_exists('wp_doing_cron') && wp_doing_cron();
                $doing_ajax = function_exists('wp_doing_ajax') && wp_doing_ajax();
                $doing_rest = function_exists('wp_doing_rest') && wp_doing_rest();

                // Only add notice in appropriate contexts (frontend, not admin/cron/ajax/rest)
                if (!is_admin() && !$doing_cron && !$doing_ajax && !$doing_rest && function_exists('wc_add_notice')) {
                    /* translators: Error message asking user to try again later */
                    wc_add_notice(__('Please try again after some time.', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
                }
            }
        }

        return $folder_path;
    }

    public static function eh_stripe_refresh_oauth_token( $mode = null, $test_mode = null ) {
        EH_Stripe_Token_Handler::wtst_refresh_token(true, $mode, $test_mode);
    }

    /**
     * Get the user agent string for API requests
     * @return string
     */
    public static function wt_get_api_user_agent() {
        $plugin_name = EH_STRIPE_PLUGIN_NAME;
        $plugin_version = EH_STRIPE_VERSION;
        $wp_version = get_bloginfo('version');
        $php_version = PHP_VERSION;
        
        $user_agent =  sprintf(
            '%s/%s (WordPress/%s; PHP/%s; %s)',
            $plugin_name,
            $plugin_version,
            $wp_version,
            $php_version,
            home_url()
        );

        return apply_filters('eh_stripe_api_user_agent', $user_agent);
    }  
    
    /** Themehigh added */
    public static function get_stripe_test_mode_type() {
        $mode = get_option("woocommerce_eh_stripe_test_mode_type");
        return ( $mode === 'sandbox' ) ? 'sandbox' : 'test';
    }
}
