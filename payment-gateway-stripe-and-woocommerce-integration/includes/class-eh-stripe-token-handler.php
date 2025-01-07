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
        // Private constructor to prevent direct creation
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
            \Stripe\Stripe::setApiKey(self::get_stripe_api_key());
            \Stripe\Stripe::setApiVersion(self::wt_get_api_version());
            \Stripe\Stripe::setAppInfo(
                'WordPress payment-gateway-stripe-and-woocommerce-integration', EH_STRIPE_VERSION, 'https://wordpress.org/plugins/payment-gateway-stripe-and-woocommerce-integration/', 'pp_partner_KHip9dhhenLx0S'
            );
            self::$is_initialized = true;
        }
    }


    /**
     * function to get stripe api key.
     */
    private static function get_stripe_api_key(){
        
        $stripe_settings  = get_option( 'woocommerce_eh_stripe_pay_settings' );
        if(!$stripe_settings){
            return false;
        }        
        $mode = isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live';
        if(!empty($mode)){
            if(Eh_Stripe_Admin_Handler::wtst_oauth_compatible($mode)){ 
                if ('test' === $mode) { 
                    //check if transient is not expired then return the access token
                    if('wtst_oauth_expriy' === self::wtst_get_site_option('get', null, array('name' => 'wtst_oauth_expriy_test'))){ 
                        return base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_test')));

                    }
                    else{ 
                        return self::wtst_refresh_token();
                    }

                } else {
                    //check if transient is not expired then return the access token
                    if('wtst_oauth_expriy' === self::wtst_get_site_option('get', null, array('name' => 'wtst_oauth_expriy_live'))){
                        return base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_live')));

                    }
                    else{
                        //if transient is expired then call the refresh token API
                        return self::wtst_refresh_token();
                    }               
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
        return apply_filters('wt_stripe_api_version', '2022-08-01');
    }

    /**
     * Function calling Refresh token API.
     * @return refresh token and access token
     * @since 4.0.4
     * 
     */
    private static function wtst_refresh_token()
    {
        try{
            //To prevent multiple API call
            if("yes" === self::wtst_get_site_option('get', null, array('name' => 'wtst_refresh_token_calling'))){
                return;
            }
            else{
                //Set transient to know that refresh token API is calling now
                self::wtst_get_site_option('update', null, array(
                    'name' => 'wtst_refresh_token_calling',
                    'value' => 'yes'
                ));

            }
            $stripe_settings = get_option("woocommerce_eh_stripe_pay_settings");
            $stripe_settings["eh_stripe_mode"] = (isset($stripe_settings["eh_stripe_mode"]) && !empty($stripe_settings["eh_stripe_mode"])) ? $stripe_settings["eh_stripe_mode"] : 'live';

            $access_token_url = EH_STRIPE_OAUTH_WT_URL . 'get-access-token';

            $instance = self::get_instance();

            if('test' === $stripe_settings["eh_stripe_mode"]){ 
                //Clear cache for the tokens to get the newly updated values
                $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_test', 'wt_stripe_account_id_test'));

                $refresh_token =  base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_test')));
                $account_id = self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_test'));
            }
            else{ 
                //Clear cache for the tokens to get the newly updated values
                $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_live', 'wt_stripe_account_id_live'));

                $refresh_token = base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_live')));
                $account_id = self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_live'));

            }

            if(!$refresh_token){
                throw new Exception('Refresh token not found!');
            }
            // JSON data to send in the POST request body.
            $access_token_req_data = array(
                'refresh_token' => sanitize_text_field($refresh_token),
                'mode' => sanitize_text_field($stripe_settings["eh_stripe_mode"]),
                'account_id' => sanitize_text_field($account_id),

            );

            wc_get_logger()->log('Refresh token API request', print_r($access_token_req_data, true), array( 'source' => 'wt_stripe_oauth' )  );

            // Convert the data to JSON format.
            $access_token_json_data = json_encode( $access_token_req_data );

            // Arguments for the POST request.
            $access_token_args = array(
                'body'    => $access_token_json_data,
                'headers' => array(
                    'Content-Type' => 'application/json', // Tell the server it's JSON.
                ),
                'timeout' => apply_filters("wtst_refresh_token_timeout", 45), // Optional: Set a timeout for the request.
            );

            // Make the POST request.
            $access_token_response = wp_safe_remote_post( $access_token_url, $access_token_args );

            wc_get_logger()->log('Refresh token API response', print_r($access_token_response, true), array( 'source' => 'wt_stripe_oauth' )  );

            // Handle the response.
            if ( is_wp_error( $access_token_response ) ) {
                // There was an error in the request.
                $error_message = $access_token_response->get_error_message();
                throw new Exception('WP error - ' . $error_message);
            } else {
                // Process the response body.
                
                $decoded_response = json_decode(wp_remote_retrieve_body($access_token_response), true);
                wc_get_logger()->log('Refresh token API response parsed', print_r($decoded_response, true), array( 'source' => 'wt_stripe_oauth' )  );

                // Check if response contains any error
                if (isset($decoded_response['error'])) {
                    throw new Exception('Error: ' . (isset($decoded_response['error']) ? $decoded_response['error'] . ' - ' : '') . (isset($decoded_response['error_description']) ? $decoded_response['error_description'] : ''));
                } elseif(isset($decoded_response['access_token']) && isset($decoded_response['refresh_token'])) { 
                    $access_token = sanitize_text_field($decoded_response['access_token']);
                    $refresh_token = (isset($decoded_response['refresh_token']) ? sanitize_text_field($decoded_response['refresh_token'])  : '');
                    $account_id = (isset($decoded_response['stripe_user_id']) ? sanitize_text_field($decoded_response['stripe_user_id'])  : '');
                    $stripe_publishable_key = (isset($decoded_response['stripe_publishable_key']) ? sanitize_text_field($decoded_response['stripe_publishable_key'])  : '');
                    $expiry_time = (isset($decoded_response['transient_expiry']) ? sanitize_text_field($decoded_response['transient_expiry'])  : '');

                
                    if('test' === $stripe_settings["eh_stripe_mode"]){                                    
                        //Set expiry
                        self::wtst_get_site_option('update', null, array(
                            'name' => 'wtst_oauth_expriy_test',
                            'value' => 'wtst_oauth_expriy',
                            'expiry' => $expiry_time
                        ));

                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_account_id_test',
                            'value' => $account_id
                        ));
                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_access_token_test',
                            'value' => base64_encode($access_token)
                        ));
                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_refresh_token_test',
                            'value' => base64_encode($refresh_token)
                        ));
                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_test_publishable_key',
                            'value' => $stripe_publishable_key
                        ));

                    }
                    else{
                        //Set expiry
                        self::wtst_get_site_option('update', null, array(
                            'name' => 'wtst_oauth_expriy_live',
                            'value' => 'wtst_oauth_expriy',
                            'expiry' => $expiry_time
                        ));

                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_account_id_live',
                            'value' => $account_id
                        ));
                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_access_token_live',
                            'value' => base64_encode($access_token)
                        ));
                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_refresh_token_live',
                            'value' => base64_encode($refresh_token)
                        ));
                        self::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_live_publishable_key',
                            'value' => $stripe_publishable_key
                        ));

                    }

                    //delete the transient
                    self::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));
                    return $access_token;

                }
                else{
                    throw new Exception('Unknown response!');
                }       

            }
                
        }
        catch(Exception $e){
            self::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));
            wc_get_logger()->log('Refresh token API', $e->getMessage(), array( 'source' => 'wt_stripe_oauth' )  );

        }
    }

    /**
     * function to get stripe  token.
     * @param $mode string current payment mode
     * @since 4.0.4
     * 
     */
    public static function wtst_get_stripe_tokens($mode) {
        if(!empty($mode)) {
            $instance = self::get_instance();
            if ('test' === $mode) {
                //Clear cache for the tokens to get the newly updated values
                $instance->wtst_clear_cache_for_options(array('wt_stripe_refresh_token_test', 'wt_stripe_account_id_test', 'wt_stripe_access_token_test', 'wt_stripe_test_publishable_key'));

                return array(
                   "wt_stripe_account_id" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_test')),
                   "wt_stripe_access_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_test'))),
                   "wt_stripe_refresh_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_test'))),
                   "wt_stripe_publishable_key" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_test_publishable_key')),
                );
            } else {
                //Clear cache for the tokens to get the newly updated values
                $instance->wtst_clear_cache_for_options(array('wt_stripe_account_id_live', 'wt_stripe_access_token_live', 'wt_stripe_refresh_token_live', 'wt_stripe_live_publishable_key'));

                return array(
                   "wt_stripe_account_id" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_live')),
                   "wt_stripe_access_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_live'))),
                   "wt_stripe_refresh_token" => base64_decode(self::wtst_get_site_option('get', array('name' => 'wt_stripe_refresh_token_live'))),
                   "wt_stripe_publishable_key" => self::wtst_get_site_option('get', array('name' => 'wt_stripe_live_publishable_key')),
                );             
            }
        }
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
                if(is_multisite() && apply_filters('wt_stripe_same_account_for_all_sites', false)){
                    if($option_data && isset($option_data['name'])){
                        return update_site_option($option_data['name'], $option_data['value']);
                    }
                    elseif($transient_data && isset($transient_data['name'])){
                        return set_site_transient(
                            $transient_data['name'], 
                            $transient_data['value'], 
                            isset($transient_data['expiry']) ? (int)$transient_data['expiry'] : 0
                        );
                    }
                }
                else{
                    if($option_data && isset($option_data['name'])){
                        return update_option($option_data['name'], $option_data['value']);
                    }
                    elseif($transient_data && isset($transient_data['name'])){
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
}
