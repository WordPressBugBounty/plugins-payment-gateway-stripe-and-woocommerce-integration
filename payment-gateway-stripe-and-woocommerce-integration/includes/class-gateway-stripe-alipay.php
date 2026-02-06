<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles Alipay payment method.
 *
 * @extends WC_Payment_Gateway
 *
 */
#[\AllowDynamicProperties]
class EH_Alipay_Stripe_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		
		$this->id                 = 'eh_alipay_stripe';
		$this->method_title       = __( 'Alipay', 'payment-gateway-stripe-and-woocommerce-integration' );
		$this->method_description = __( 'Accepts Alipay payments via Stripe.', 'payment-gateway-stripe-and-woocommerce-integration' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
        
		$stripe_settings               = get_option( 'woocommerce_eh_stripe_pay_settings' );
		
		$this->title                   = $this->get_option( 'eh_stripe_alipay_title' );
		$this->description             = $this->get_option( 'eh_stripe_alipay_description' );
		$this->enabled                 = $this->get_option( 'enabled' );
		$this->eh_order_button         = $this->get_option( 'eh_stripe_alipay_order_button');
        $this->order_button_text       = $this->eh_order_button;


		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        add_action( 'woocommerce_api_eh_alipay_stripe_gateway', array( $this, 'eh_alipay_handler' ) );


        // Set stripe API key.
        EH_Stripe_Token_Handler::get_instance()->init_stripe_api();
        
	}


	/**
	 * Initialize form fields in alipay payment settings page.
	 */
	public function init_form_fields() {

		$stripe_settings   = get_option( 'woocommerce_eh_stripe_pay_settings' );
        
		$this->form_fields = array(

            'eh_alipay_desc' => array(
                'type' => 'title',
                /* translators: %1$s: Opening HTML div and list tags, %2$s: Bold tag opening, %3$s: Bold tag closing, %4$s: Bold tag opening, %5$s: Bold tag closing, %6$s: Closing HTML list and div tags, %7$s: Documentation link opening, %8$s: Documentation link closing */
                'description' => sprintf( __('%1$sSupported currencies: %2$s CNY, AUD, CAD, EUR, GBP, HKD, JPY, MYR, NZD, SGD, USD %3$s.%4$sStripe accounts in the following countries can accept the payment: %5$sAustralia, Austria, Belgium, Bulgaria, Canada, Cyprus, Czech Republic, Denmark, Estonia, Finland, France, Germany, Greece, Hong Kong, Hungary, Ireland, Italy, Japan, Latvia, Lithuania, Luxembourg, Malaysia, Malta, Netherlands, New Zealand, Norway, Portugal, Romania, Singapore, Slovakia, Slovenia, Spain, Sweden, Switzerland, United Kingdom, United States%6$s %7$s Read documentation %8$s ', 'payment-gateway-stripe-and-woocommerce-integration'), '<div class="wt_info_div"><ul><li>', '<b>','</b>', '</li><li>', '<b>', '</b></li></ul></div>', '<p><a target="_blank" href="https://www.themehigh.com/docs/set-up-alipay-payment-method-for-a-woocommerce-store/">', '</a></p>'),
            ),
			'eh_stripe_alipay_form_title'   => array(
                'type'        => 'title',
                'class'       => 'eh-css-class',
            ),
			'enabled'                       => array(
				'title'       => __('Alipay','payment-gateway-stripe-and-woocommerce-integration'),
				'label'       => __('Enable','payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'checkbox',
				'default'     => isset($stripe_settings['eh_stripe_alipay']) ? $stripe_settings['eh_stripe_alipay'] : 'no',
				'desc_tip'    => __('Enable to accept Alipay payments through Stripe.','payment-gateway-stripe-and-woocommerce-integration'),
			),
			'eh_stripe_alipay_title'         => array(
				'title'       => __('Title','payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'text',
				'description' =>  __('Input title for the payment gateway displayed at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
				'default'     =>isset($stripe_settings['eh_stripe_alipay_title']) ? $stripe_settings['eh_stripe_alipay_title'] : __('Alipay', 'payment-gateway-stripe-and-woocommerce-integration'),
				'desc_tip'    => true,
			),
			'eh_stripe_alipay_description'     => array(
				'title'       => __('Description','payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'textarea',
				'css'         => 'width:25em',
				'description' => __('Input texts for the payment gateway displayed at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
			 	'default'     =>isset($stripe_settings['eh_stripe_alipay_description']) ? $stripe_settings['eh_stripe_alipay_description'] : __('Secure payment via Alipay.', 'payment-gateway-stripe-and-woocommerce-integration'),
			 	'desc_tip'    => true
			),

			'eh_stripe_alipay_order_button'    => array(
				'title'       => __('Order button text', 'payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'text',
				'description' => __('Input a text that will appear on the order button to place order at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
				'default'     => isset($stripe_settings['eh_stripe_alipay_order_button']) ? $stripe_settings['eh_stripe_alipay_order_button'] :__('Pay via Alipay', 'payment-gateway-stripe-and-woocommerce-integration'),
				'desc_tip'    => true
			)
		);   
    }
    
	/**
	 * Get Alipay icon.
	 *
	 */
	public function get_icon() {
		$style = version_compare(WC()->version, '2.6', '>=') ? 'style="margin-left: 0.3em"' : '';
        $icon = '';
        
		$icon .= '<img src="' . WC_HTTPS::force_https_url(EH_STRIPE_MAIN_URL_PATH . 'assets/img/alipay.png') . '" alt="Alipay" width="52" title="Alipay" ' . $style . ' />';
		return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
	}

	/**
     *Makes gateway available for only alipay supported currencies.
     */
    public function is_available() {

		$stripe_settings   = get_option( 'woocommerce_eh_stripe_pay_settings' );
		if (!empty($stripe_settings) && 'yes' === $this->enabled) {
            $mode = isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live';
    	    $alipay_cur  = array('CNY','AUD', 'CAD', 'EUR', 'GBP', 'HKD', 'JPY','MYR', 'NZD', 'SGD', 'USD');
        
            if (! in_array( get_woocommerce_currency(), $alipay_cur, true) ) {
		
				return false; 
			}
            if(!Eh_Stripe_Admin_Handler::wtst_oauth_compatible($mode)){

    			if (isset($stripe_settings) && 'test' === $stripe_settings['eh_stripe_mode']) {
                    if (!isset($stripe_settings['eh_stripe_test_publishable_key']) || !isset($stripe_settings['eh_stripe_test_secret_key']) || ! $stripe_settings['eh_stripe_test_publishable_key'] || ! $stripe_settings['eh_stripe_test_secret_key']) {
                        return false;
                    }
                } else {
                    if (!isset($stripe_settings['eh_stripe_live_secret_key']) || !isset($stripe_settings['eh_stripe_live_publishable_key']) || !$stripe_settings['eh_stripe_live_secret_key'] || !$stripe_settings['eh_stripe_live_publishable_key']) {
                        return false;
                    }
                }

    			return true;
            }
            else{

                $tokens = EH_Stripe_Token_Handler::wtst_get_stripe_tokens($mode); 
                return  EH_Stripe_Token_Handler::wtst_is_valid($tokens);
            }                
	    }
        return false; 
	}
	

    /**
     * Outputs scripts used for stripe payment.
     */
    public function payment_scripts() {

        if(!$this->is_available()){
            return false;
        }

        if ( (is_checkout()  && !is_order_received_page())) {
            $stripe_settings   = get_option( 'woocommerce_eh_stripe_pay_settings' );
            $mode = isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live';
            //phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion, WordPress.WP.EnqueuedResourceParameters.NotInFooter            
            wp_register_script('stripe_v3_js', 'https://js.stripe.com/v3/');

           wp_enqueue_script('eh_alipay', plugins_url('assets/js/eh-alipay.js', EH_STRIPE_MAIN_FILE), array('stripe_v3_js','jquery'),EH_STRIPE_VERSION, true);

            if(Eh_Stripe_Admin_Handler::wtst_oauth_compatible($mode)){
                $tokens = EH_Stripe_Token_Handler::wtst_get_stripe_tokens($mode);  
                $public_key = $tokens['wt_stripe_publishable_key'];
            }
            else{
                if (isset($stripe_settings['eh_stripe_mode']) && 'test' === $stripe_settings['eh_stripe_mode']) {
                    $public_key = $stripe_settings['eh_stripe_test_publishable_key'];
                } else {
                    $public_key = $stripe_settings['eh_stripe_live_publishable_key'];
                }
            }

            $stripe_params['key'] =	$public_key;
            //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
            $stripe_params['is_checkout']  = ( is_checkout() && empty( $_GET['pay_for_order'] ) ) ? 'yes' : 'no';
            $stripe_params['inline_postalcode'] = apply_filters('hide_inline_postal_code', true);

            // If we're on the pay page we need to pass stripe.js the address of the order.
            //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
            if ( isset( $_GET['pay_for_order'] ) && 'true' === $_GET['pay_for_order'] ) {

                $order     = wc_get_order( absint( get_query_var( 'order-pay' ) ) );

                if ( is_a( $order, 'WC_Order' ) ) {
                    $stripe_params['billing_first_name'] = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : $order->billing_first_name;
                    $stripe_params['billing_last_name']  = method_exists($order, 'get_billing_last_name')  ? $order->get_billing_last_name()  : $order->billing_last_name;
                    $stripe_params['billing_address_1']  = method_exists($order, 'get_billing_address_1')  ? $order->get_billing_address_1()  : $order->billing_address_1;
                    $stripe_params['billing_address_2']  = method_exists($order, 'get_billing_address_2')  ? $order->get_billing_address_2()  : $order->billing_address_2;
                    $stripe_params['billing_state']      = method_exists($order, 'get_billing_state')      ? $order->get_billing_state()      : $order->billing_state;
                    $stripe_params['billing_city']       = method_exists($order, 'get_billing_city')       ? $order->get_billing_city()       : $order->billing_city;
                    $stripe_params['billing_postcode']   = method_exists($order, 'get_billing_postcode')   ? $order->get_billing_postcode()   : $order->billing_postcode;
                    $stripe_params['billing_country']    = method_exists($order, 'get_billing_country')    ? $order->get_billing_country()    : $order->billing_country;
                }                       
            }
            $stripe_params['version'] = EH_Stripe_Token_Handler::wt_get_api_version();  
            wp_localize_script('eh_alipay', 'eh_alipay_val', apply_filters('eh_alipay_val', $stripe_params));
        }
    }
	
    /**
     *Gets client details.
     */
    public function get_clients_details() {
        return array(
            'IP' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            'Agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'Referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : ''
        );
    }

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		$user        = wp_get_current_user();
		$total       = WC()->cart->total;
		$description = $this->get_description();
		
		echo '<div class="status-box">';
        
        if ($description) {
            echo wp_kses_post(apply_filters('eh_stripe_desc', wpautop(wp_kses_post("<span>" . $description . "</span>"))));
        }
        echo "</div>";
	}

	
	/**
	 * Builds the return URL from redirects.
	 *
	 */
	public function get_stripe_return_url( $order = null, $id = null ) {
		if ( is_object( $order ) ) {
			if ( empty( $id ) ) {
				$id = uniqid();
			}
			
			$order_id  = version_compare(WC_VERSION, '2.7.0', '<') ? $order->id : $order->get_id();

			$args = array(
				'utm_nooverride' => '1',
				'order_id'       => $order_id,
			);

			return esc_url_raw( add_query_arg( $args, $this->get_return_url( $order ) ) );
		}

		return esc_url_raw( add_query_arg( array( 'utm_nooverride' => '1' ), $this->get_return_url() ) );
	}


	    /**
     * Retreve the payment intent detials from order
     * @since 3.3.0
     */
    public function get_payment_intent_from_order( $order ) {
        $order_id = version_compare(WC_VERSION, '2.7.0', '<') ? $order->id : $order->get_id();

        if ( version_compare(WC_VERSION, '2.7.0', '<') ) {
            $intent_id = get_post_meta( $order_id, '_eh_stripe_payment_intent', true );
        } else {
            $intent_id = $order->get_meta( '_eh_stripe_payment_intent' );
        }

        if ( ! $intent_id ) {
            return false;
        }

        return \Stripe\PaymentIntent::retrieve( $intent_id );
    }

   
     /**
     *Gets details for stripe charge creation.
     */
    public function get_charge_details( $wc_order, $payment_method, $client, $currency, $amount) {
        $product_name = array();
        $order_id = $wc_order->get_id();
        foreach ($wc_order->get_items() as $item) {
            array_push($product_name, $item['name']);
        }

        $charge = array(
            'payment_method_types' => array('alipay'),
            'amount' => $amount,
            'payment_method' => $payment_method,
            'currency' => $currency,
            'metadata' => array(
                'order_id' => $wc_order->get_id(),
                'Total Tax' => $wc_order->get_total_tax(),
                'Total Shipping' => $wc_order->get_total_shipping(),
                'Customer IP' => $client['IP'],
                'Agent' => $client['Agent'],
                'Referer' => $client['Referer'],
                'WP customer #' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->user_id : $wc_order->get_user_id(),
                'Billing Email' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->billing_email : $wc_order->get_billing_email()
            ),
            'description' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' Order #' . $wc_order->get_order_number(),
        );
        
        if( $this->get_option('eh_stripe_statement_descriptor') ) {
            
            $statement_descriptor = $this->get_option('eh_stripe_statement_descriptor');
            $statement_descriptor =  ( strlen( $statement_descriptor < 22 ) ) ? $statement_descriptor : substr( $statement_descriptor ,0,22);            
            $charge['statement_descriptor'] = $statement_descriptor;
        }

        $product_list = implode(' | ', $product_name);

        $charge['metadata']['Products'] = substr($product_list, 0, 499);
        
        
        $show_items_details = apply_filters('eh_stripe_show_items_in_payment_description', false);
                
        if($show_items_details){
            
            $charge['description']=$charge['metadata']['Products'] .' '.wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' Order #' . $wc_order->get_order_number();
        }
        $charge['confirm'] =  true ;
        $charge['return_url'] =  add_query_arg('order_id', $order_id, WC()->api_request_url('EH_Alipay_Stripe_Gateway')) ;//$this->get_return_url($order); 

        if ($this->eh_stripe_email_receipt) {
            $charge['receipt_email'] = (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->billing_email : $wc_order->get_billing_email();
        }
        if (!is_checkout_pay_page()) {
            $charge['shipping'] = array(
                'address' => array(
                    'line1' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_address_1 : $wc_order->get_shipping_address_1(),
                    'line2' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_address_2 : $wc_order->get_shipping_address_2(),
                    'city' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_city : $wc_order->get_shipping_city(),
                    'state' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_state : $wc_order->get_shipping_state(),
                    'country' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_country : $wc_order->get_shipping_country(),
                    'postal_code' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_postcode : $wc_order->get_shipping_postcode()
                ),
                'name' => ((version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_first_name : $wc_order->get_shipping_first_name()) . ' ' . ((version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->shipping_last_name : $wc_order->get_shipping_last_name()),
                'phone' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->billing_phone : $wc_order->get_billing_phone(),
            );
        }
        
       return apply_filters('eh_alipay_payment_intent_args', $charge);
    }

    
    /**
     * Save intent details with order
     * @since 3.3.0
     */
    public function save_payment_intent_to_order( $order, $intent ) {
        $order_id = (version_compare(WC()->version, '2.7.0', '<')) ? $order->id : $order->get_id();
        
        if ( version_compare(WC_VERSION, '2.7.0', '<') ) {
            update_post_meta( $order_id, '_eh_stripe_payment_intent', $intent->id );
        } else {
            $order->update_meta_data( '_eh_stripe_payment_intent', $intent->id );
        }

        if ( is_callable( array( $order, 'save' ) ) ) {
            $order->save();
        }
    }
    
	/**
	 * Process the payment
	 *
	 */
	public function process_payment( $order_id, $retry = true, $force_save_save = false ) {
		$wc_order = wc_get_order( $order_id );

        //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing	
        $payment_method = isset($_POST['eh_alipay_token']) ? sanitize_text_field(wp_unslash($_POST['eh_alipay_token'])) : '';

		$currency =  $wc_order->get_currency();
        $amount = EH_Stripe_Payment::get_stripe_amount(((version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->order_total : $wc_order->get_total())) ;
		
        $intent = $this->get_payment_intent_from_order( $wc_order );
       
        $client = $this->get_clients_details();

        $payment_intent_args  = $this->get_charge_details($wc_order, $payment_method, $client, $currency, $amount);

        if(! empty($intent)){

            if ( $intent->status === 'succeeded' ) {
                wc_add_notice(__('An error has occurred internally, due to which you are not redirected to the order received page. Please contact support for more assistance.', 'payment-gateway-stripe-and-woocommerce-integration'), $notice_type = 'error');
                wp_redirect(wc_get_checkout_url());
            }else{
                $intent = \Stripe\PaymentIntent::create( $payment_intent_args , array(
                    'idempotency_key' => $wc_order->get_order_key() . '-' . $payment_method
                ));
            }
        }else{
            $intent = \Stripe\PaymentIntent::create( $payment_intent_args , array(
                'idempotency_key' => $wc_order->get_order_key() . '-' . $payment_method
            ));
        }
            
        $this->save_payment_intent_to_order( $wc_order, $intent );
        
        EH_Helper_Class::wt_stripe_order_db_operations($order_id, $wc_order, 'add', '_eh_stripe_payment_intent', $intent->id, false); 

        if ($intent->status == 'requires_action' &&
            $intent->next_action->type == 'alipay_handle_redirect') {
            # Tell the client to handle the action

            return array(
               'result'        => 'success',
                'redirect'      => esc_url_raw($intent->next_action->alipay_handle_redirect->url),

            );
            
        }    
         else {
              return $this->eh_process_payment_response( $intent, $wc_order );
         }
		
		$order->save();
			
		
		if ( ! empty( $response->error ) ) {
			$order->add_order_note( $response->error->message );
			$order->save();
		}
			
		return array(
			'result'   => 'success',
		'redirect'     => esc_url_raw( $response->redirect->url ),
		);
	}

	/**
     *Process alipay refund process.
	 */
	public function process_refund($order_id, $amount = NULL, $reason = '') {
    
		$client = $this->get_clients_details();
		if ($amount > 0) {
			
            $data = EH_Helper_Class::wt_stripe_order_db_operations($order_id, null, 'get', '_eh_stripe_payment_charge', null, true);

            $status = $data['captured'];

			if ('Captured' === $status) {
				$charge_id = $data['id'];
				$currency = $data['currency'];
				$total_amount = $data['amount'];
						
				$wc_order = new WC_Order($order_id);
				$div = $amount * ($total_amount / ((version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->order_total : $wc_order->get_total()));
				$refund_params = array(
					'amount' => EH_Stripe_Payment::get_stripe_amount($div, $currency),
					'reason' => 'requested_by_customer',
					'metadata' => array(
						'order_id' => $wc_order->get_id(),
						'Total Tax' => $wc_order->get_total_tax(),
						'Total Shipping' => (version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->get_total_shipping() : $wc_order->get_shipping_total(),
						'Customer IP' => $client['IP'],
						'Agent' => $client['Agent'],
						'Referer' => $client['Referer'],
						'Reason for Refund' => $reason
					)
				);
						
				try {
					$charge_response = \Stripe\Charge::retrieve($charge_id);
					$refund_response = $charge_response->refunds->create($refund_params);
					if ($refund_response) {
										
                        //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						$refund_time = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600);
						$obj = new EH_Stripe_Payment();
						$data = $obj->make_refund_params($refund_response, $amount, ((version_compare(WC()->version, '2.7.0', '<')) ? $wc_order->order_currency : $wc_order->get_currency()), $order_id);
						
                        EH_Helper_Class::wt_stripe_order_db_operations($order_id, $wc_order, 'add', '_eh_stripe_payment_refund', $data, false);

						/* translators: %1$s: Reason, %2$s: Amount with currency symbol, %3$s: Status, %4$s: Refund time, %5$s: Transaction ID (optional) */
						$wc_order->add_order_note(sprintf(__('Reason : %1$s.<br>Amount : %2$s.<br>Status : %3$s [ %4$s ] %5$s', 'payment-gateway-stripe-and-woocommerce-integration'), $reason, get_woocommerce_currency_symbol() . $amount, esc_html($data['status']), $refund_time, (is_null($data['transaction_id']) ? '' : '<br>' . __('Transaction ID : ', 'payment-gateway-stripe-and-woocommerce-integration') . $data['transaction_id'])));
						EH_Stripe_Log::log_update('live', $data, get_bloginfo('blogname') . ' - Refund - Order #' . $wc_order->get_order_number());

                        if('succeeded' !== $data['status']){

                            //Save a meta to check and process the refund status through webhook, if currenty refund is on pending status
                            $wc_order->update_meta_data( 'auto_process_refund_status', 'yes' );
                            $wc_order->save();
                            return new WP_Error( 'pending_refund', 'Refund is in pending status!', array( 'additional_info' => 'Refund status will be updated automatically!' ) );
                        }
                        else{                        
						return true;
                        }
					} else {
						EH_Stripe_Log::log_update('dead', $data, get_bloginfo('blogname') . ' - Refund Error - Order #' . $wc_order->get_order_number());
						/* translators: %1$s: Reason, %2$s: Amount with currency symbol */
						$wc_order->add_order_note(sprintf(__('Reason : %1$s.<br>Amount : %2$s.<br>Status : Failed', 'payment-gateway-stripe-and-woocommerce-integration'), $reason, get_woocommerce_currency_symbol() . $amount));
						return new WP_Error('error', $data->message);
					}
				} catch (Exception $error) {
					$oops = $error->getJsonBody();
					EH_Stripe_Log::log_update('dead', $oops['error'], get_bloginfo('blogname') . ' - Refund Error - Order #' . $wc_order->get_order_number());
					/* translators: %1$s: Reason, %2$s: Amount with currency symbol, %3$s: Error message */
					$wc_order->add_order_note(sprintf(__('Reason : %1$s.<br>Amount : %2$s.<br>Status : %3$s', 'payment-gateway-stripe-and-woocommerce-integration'), $reason, get_woocommerce_currency_symbol() . $amount, $oops['error']['message']));
					return new WP_Error('error', $oops['error']['message']);
				}
			} else {
				return new WP_Error('error', __('Uncaptured Amount cannot be refunded', 'payment-gateway-stripe-and-woocommerce-integration'));
			}
		} else {
			return false;
	    }
    }


    public function eh_alipay_handler(){ 
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing	
    	if (isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id'])) {
            //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing	
    		$order_id = sanitize_text_field(wp_unslash($_REQUEST['order_id']));
			$order = wc_get_order( $order_id );

    	}
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing	
    	if (isset($_REQUEST['payment_intent']) && !empty($_REQUEST['payment_intent'])) {
            //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing	
    		$intent_id = sanitize_text_field(wp_unslash($_REQUEST['payment_intent']));	
    		$intent_result = \Stripe\PaymentIntent::retrieve( $intent_id );
    		if (!empty($intent_result)) {
    			$this->eh_process_payment_response($intent_result, $order);
    			 $redirect_url = $this->get_return_url( $order );
    			wp_safe_redirect($redirect_url);
    		}
    		else{
    			if ($order) {
    			$order->update_status( 'failed', __( 'Stripe payment failed', 'payment-gateway-stripe-and-woocommerce-integration' ) );
	    		}
				
				wc_add_notice( __( 'Unable to process this payment.', 'payment-gateway-stripe-and-woocommerce-integration' ), 'error' );
				wp_safe_redirect( wc_get_checkout_url() );
    		}
    	}
    	else{
    		if ($order) {
    			$order->update_status( 'failed', __( 'Stripe payment failed', 'payment-gateway-stripe-and-woocommerce-integration' ) );
    		}
			
			wc_add_notice( __( 'Unable to process this payment.', 'payment-gateway-stripe-and-woocommerce-integration' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
    	 }
    }


	/**
	 * Store extra meta data for an order and adds order notes for orders.
	 */
	public function eh_process_payment_response( $response, $order = null ) {
		
		if (!$order) {
			$order_id = $response->metadata->order_id;
			$order = wc_get_order( $order_id );
		}
		$order_id = $order->get_id();
        
        // Stores charge data.
        $obj1 = new EH_Stripe_Payment();
		$charge_response = end($response->charges->data);
		if (!empty($charge_response)) {
			$charge_param = $obj1->make_charge_params($charge_response , $order_id);
            EH_Helper_Class::wt_stripe_order_db_operations($order_id, $order, 'add', '_eh_stripe_payment_charge', $charge_param, false);            

			
			//$order_id  = version_compare(WC_VERSION, '2.7.0', '<') ? $order->id : $order->get_id();
			$captured = ( isset( $charge_response->captured )) ? 'Captured' : 'Uncaptured';

			// Stores charge capture data.
			if ( version_compare(WC_VERSION, '2.7.0', '<') ) {
	            update_post_meta( $order_id, '_eh_alipay_charge_captured', $captured );
	        } else {
	            $order->update_meta_data( '_eh_alipay_charge_captured', $captured );
			}
		}
		
        //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$order_time = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600); 

		if ($response->status == 'requires_payment_method') {
			$order->update_status( 'failed', __( 'Stripe payment failed.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
		}
		elseif($response->status == 'succeeded'){
			if ( 'Captured' === $captured ) {
			
				if ( 'pending' === $charge_response->status && ( $order->get_transaction_id( $charge_response->id ) !== $charge_response->id || $order->get_status() !== 'on-hold' ) ) {
					$order_stock_reduced = $order->get_meta( '_order_stock_reduced', true );

					if ( ! $order_stock_reduced ) {
					    wc_reduce_stock_levels( $order_id );
					}

					$order->set_transaction_id( $charge_response->id );
					$order->update_status( 'on-hold');
					/* translators: %1$s: Payment status, %2$s: Order time, %3$s: Source type, %4$s: Charge status, %5$s: Transaction ID (optional) */
					$order->add_order_note( sprintf( __('Payment Status : %1$s [ %2$s ] . Source : %3$s . Charge Status : %4$s%5$s', 'payment-gateway-stripe-and-woocommerce-integration'), ucfirst($charge_response->status), $order_time, $charge_response->source->type, $captured, (is_null($charge_response->balance_transaction) ? '' :'. Transaction ID : ' . $charge_response->balance_transaction) ) );
				}
				
				if ( 'succeeded' === $charge_response->status && $order->needs_payment() ) {
					$order->payment_complete( $charge_response->id );

					/* translators: %1$s: Payment status, %2$s: Order time, %3$s: Source type, %4$s: Charge status, %5$s: Transaction ID (optional) */
					$order->add_order_note( sprintf( __('Payment Status : %1$s [ %2$s ] . Source : %3$s . Charge Status : %4$s%5$s', 'payment-gateway-stripe-and-woocommerce-integration'), ucfirst($charge_response->status), $order_time, $charge_response->payment_method_details->type, $captured, (is_null($charge_response->balance_transaction) ? '' :'. Transaction ID : ' . $charge_response->balance_transaction) ) );
				}

			} else {
				 $order->set_transaction_id( $charge_response->id );

				if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
				    wc_reduce_stock_levels( $order_id );
				}

				/* translators: %s: Charge ID */
				$order->update_status( 'on-hold', sprintf( __( 'Stripe alipay order meta (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'payment-gateway-stripe-and-woocommerce-integration' ), $charge_response->id) );
			}
		}
		return $charge_response;
		
	}
}