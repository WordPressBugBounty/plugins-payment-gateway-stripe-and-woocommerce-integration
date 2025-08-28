<?php

if (!defined('ABSPATH')) {
    exit;
}  

/**
 * EH_Stripe_General_Settings class.
 *
 * @extends EH_Stripe_Payment
 */
class EH_Stripe_General_Settings extends EH_Stripe_Payment {

    
    public function __construct() {
		$this->id        = 'eh_stripe_pay';
        $this->init_form_fields();

        $this->init_settings();
	}

    public function init_form_fields() {

        $log_file_success = '';
        $log_file_failed = '';
        if ( function_exists( 'wp_hash' ) ) {
            //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            $date_suffix = date( 'Y-m-d', time() );
            
            $handle_success = 'eh_stripe_pay_live';
            $hash_suffix_success = wp_hash( $handle_success );
            $log_file_success = sanitize_file_name( implode( '-', array( $handle_success, $date_suffix, $hash_suffix_success ) ) . '.log' );

            $handle_failed = 'eh_stripe_pay_dead';
            $hash_suffix_failed = wp_hash( $handle_failed );
            $log_file_failed = sanitize_file_name( implode( '-', array( $handle_failed, $date_suffix, $hash_suffix_failed ) ) . '.log' );
        }

        $url = add_query_arg( 'wc-api', 'wt_stripe', trailingslashit( get_home_url() ) );
        //if it's an existing plugin and oAuth authentication is pending yet, show the old tokens
        if(!Eh_Stripe_Admin_Handler::wtst_oauth_compatible() && !Eh_Stripe_Admin_Handler::wtst_new_installation()){ 
            $this->form_fields = array(



                'eh_stripe_mode_switch' => array(
                    'type' => "eh_stripe_mode_switch",
                ),               
              
                'eh_stripe_mode' => array(
                    'title' => __('Transaction mode', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'select',
                    'options' => array(
                        'test' => __('Test mode', 'payment-gateway-stripe-and-woocommerce-integration'),
                        'live' => __('Live mode', 'payment-gateway-stripe-and-woocommerce-integration')
                    ),
                    'class' => 'wc-enhanced-select',
                    'default' => 'live',
                    'desc_tip' => __('Choose test mode to trial run using test API keys. Switch to live mode to begin accepting payments with Stripe using live API keys.', 'payment-gateway-stripe-and-woocommerce-integration')
                ),

                            
                'eh_stripe_test_publishable_key' => array(
                    'title' => __('Test publishable key', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'text',
                    'description' => __('Get the test publishable key from your stripe account.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'placeholder' => 'Test publishable key',
                    'desc_tip' => true
                ),
                'eh_stripe_test_secret_key' => array(
                    'title' => __('Test secret key', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'password',
                    'description' => __('Get the test secret key from your stripe account.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'placeholder' => 'Test secret key',
                    'default'     => '',
                    'desc_tip' => true
                ),
                'eh_stripe_live_publishable_key' => array(
                    'title' => __('Live publishable key', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'text',
                    'description' => __('Get the live publishable key from your stripe account.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'placeholder' => 'Live publishable key',
                    'desc_tip' => true
                ),
                'eh_stripe_live_secret_key' => array(
                    'title' => __('Live secret key', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'password',
                    'description' => __('Get the live secret key from your stripe account.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'placeholder' => 'Live secret key',
                    'default'     => '',
                    'desc_tip' => true
                ),
                'eh_stripe_general' => array(
                  /*'class'=> 'eh-css-class',*/
                  'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'General','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                  'type' => 'title',
                 ),                
                'eh_stripe_overview_title' => array(
                    /*'class'=> 'eh-css-class',*/
                    'type' => 'title',
                ),
                'overview' => array(
                    'title' => __('Stripe overview page', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    'description' => __("Enable a 'Stripe Overview' submenu that mirrors the Stripe dashboard", 'payment-gateway-stripe-and-woocommerce-integration'),
                    'default' => 'no',
                    
                ),
                'eh_stripe_capture' => array(
                    'title' => __('Capture payment immediately', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    'description' => __('Uncaptured payments will expire in 7 days. Payment methods such as Alipay, WeChat Pay, iDEAL, and SEPA do not support manual payment capture.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'default' => 'yes',
                    
                    
                ),
                'eh_stripe_express_button_position' => array(
                    'title' => __('G Pay/Apple Pay button position', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'options' => array(
                        'above' => __('Above', 'payment-gateway-stripe-and-woocommerce-integration'),
                        'below' => __('Below', 'payment-gateway-stripe-and-woocommerce-integration')
                    ),
                    'description' => sprintf(__('Select the position of the Express Payment Buttons: "Above" places them above the order button, while "Below" places them underneath. Applies to all active express payment buttons.', 'payment-gateway-stripe-and-woocommerce-integration')),
                    'default' => 'below',
                    'desc_tip' =>true                    
                ),
                'eh_payment_request_button_height' => array(
                    'title'       => __( 'G Pay/Apple Pay button height', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'type'        => 'text',
                    'description' => __( 'Set the height of the express payment buttons in pixels. Enter a value between 40 and 55 px. The height you select will apply to all active express payment buttons.', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'default'     => '44',
                    'desc_tip'    => true,
                ),                
                
                'eh_stripe_webhhook' => array(
                  /*'class'=> 'eh-css-class',*/
                  'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Webhooks','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                  'type' => 'title',
                  /* translators: %1$s: Opening paragraph tag, %2$s: Line break and bold tag opening, %3$s: Webhook URL, %4$s: Bold tag closing, %5$s: Closing paragraph tag */
                  'description' => sprintf(__('Stripe uses webhooks to notify charge statuses and update order statuses for payment method like SEPA Direct Debit, Klarna, Bacs Direct Debit, BECS Direct Debit, Boleto, OXXO, Stripe Checkout.%1$sTo confgure, add the following webhook endpoint %2$s %3$s %4$s to your <a href="https://dashboard.stripe.com/account/webhooks" target="_blank">Stripe account (Dashboard > Developers > Webhooks > Add endpoint)</a>%5$s', 'payment-gateway-stripe-and-woocommerce-integration'), '<p>', '<br><b>', $url, '</b>', '</p>'),
                 ),
                'eh_stripe_webhook_secret' => array(
                    'title' => __('Webhook secret', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'text',
                    'description' => __('Webhook secret to ensure the requests are coming from Stripe.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'placeholder' => 'Webhook secret',
                    'desc_tip' => true
                ),            
              'eh_stripe_log_title' => array(
                    'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Debug','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                    'type' => 'title',
                   /* 'class'=> 'eh-css-class',*/
                    /* translators: %s: Admin URL for viewing logs */
                    'description' => sprintf(__('Records Stripe payment transactions into WooCommerce status log. <a href="%s" target="_blank"> View log </a>', 'payment-gateway-stripe-and-woocommerce-integration'), admin_url("admin.php?page=wc-status&tab=logs")),
                ),
                'eh_stripe_debug' => array(
                    'title' => __('Debug mode', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc_tip' => __('Enable to for debug mode.', 'payment-gateway-stripe-and-woocommerce-integration')
                ),
                'eh_stripe_logging' => array(
                    'title' => __('Log', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    /* translators: %1$s: Success log file label, %2$s: Success log file path, %3$s: Failure log file label, %4$s: Failure log file path */
                    'description' => sprintf(__( '%1$s: %2$s <br> <br>%3$s: %4$s', 'payment-gateway-stripe-and-woocommerce-integration' ), '<span style="color:green">' . __( 'Success log file','payment-gateway-stripe-and-woocommerce-integration' ) . '</span>', $log_file_success, '<span style="color:red">' . __( 'Failure log file','payment-gateway-stripe-and-woocommerce-integration' ) . '</span>', $log_file_failed),
                    'default' => 'yes',
                    'desc_tip' => __('Enable to record stripe payment transaction in a log file.', 'payment-gateway-stripe-and-woocommerce-integration')
                )
               
            );
        }
        else{
            $this->form_fields = array(
                'eh_stripe_mode_switch' => array(
                    'type' => "eh_stripe_mode_switch",
                ),                
                'eh_stripe_oauth' => array(
                    'type' => "eh_stripe_oauth",
                ),                

                /*'eh_stripe_test_oauth' => array(
                    //'title' => __('Connect to Stripe', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => "eh_stripe_oauth",
                ),

                 'eh_stripe_live_oauth' => array(
                    //'title' => __('Connect to Stripe', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => "eh_stripe_live_oauth",
                ), */ 
                'eh_stripe_mode' => array(
                    'title' => __('Transaction mode', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'select',
                    'options' => array(
                        'test' => __('Test mode', 'payment-gateway-stripe-and-woocommerce-integration'),
                        'live' => __('Live mode', 'payment-gateway-stripe-and-woocommerce-integration')
                    ),
                    'class' => 'wc-enhanced-select',
                    'default' => 'live',
                    'desc_tip' => __('Choose test mode to trial run using test API keys. Switch to live mode to begin accepting payments with Stripe using live API keys.', 'payment-gateway-stripe-and-woocommerce-integration')
                ),

                            
                'eh_stripe_general' => array(
                  /*'class'=> 'eh-css-class',*/
                  'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'General','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                  'type' => 'title',
                 ),
                'eh_stripe_overview_title' => array(
                    /*'class'=> 'eh-css-class',*/
                    'type' => 'title',
                ),
                'overview' => array(
                    'title' => __('Stripe overview page', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    'description' => __('Enable to have a sub menu ‘Stripe Overview’ that replicates Stripe dashboard. Gives provision to manage orders, process partial/full refunds and capture payments.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'default' => 'no',
                    
                ),
                'eh_stripe_capture' => array(
                    'title' => __('Capture payment immediately', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    'description' => __('Disable to capture payments later manually from Stripe dashboard/overview/order details page. Uncaptured payment will expire in 7 days. <br><br>Alipay, WeChat Pay,  iDEAL and SEPA Payment does not allow to manually capture payments later.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'default' => 'yes',
                    
                    
                ),
                'eh_stripe_express_button_position' => array(
                    'title' => __('G Pay/Apple Pay button position', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'options' => array(
                        'above' => __('Above', 'payment-gateway-stripe-and-woocommerce-integration'),
                        'below' => __('Below', 'payment-gateway-stripe-and-woocommerce-integration')
                    ),
                    'description' => sprintf(__('Select the position of the Express Payment Buttons: "Above" places them above the order button, while "Below" places them underneath. Applies to all active express payment buttons.', 'payment-gateway-stripe-and-woocommerce-integration')),
                    'default' => 'below',
                    'desc_tip' =>true                    
                ),
                'eh_payment_request_button_height' => array(
                    'title'       => __( 'G Pay/Apple Pay button height', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'type'        => 'text',
                    'description' => __( 'Set the height of the express payment buttons in pixels. Enter a value between 40 and 55 px. The height you select will apply to all active express payment buttons.', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'default'     => '44',
                    'desc_tip'    => true,
                ),                
                                
                'eh_stripe_webhhook' => array(
                  /*'class'=> 'eh-css-class',*/
                  'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Webhooks','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                  'type' => 'title',
                  /* translators: %1$s: Opening paragraph tag, %2$s: Line break and bold tag opening, %3$s: Webhook URL, %4$s: Bold tag closing, %5$s: Closing paragraph tag */
                  'description' => sprintf(__('Stripe uses webhooks to notify charge statuses and update order statuses for payment method like SEPA Direct Debit, Klarna, Bacs Direct Debit, BECS Direct Debit, Boleto, OXXO, Stripe Checkout.%1$sTo confgure, add the following webhook endpoint %2$s %3$s %4$s to your <a href="https://dashboard.stripe.com/account/webhooks" target="_blank">Stripe account (Dashboard > Developers > Webhooks > Add endpoint)</a>%5$s', 'payment-gateway-stripe-and-woocommerce-integration'), '<p>', '<br><b>', $url, '</b>', '</p>'),
                 ),
                'eh_stripe_webhook_secret' => array(
                    'title' => __('Webhook secret', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'text',
                    'description' => __('Webhook secret to ensure the requests are coming from Stripe.', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'placeholder' => 'Webhook secret',
                    'desc_tip' => true
                ),            
              'eh_stripe_log_title' => array(
                    'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Debug','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                    'type' => 'title',
                   /* 'class'=> 'eh-css-class',*/
                    /* translators: %s: Admin URL for viewing logs */
                    'description' => sprintf(__('Records Stripe payment transactions into WooCommerce status log. <a href="%s" target="_blank"> View log </a>', 'payment-gateway-stripe-and-woocommerce-integration'), admin_url("admin.php?page=wc-status&tab=logs")),
                ),
                'eh_stripe_debug' => array(
                    'title' => __('Debug mode', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc_tip' => __('Enable for debug mode.', 'payment-gateway-stripe-and-woocommerce-integration')
                ),
                'eh_stripe_logging' => array(
                    'title' => __('Log', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                    'type' => 'checkbox',
                    /* translators: %1$s: Success log file label, %2$s: Success log file path, %3$s: Failure log file label, %4$s: Failure log file path */
                    'description' => sprintf(__( '%1$s: %2$s <br> <br>%3$s: %4$s', 'payment-gateway-stripe-and-woocommerce-integration' ), '<span style="color:green">' . __( 'Success log file','payment-gateway-stripe-and-woocommerce-integration' ) . '</span>', $log_file_success, '<span style="color:red">' . __( 'Failure log file','payment-gateway-stripe-and-woocommerce-integration' ) . '</span>', $log_file_failed),
                    'default' => 'yes',
                    'desc_tip' => __('Enable to record stripe payment transaction in a log file.', 'payment-gateway-stripe-and-woocommerce-integration')
                )
               
            );
        }
      
        
    }
    public function admin_options() {
       
        parent::admin_options();
    }

    public function process_admin_options(){
        
        parent::process_admin_options();
    }

    //gets file size of log files in units of bytes
    public function file_size($bytes) {
        $result = 0;
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", ".", strval(round($result, 2))) . " " . $arItem["UNIT"];
                break;
            }
        }
        return $result;
    }

    /**
     * 
     * Function to generate Stripe connect button  html
     * @since 4.0.0
     */
    public function generate_eh_stripe_oauth_html( $key, $value ) { 
        $html = '';
        $settings = get_option("woocommerce_eh_stripe_pay_settings");
         $mode = isset($settings['eh_stripe_mode']) ? $settings['eh_stripe_mode'] : 'live'; 
         if('test' === $mode ){       
            if(true === Eh_Stripe_Admin_Handler::wtst_oauth_compatible() && 
                !empty(EH_Stripe_Token_Handler::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_test')))) {
                $wt_stripe_account_id = EH_Stripe_Token_Handler::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_test'));



               $html = '<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_eh_stripe_test_oauth">Stripe account </label>
                    </th>            
                    <td colspan="2" class="forminp forminp-' . sanitize_text_field($value['type']) . '">
                         <span class="wtst-account-id" style="background_color:#2A3646">' . esc_html($wt_stripe_account_id) . '</span><span class="wtst-connect" style="font-weight: 600;" >' . __("Connected", "payment-gateway-stripe-and-woocommerce-integration") . '</span><span class="wtst-deactivate-oauth"><input type="button" class="button-secondary wtst-btn-red" id="wtst-deactivate-btn" value="' . esc_html__("Disconnect", "payment-gateway-stripe-and-woocommerce-integration") . '" ></span>
                    </td>
                </tr>';

            }
            else{
                $install_link = Eh_Stripe_Admin_Handler::wt_get_install_link($mode);
                $message = __("You are in test mode. Connect to Stripe in live mode to receive payments.", "payment-gateway-stripe-and-woocommerce-integration");
                 $html = '<div class="wtst-notice wtst-notice-warning " ><div style="padding:10px">' . $message .'</div></div><tr valign="top"><td colspan="2" class="forminp forminp-' . sanitize_text_field($value['type']) . '"><div class="wtst-oauth-banner">
                    <div class="wtst-oauth-banner-img" style="background-image: url(' . esc_url(EH_STRIPE_MAIN_URL_PATH."assets/img/oauth-banner.svg" ). ')"></div>
                    <div class="wtst-oauth-banner-container"><div>' .  esc_html__("Connect your Stripe account to start testing", "payment-gateway-stripe-and-woocommerce-integration") .'</div>
                        <div><a target="_blank" class="button-primary wtst-oauth" href="' . esc_url($install_link) .'">' . esc_html__("Connect to Stripe", "payment-gateway-stripe-and-woocommerce-integration") . '</a></div>
                    </div>
                </div></td>
                </tr>';
            }
        }
        //Live mode
        else{
            if(true === Eh_Stripe_Admin_Handler::wtst_oauth_compatible() &&                 
                !empty(EH_Stripe_Token_Handler::wtst_get_site_option('get', array('name' => 'wt_stripe_access_token_live')))) {
                $wt_stripe_account_id = EH_Stripe_Token_Handler::wtst_get_site_option('get', array('name' => 'wt_stripe_account_id_live'));

                $html = '<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_eh_stripe_live_oauth">Stripe account </label>
                    </th>            
                    <td colspan="2" class="forminp forminp-'.sanitize_text_field( $value['type'] ).'">
                         <span class="wtst-account-id" style="background_color:#2A3646">'. esc_html($wt_stripe_account_id) .'</span><span class="wtst-connect" style="font-weight: 600;" >' . __("Connected", "payment-gateway-stripe-and-woocommerce-integration") . '</span><span class="wtst-deactivate-oauth"><input type="button" class="button-secondary wtst-btn-red" id="wtst-deactivate-btn" value="' . esc_html__("Disconnect", "payment-gateway-stripe-and-woocommerce-integration") . '" ></span>
                    </td>
                </tr>';            

            }
            else{
                $install_link = Eh_Stripe_Admin_Handler::wt_get_install_link($mode);

                 $html = '<tr valign="top"><td colspan="2" class="forminp forminp-' . sanitize_text_field($value['type']) . '"><div class="wtst-oauth-banner">
                    <div class="wtst-oauth-banner-img" style="background-image: url(' . esc_url(EH_STRIPE_MAIN_URL_PATH."assets/img/oauth-banner.svg" ). ')"></div>
                    <div class="wtst-oauth-banner-container"><div>' .  esc_html__("You haven’t connected your Stripe account yet. Connect now to start receiving payments", "payment-gateway-stripe-and-woocommerce-integration") .'</div>
                        <div><a target="_blank" class="button-primary wtst-oauth" href="' . esc_url($install_link) .'">' . esc_html__("Connect to Stripe", "payment-gateway-stripe-and-woocommerce-integration") . '</a></div>
                    </div>
                </div></td>
                </tr>';
            }
        }

        return $html;
    }



    /**
     * 
     * Function to generate payment mode switch html
     * @since 4.0.0
     */
    public function generate_eh_stripe_mode_switch_html($key, $value)
    {
        $settings = get_option("woocommerce_eh_stripe_pay_settings");
        $mode = (!empty($settings) && isset($settings['eh_stripe_mode'])) ? $settings['eh_stripe_mode'] : 'live';
        $title = ('test' === $mode) ? "Switch to live mode" : "Switch to test mode";
        $id = ('test' === $mode) ? "eh_test_mode" : "eh_live_mode";
        $visibility_test = ('test' === $mode) ? "block" : "none";
        $visibility_live = ('test' === $mode) ? "none" : "block";
        $current_mode = ('test' === $mode) ? "TEST MODE" : "LIVE MODE";
        $current_mode_icon = ('test' === $mode) ? "wtst-test-mode-icon" : "wtst-live-mode-icon";
       
        $html = sprintf('<div>
            <th scope="row" class="titledesc wtst-settings-title">
                %1$s
                <span class="%2$s">%3$s</span>
            </th>            
            <td scope="row" class="eh-stripe-mode" data-eh-stripe-mode="test"  class="titledesc" style="text-align:right; display:%4$s">
                 <a id="eh_test_mode" href="#" class="button eh_test_mode">%5$s</a>
            </td>            
            <td scope="row" class="eh-stripe-mode" data-eh-stripe-mode="live"  class="titledesc" style="text-align:right; display:%6$s">
                 <a id="eh_live_mode" href="#" class="button eh_live_mode">%7$s</a>
            </td>
        </tr>',  __( 'Connect to Stripe', 'payment-gateway-stripe-and-woocommerce-integration' ), $current_mode_icon,  $current_mode, $visibility_test,  __( 'Switch to live mode', 'payment-gateway-stripe-and-woocommerce-integration' ), $visibility_live, __( 'Switch to test mode', 'payment-gateway-stripe-and-woocommerce-integration' ));

        return $html;
    }



}