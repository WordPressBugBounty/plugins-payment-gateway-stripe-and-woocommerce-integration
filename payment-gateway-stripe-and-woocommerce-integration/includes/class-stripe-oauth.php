<?php

if (!defined('ABSPATH')) {
    exit;
}  

/**
 * EH_Stripe_Oauth class.
 *
 * To received the tokens send by Stripe and save to the databse
 */
#[\AllowDynamicProperties]
class EH_Stripe_Oauth {
    
    
    public function __construct() { 
        //handle the redirection after installing Stripe app
        add_action( 'woocommerce_api_wt_stripe_oauth_update', array( $this, 'wt_stripe_oauth_update' ) );
        add_action('eh_stripe_refresh_oauth_token', array('EH_Stripe_Token_Handler', 'eh_stripe_refresh_oauth_token'));
        add_action('init', array($this, 'check_and_remove_scheduled_actions'));
    }


    /**
     * 
     * Function to retrieve tokens send from Stripe to WebToffee server and save tokens to db
     * @since 4.0.0
     */ 
    public function wt_stripe_oauth_update()
    {  
        $raw_post = file_get_contents( 'php://input' );
        if (!empty($raw_post)) {
            $decoded  = json_decode($raw_post, true);

            if(isset($decoded['nonce']) && isset($decoded['access_token'])){
				$nonce = $decoded['nonce'];
				$user_id = isset($nonce['user_id']) ? absint($nonce['user_id']) : 0;
				$key = isset($nonce ['key']) ? sanitize_text_field($nonce ['key']) : '';
				$key_in_db = get_user_meta($user_id, 'wtst_random_key', true);

                if(isset($key) && isset($key_in_db) && !empty($key) && $key_in_db === $key){
                    $access_token = sanitize_text_field($decoded['access_token']);
                    $refresh_token = (isset($decoded['refresh_token']) ? sanitize_text_field($decoded['refresh_token'])  : '');
                    $account_id = (isset($decoded['account_id']) ? sanitize_text_field($decoded['account_id'])  : '');
                    $stripe_publishable_key = (isset($decoded['stripe_publishable_key']) ? sanitize_text_field($decoded['stripe_publishable_key'])  : '');
    
                    $arr_oauth_tokens = array(
                        'access_token' => $access_token,
                        'refresh_token' => $refresh_token,
                        'account_id' => $account_id,
                        'stripe_publishable_key' => $stripe_publishable_key,
                    );
                    EH_Stripe_Log::log_update('oauth', $arr_oauth_tokens,'Stripe OAuth tokens');
    
                    $stripe_settings = get_option("woocommerce_eh_stripe_pay_settings");
                    $mode = (isset($stripe_settings["eh_stripe_mode"]) ?  $stripe_settings["eh_stripe_mode"] : 'live');
                    
                    if('test' === $mode){
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_account_id_test',
                            'value' => $account_id
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_access_token_test',
                            'value' => base64_encode($access_token)
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_refresh_token_test',
                            'value' => base64_encode($refresh_token)
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_test_publishable_key',
                            'value' => $stripe_publishable_key
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wtst_oauth_expriy_test',
                            'value' => time()
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_oauth_connected_test',
                            'value' => 'yes'
                        ));
    
                        $stripe_settings['eh_stripe_mode'] = 'test';
                        update_option("woocommerce_eh_stripe_pay_settings", $stripe_settings);
                    }
                    else{
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_account_id_live',
                            'value' => $account_id
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_access_token_live',
                            'value' => base64_encode($access_token)
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_refresh_token_live',
                            'value' => base64_encode($refresh_token)
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_live_publishable_key',
                            'value' => $stripe_publishable_key
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wtst_oauth_expriy_live',
                            'value' => time()
                        ));
                        EH_Stripe_Token_Handler::wtst_get_site_option('update', array(
                            'name' => 'wt_stripe_oauth_connected_live',
                            'value' => 'yes'
                        ));
    
                        $stripe_settings['eh_stripe_mode'] = 'live';
                        update_option("woocommerce_eh_stripe_pay_settings", $stripe_settings);
                    
    
                        $oauth_status = 'success';
                        EH_Stripe_Oauth::eh_stripe_schedule_oauth_refresh();
                    }
                    
                }else{
                    EH_Stripe_Log::log_update('oauth', 'Invalid nonce.','Stripe OAuth invalid nonce');
                    $oauth_status = 'failed';
                }
            }
        }
        else{
            EH_Stripe_Log::log_update('oauth', 'empty response','Stripe OAuth empty response');
            $oauth_status = 'failed';
        }

        //Redirect back to settings page
        $setting_link = admin_url(sprintf('admin.php?page=wt_stripe_menu&oauth_status=%s', $oauth_status));

        $response = array(
            'oauth_status' => $oauth_status,
            'redirect_url' => $setting_link,
        );
        EH_Stripe_Log::log_update('oauth', $response,'Stripe OAuth response');
       
        echo wp_json_encode( $response);
        exit;
    }
    /**
     * Function to delete the oAuth tokens
     * 
     * */     
    public static function wtst_oauth_disconnect($force = false)
    {
        
        
        // Check if this is an AJAX request
        if (wp_doing_ajax()) {
            // AJAX request - verify nonce
            $disconnect_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
            if (isset($disconnect_nonce) && wp_verify_nonce($disconnect_nonce, 'eh_stripe_oauth_connect') && current_user_can('manage_woocommerce')) {
                // Valid AJAX request with proper nonce - proceed
                self::perform_oauth_disconnect($force);
            } else {
                // Invalid AJAX request - no nonce or invalid nonce
                wp_die(esc_html__('Security check failed. Please try again.', 'payment-gateway-stripe-and-woocommerce-integration'));
            }
        } elseif ($force) {
            // Non-AJAX request, Check if this is a forced disconnect

            self::perform_oauth_disconnect($force);
        }
    }

    /**
     * Perform the actual OAuth disconnect operations
     */
    private static function perform_oauth_disconnect($force)
    {
        $stripe_settings = get_option("woocommerce_eh_stripe_pay_settings");            
        $mode = (isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live');

        //If mode is passed override 
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $mode = (isset($_REQUEST['mode']) ? sanitize_text_field( wp_unslash($_REQUEST['mode']) ): $mode);
        if('test' === $mode){ 
            //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if(isset($_REQUEST['expire']) && 'access_token' === sanitize_text_field(wp_unslash($_REQUEST['expire'])) ){ 
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wtst_oauth_expriy_test')); 
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));                   
            }
            else{  
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_account_id_test'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_access_token_test')); 
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_refresh_token_test'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_oauth_connected_test'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wtst_oauth_expriy_test'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));   
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_test_publishable_key'));
            }

        }
        else{ 
            //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if(isset($_REQUEST['expire']) && 'access_token' === sanitize_text_field(wp_unslash($_REQUEST['expire'])) ){
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wtst_oauth_expriy_live'));                   
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));
            }
            else{                
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_account_id_live'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_access_token_live'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_refresh_token_live')); 
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_oauth_connected_live'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wtst_oauth_expriy_live'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));
                EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_live_publishable_key'));
                
            }
        }
        if(as_next_scheduled_action('eh_stripe_refresh_oauth_token')){
            as_unschedule_all_actions('eh_stripe_refresh_oauth_token');
        }
        
        // Send email notification for forced disconnects
        if ($force) {
            // Get the admin email
            $admin_email = get_option('admin_email');

            // Set the email subject and message
            $subject = 'Stripe Connection Lost';
            $message = 'Please reconnect to Stripe. Due to some error, the Stripe connection is lost.';

            // Send the email
            wp_mail($admin_email, $subject, $message);
        }
    }
    public static function eh_stripe_schedule_oauth_refresh() {
        if (!as_next_scheduled_action('eh_stripe_refresh_oauth_token')) {
				as_schedule_recurring_action(time(), 50 * MINUTE_IN_SECONDS, 'eh_stripe_refresh_oauth_token');
        }
    }  
  
    public function check_and_remove_scheduled_actions() {
        // Check if the connection is disabled
        $settings = get_option("woocommerce_eh_stripe_pay_settings");
        $mode = isset($settings['eh_stripe_mode']) ? $settings['eh_stripe_mode'] : 'live'; 
        $connection_key = 'wt_stripe_oauth_connected_' . $mode;

        // Check if the connection is established
        if (empty(EH_Stripe_Token_Handler::wtst_get_site_option('get', array('name' => $connection_key)))) {
            if (function_exists('as_unschedule_all_actions')) {
                as_unschedule_all_actions('eh_stripe_refresh_oauth_token', null);
            }
        }else{
            EH_Stripe_Oauth::eh_stripe_schedule_oauth_refresh();
        }
    }

}

new EH_Stripe_Oauth();

