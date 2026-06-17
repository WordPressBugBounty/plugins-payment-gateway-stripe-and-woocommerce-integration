<?php

if (!defined('ABSPATH')) {
    exit;
}  

/**
 * EH_Stripe_Multibanco_Pay class.
 *
 * @extends EH_Stripe_Payment
 */
#[\AllowDynamicProperties]
class EH_Multibanco extends WC_Payment_Gateway {

    /**
     * Constructor
     */
    public function __construct() {
        
        $this->id                 = 'eh_multibanco_stripe';
        $this->method_title       = __( 'Multibanco', 'payment-gateway-stripe-and-woocommerce-integration' );

        $url = add_query_arg( 'wc-api', 'wt_stripe', trailingslashit( get_home_url() ) );
        /* translators: %1$s: Opening anchor tag with preview link, %2$s: Closing anchor tag */
        $this->method_description = sprintf( __( 'Stripe users in Europe and the United States can accept Multibanco payments from customers in Portugal. %1$s[Preview]%2$s', 'payment-gateway-stripe-and-woocommerce-integration' ), '<a class="thickbox" href="'.EH_STRIPE_MAIN_URL_PATH . 'assets/img/multibanco-preview.png?TB_iframe=true&width=100&height=100">', '</a>' );
        $this->supports = array(
            'products',
            'refunds',
        );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        
        $stripe_settings               = get_option( 'woocommerce_eh_stripe_pay_settings' );
        
        $this->title                   = $this->get_option( 'eh_stripe_multibanco_title' );
        $this->description             = $this->get_option( 'eh_stripe_multibanco_description' );
        $this->enabled                 = $this->get_option( 'enabled' );
        $this->eh_order_button         = $this->get_option( 'eh_stripe_multibanco_order_button');
        $this->order_button_text       = $this->eh_order_button;

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // Set stripe API key.
        EH_Stripe_Token_Handler::get_instance()->init_stripe_api();

        // Hooks
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        //add_filter( 'woocommerce_payment_successful_result', array( $this, 'modify_successful_payment_result' ), 99999, 2 );
       add_action( 'woocommerce_api_eh_multibanco', array( $this, 'eh_multibanco_callback_handler' ) );
       //add_action('woocommerce_thankyou_eh_multibanco_stripe', array($this, 'show_multibanco_voucher'));
        add_action('woocommerce_order_details_after_order_table', array($this, 'show_multibanco_voucher_myaccount'));
    }



    /**
     * Initialize form fields in multibanco payment settings page.
     */
    public function init_form_fields() {

        $stripe_settings   = get_option( 'woocommerce_eh_stripe_pay_settings' );
        
        $this->form_fields = array(
            'eh_multibanco_desc' => array(
                'type' => 'title',
                /* translators: %1$s: Opening HTML div and list tags, %2$s: Bold tag opening, %3$s: Bold tag closing, %4$s: Bold tag opening, %5$s: Bold tag closing, %6$s: Closing HTML list and div tags */
                'description' => sprintf(__('%1$sSupported currencies: %2$sEUR%3$sStripe accounts in the following countries can accept the payment: %4$sEurope, United States%5$s', 'payment-gateway-stripe-and-woocommerce-integration'), '<div class="wt_info_div"><ul><li>', '<b>', '</b></li><li>', '<b>', '</b></li></ul></div>'),
            ),

            'eh_stripe_multibanco_form_title'   => array(
                'type'        => 'title',
                'class'       => 'eh-css-class',
            ),
            'enabled'                       => array(
                'title'       => __('Multibanco Pay','payment-gateway-stripe-and-woocommerce-integration'),
                'label'       => __('Enable','payment-gateway-stripe-and-woocommerce-integration'),
                'type'        => 'checkbox',
                'default'     => isset($stripe_settings['eh_stripe_multibanco']) ? $stripe_settings['eh_stripe_multibanco'] : 'no',
                'desc_tip'    => __('Enables customers in the Single Euro Payments Area (Multibanco) to pay by providing their bank account details.','payment-gateway-stripe-and-woocommerce-integration'),
            ),
            'eh_stripe_multibanco_title'         => array(
                'title'       => __('Title','payment-gateway-stripe-and-woocommerce-integration'),
                'type'        => 'text',
                'description' =>  __('Input title for the payment gateway displayed at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
                'default'     =>isset($stripe_settings['eh_stripe_multibanco_title']) ? $stripe_settings['eh_stripe_multibanco_title'] : __('Multibanco Pay', 'payment-gateway-stripe-and-woocommerce-integration'),
                'desc_tip'    => true,
            ),
            'eh_stripe_multibanco_description'     => array(
                'title'       => __('Description','payment-gateway-stripe-and-woocommerce-integration'),
                'type'        => 'textarea',
                'css'         => 'width:25em',
                'description' => __('Input texts for the payment gateway displayed at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
                'default'     =>isset($stripe_settings['eh_stripe_multibanco_description']) ? $stripe_settings['eh_stripe_multibanco_description'] : __('Secure payment via Multibanco.', 'payment-gateway-stripe-and-woocommerce-integration'),
                'desc_tip'    => true
            ),

            'eh_stripe_multibanco_order_button'    => array(
                'title'       => __('Order button text', 'payment-gateway-stripe-and-woocommerce-integration'),
                'type'        => 'text',
                'description' => __('Input a text that will appear on the order button to place order at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
                'default'     => isset($stripe_settings['eh_stripe_multibanco_order_button']) ? $stripe_settings['eh_stripe_multibanco_order_button'] :__('Pay via Multibanco', 'payment-gateway-stripe-and-woocommerce-integration'),
                'desc_tip'    => true
            )
        );   
    }
 
     
    public function get_icon() {
        $style = version_compare(WC()->version, '2.6', '>=') ? 'style="margin-left: 0.3em"' : '';
        $icon = '';
        
       $icon .= '<img src="' . esc_url(WC_HTTPS::force_https_url(EH_STRIPE_MAIN_URL_PATH . 'assets/img/multibanco.svg')) . '" alt="Multibanco" width="52" title="Multibanco" ' . $style . ' />';
        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }
   
    /**
     *Makes gateway available 
     */
    public function is_available() {

        $stripe_settings   = get_option( 'woocommerce_eh_stripe_pay_settings' );

        if (!empty($stripe_settings) && 'yes' === $this->enabled) {
           $mode = isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live';

            if(!Eh_Stripe_Admin_Handler::wtst_oauth_compatible()){
                if (isset($stripe_settings['eh_stripe_mode']) && 'test' === $stripe_settings['eh_stripe_mode']) {
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
        $stripe_settings   = get_option( 'woocommerce_eh_stripe_pay_settings' );
        if ( (is_checkout()  && !is_order_received_page())) {
            //phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion, WordPress.WP.EnqueuedResourceParameters.NotInFooter  
            wp_register_script('stripe_v3_js','https://js.stripe.com/basil/stripe.js');          
            //wp_register_script('stripe_v3_js', 'https://js.stripe.com/v3/');
        }
    }

    /**
     *override woocommerce payment form.
     */
    public function payment_fields() {
        $user = wp_get_current_user();
        if ($user->ID) {
            $user_email = get_user_meta($user->ID, 'billing_email', true);
            $user_email = $user_email ? $user_email : $user->user_email;
        } else {
            $user_email = '';
        }
         $description = $this->get_description();           

        echo '<div class="status-box">';

        if ($description) {
            echo wp_kses_post(apply_filters('eh_multibanco_desc', wpautop(wp_kses_post("<span>" . $description . "</span>"))));
        }
        echo "</div>";
        $pay_button_text = __('Pay', 'payment-gateway-stripe-and-woocommerce-integration');
        if (is_checkout_pay_page()) {
            $order_id = get_query_var('order-pay');
            $order = wc_get_order($order_id);
            $email = (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_email : $order->get_billing_email();
            echo '<div
                id="eh-multibanco-pay-data"
                data-panel-label="' . esc_attr($pay_button_text) . '"
                data-email="' . esc_attr(($email !== '') ? $email : get_bloginfo('name', 'display')) . '"
                data-amount="' . esc_attr(EH_Stripe_Payment::get_stripe_amount(((version_compare(WC()->version, '2.7.0', '<')) ? $order->order_total : $order->get_total()))) . '"
                data-name="' . esc_attr(sprintf(get_bloginfo('name', 'display'))) . '"
                data-currency="' . esc_attr(((version_compare(WC()->version, '2.7.0', '<')) ? $order->order_currency : $order->get_currency())) . '">';

           echo wp_kses_post($this->elements_form());
            echo '</div>';

        } else {
            echo '<div
                id="eh-multibanco-pay-data"
                data-panel-label="' . esc_attr($pay_button_text) . '"
                data-email="' . esc_attr($user_email) . '"
                data-amount="' . esc_attr(EH_Stripe_Payment::get_stripe_amount(WC()->cart->total)) . '"
                data-name="' . esc_attr(sprintf(get_bloginfo('name', 'display'))) . '"
                data-currency="' . esc_attr(strtolower(get_woocommerce_currency())) . '">';

           echo wp_kses_post($this->elements_form());
           
           echo '</div>';
        }
    }


        /**
     *Renders stripe elements on payment form.
     */
    public function elements_form() {
        ob_start();
        ?>
        <fieldset id="eh-<?php echo esc_attr( $this->id ); ?>-cc-form" class="eh-credit-card-form eh-payment-form" style="background:transparent;">

                <div class="clear"></div>

            <!-- Used to display form errors -->
            <div class="eh-multibanco-errors" role="alert" style="color:#ff0000"></div>
            <div class="clear"></div>
        </fieldset>
        <?php
        return ob_get_clean();
    }

    /*public function create_source( $order ) {
        $currency              = $order->get_currency();
        $order_id = $order->get_id();
        $return_url            = add_query_arg('order_id', $order_id, WC()->api_request_url('EH_Multibanco'));
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name  = $order->get_billing_last_name();

        $details = [];

        $name  = $billing_first_name . ' ' . $billing_last_name;
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();

        if ( ! empty( $phone ) ) {
            $details['phone'] = $phone;
        }

        if ( ! empty( $name ) ) {
            $details['name'] = $name;
        }

        if ( ! empty( $email ) ) {
            $details['email'] = $email;
        }

        $details['address']['line1']       = $order->get_billing_address_1();
        $details['address']['line2']       = $order->get_billing_address_2();
        $details['address']['state']       = $order->get_billing_state();
        $details['address']['city']        = $order->get_billing_city();
        $details['address']['postal_code'] = $order->get_billing_postcode();
        $details['address']['country']     = $order->get_billing_country();

        $post_data             = [];
        $post_data['amount']   = EH_Stripe_Payment::get_stripe_amount( $order->get_total(), $currency );
        $post_data['currency'] = strtolower( $currency );
        $post_data['type']     = 'multibanco';
        $post_data['owner']    = $details;
        $post_data['redirect'] = [ 'return_url' => $return_url ];
        $post_data['metadata'] = array('order_id' => $order_id);


        $response = \Stripe\Source::create($post_data);
        return $response;
    }*/
    
        /**
     * Save intent details with order
     */
    public function save_payment_intent_to_order( $order, $intent ) {
        $order->update_meta_data( '_eh_stripe_payment_intent', $intent->id );
        if ( is_callable( array( $order, 'save' ) ) ) {
            $order->save();
        }
    }

    /**
     * Retrieve the payment intent details from order
     */
    public function get_payment_intent_from_order( $order ) {
        $intent_id = $order->get_meta( '_eh_stripe_payment_intent' );

        if ( ! $intent_id ) {
            return false;
        }

        return \Stripe\PaymentIntent::retrieve( array(
            'id'     => $intent_id,
            'expand' => array('latest_charge.balance_transaction'),
        ));
    }


    /**
     *Process stripe payment.
     */
    /*public function process_payment($order_id) { 
        $order = wc_get_order( $order_id );//print_r($order);exit;
        $currency =  $order->get_currency();
        
        try{
            $obj_stripe = new EH_Stripe_Payment();

           $source_response =  $this->create_source($order);
            if ( ! empty( $response->error ) ) {
                throw new Exception( $response->error->message );
            }
            if (isset($source_response->id) && !empty($source_response->id)) {
                $source_id = sanitize_text_field($source_response->id);
                 $source_status = sanitize_text_field($source_response->status);


                if ( version_compare(WC_VERSION, '2.7.0', '<') ) {
                    update_post_meta( $order_id, '_eh_multibanco_source_id', $source_id );
                } else {
                    $order->update_meta_data( '_eh_multibanco_source_id', $source_id );
                }
                $order->save();

                //check the status of source
                if ($source_status == 'chargeable') {
                   $customer = $this->create_stripe_customer($source_id, $order_id, ((version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_email : $order->get_billing_email()));

                    if ($obj_stripe->is_subscription($order_id)) {
                        if (!$customer) {
                            throw new Exception(esc_html__("Could not process subscription order this time. Please try again.", 'payment-gateway-stripe-and-woocommerce-integration'));
                        }

                        //if is zero payment
                        if ( 0 >= $order->get_total() ) {
                            $user_id = $order->get_user_id();
                             
                             EH_Helper_Class::wt_stripe_order_db_operations($order_id, $order, 'update', '_multibanco_customer_id', $customer->id, false); 
                             return $obj_stripe->complete_free_order( $order,$setup_intent );
                        }

                    }
                 
                    
                    if (!empty($customer)) {
                        $user_id = $order->get_user_id();
                        update_user_meta($user_id, "_multibanco_customer_id", $customer->id);
                    }
                    

                    
                    // create charge
                    $charge_response = \Stripe\Charge::create($this->eh_make_charge_params( $order, $source_id,  $customer->id), array(
                                'idempotency_key' => $order->get_order_key()
                            ));

                   return $this->eh_process_payment_response($charge_response, $order, true);

                }
                elseif ($source_status == 'pending') { 
                    if (isset($source_response['redirect']['url']) && !empty($source_response['redirect']['url'])) { 
                                            return array(
                        'result'        => 'success',
                        'redirect'      => esc_url($source_response['redirect']['url']),
                    );
                        //wp_safe_redirect($source_response['redirect']['url']);
                    }
                }
                elseif ($source_status == 'failed') {
                    throw new Exception( esc_html__( 'Unable to process this payment.', 'payment-gateway-stripe-and-woocommerce-integration' ));
                    
                } 
            }
            else{

                throw new Exception( esc_html__( 'Unable to process this payment, please try again.', 'payment-gateway-stripe-and-woocommerce-integration' ));
            }

        }
        catch(Exception $e){

            $order->update_status( 'failed', sprintf( esc_html__( 'Multibanco payment failed: %s', 'payment-gateway-stripe-and-woocommerce-integration' ),$e->getMessage() ) );
            
           wc_add_notice( $e->getMessage(), 'error' );
            return array (
                'result' => 'failure'
            );
        }
        

    }*/

    /**
     * Process stripe payment.
     * Basil: uses PaymentIntent with payment_method_types multibanco (Sources API removed).
     */
    public function process_payment($order_id) { 
        $order = wc_get_order( $order_id );

        try {

            $currency = $order->get_currency();
            $amount   = EH_Stripe_Payment::get_stripe_amount( $order->get_total() );
            $client   = $this->get_clients_details();

            $product_name = array();
            foreach ($order->get_items() as $item) {
                array_push($product_name, $item['name']);
            }
            $product_list = implode(' | ', $product_name);

            // Basil: create PaymentMethod server-side — Multibanco requires billing name + email
            try {
                $multibanco_payment_method = \Stripe\PaymentMethod::create(array(
                    'type'            => 'multibanco',
                    'billing_details' => array(
                        'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                        'email' => $order->get_billing_email(),
                    ),
                ));
            } catch (Exception $e) {
                throw new Exception(__('Unable to create payment method: ', 'payment-gateway-stripe-and-woocommerce-integration') . $e->getMessage());
            }

            $payment_intent_args = array(
                'payment_method_types' => array('multibanco'),
                'amount'               => $amount,
                'currency'             => $currency,
                'payment_method'       => $multibanco_payment_method->id,
                'confirm'              => true,
                //'return_url'           => add_query_arg('order_id', $order_id, WC()->api_request_url('EH_Multibanco')),
                'return_url' => add_query_arg(
                    array(
                        'order_id' => $order_id,
                        'key'      => $order->get_order_key(),
                    ),
                    WC()->api_request_url('EH_Multibanco')
                ),
                'metadata'             => array(
                    'order_id'       => $order_id,
                    'Total Tax'      => $order->get_total_tax(),
                    'Total Shipping' => $order->get_total_shipping(),
                    'Customer IP'    => $client['IP'],
                    'Agent'          => $client['Agent'],
                    'Referer'        => $client['Referer'],
                    'WP customer #'  => $order->get_user_id(),
                    'Billing Email'  => $order->get_billing_email(),
                    'Products'       => substr($product_list, 0, 499),
                ),
                'description' => wp_specialchars_decode( get_bloginfo('name'), ENT_QUOTES ) . ' Order #' . $order->get_order_number(),
                'shipping'    => array(
                    'address' => array(
                        'line1'       => $order->get_shipping_address_1(),
                        'line2'       => $order->get_shipping_address_2(),
                        'city'        => $order->get_shipping_city(),
                        'state'       => $order->get_shipping_state(),
                        'country'     => $order->get_shipping_country(),
                        'postal_code' => $order->get_shipping_postcode(),
                    ),
                    'name'  => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                    'phone' => $order->get_billing_phone(),
                ),
            );

            $payment_intent_args = apply_filters('eh_multibanco_payment_intent_args', $payment_intent_args);

            $intent = $this->get_payment_intent_from_order( $order );

            if ( ! empty($intent) ) {
                if ( 'succeeded' === $intent->status ) {
                    wc_add_notice(__('An error has occurred internally, due to which you are not redirected to the order received page. Please contact support for more assistance.', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
                    wp_safe_redirect(wc_get_checkout_url());
                    exit;
                } else {
                    $intent = \Stripe\PaymentIntent::create( $payment_intent_args, array(
                        'idempotency_key' => $order->get_order_key() . '-' . $multibanco_payment_method->id
                    ));
                }
            } else {
                $intent = \Stripe\PaymentIntent::create( $payment_intent_args, array(
                    'idempotency_key' => $order->get_order_key() . '-' . $multibanco_payment_method->id
                ));
            }

            $this->save_payment_intent_to_order( $order, $intent );
            //EH_Helper_Class::wt_stripe_order_db_operations($order_id, $order, 'add', '_eh_stripe_payment_intent', $intent->id, false);

            if ( isset($intent->next_action->multibanco_display_details) ) {
                $details = $intent->next_action->multibanco_display_details;
                $order->update_meta_data('_multibanco_entity',     $details->entity);
                $order->update_meta_data('_multibanco_reference',  $details->reference);
                $order->update_meta_data('_multibanco_expires_at', isset($details->expires_at) ? $details->expires_at : '');
                $order->save();
            }

            if ( isset($intent->status) && 'requires_action' === $intent->status ) {
                if ( isset($intent->next_action->type) && 'multibanco_display_details' === $intent->next_action->type ) {
                    $order->update_status('on-hold', __('Awaiting Multibanco payment.', 'payment-gateway-stripe-and-woocommerce-integration'));
                    WC()->cart->empty_cart();
                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                }

                // redirect_to_url fallback
                if ( isset($intent->next_action->type) && 'redirect_to_url' === $intent->next_action->type ) {
                    return array(
                        'result'   => 'success',
                        'redirect' => $intent->next_action->redirect_to_url->url,
                    );
                }

            } else {
                return $this->eh_process_payment_response( $intent, $order );
            }

        } catch (Exception $e) {
            /* translators: %s: Error message returned from Stripe Multibanco payment */
            $order->update_status('failed', sprintf(__('Multibanco payment failed: %s', 'payment-gateway-stripe-and-woocommerce-integration'), $e->getMessage()));
            wc_add_notice($e->getMessage(), 'error');
            return array('result' => 'failure');
        }
    }


    /**    
     * gets required parameters for creating stripe charge.
     *
     */
    public function eh_make_charge_params( $order, $source_id,  $customer = null ) {
       
        $stripe_settings               = get_option( 'woocommerce_eh_stripe_pay_settings' );

        $post_data                       =  array();
        $currency                        =  $order->get_currency();
        $post_data['currency']           =  strtolower( $currency);
        $post_data['amount']             =  EH_Stripe_Payment::get_stripe_amount( $order->get_total(), $currency );
        /* translators: %1$s: Site name, %2$s: Order number */
        $post_data['description']        =  sprintf( esc_html__( '%1$s - Order %2$s', 'payment-gateway-stripe-and-woocommerce-integration' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_order_number() );
        $billing_email                   =  (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_email      : $order->get_billing_email();
        $billing_first_name              =  (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_first_name : $order->get_billing_first_name();
        $billing_last_name               =  (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_last_name  : $order->get_billing_last_name();
        
        $post_data['shipping']['name']   =  $billing_first_name . ' ' . $billing_last_name;
        $post_data['shipping']['phone']  =  (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_phone : $order->get_billing_phone();

        $post_data['shipping']['address']['line1']       = (version_compare(WC()->version, '2.7.0', '<')) ? $order->shipping_address_1 : $order->get_shipping_address_1();
        $post_data['shipping']['address']['line2']       = (version_compare(WC()->version, '2.7.0', '<')) ? $order->shipping_address_2 : $order->get_shipping_address_2();
        $post_data['shipping']['address']['state']       = (version_compare(WC()->version, '2.7.0', '<')) ? $order->shipping_state     : $order->get_shipping_state();
        $post_data['shipping']['address']['city']        = (version_compare(WC()->version, '2.7.0', '<')) ? $order->shipping_city      : $order->get_shipping_city();
        $post_data['shipping']['address']['postal_code'] = (version_compare(WC()->version, '2.7.0', '<')) ? $order->shipping_postcode  : $order->get_shipping_postcode();
        $post_data['shipping']['address']['country']     = (version_compare(WC()->version, '2.7.0', '<')) ? $order->shipping_country   : $order->get_shipping_country();
        
        $post_data['metadata']  = array(
            esc_html__( 'customer_name', 'payment-gateway-stripe-and-woocommerce-integration' ) => sanitize_text_field( $billing_first_name ) . ' ' . sanitize_text_field( $billing_last_name ),
            esc_html__( 'customer_email', 'payment-gateway-stripe-and-woocommerce-integration' ) => sanitize_email( $billing_email ),
            'order_id' => $order->get_id(),
        );
        
        if ( $source_id ) {
            $post_data['source'] = $source_id;
        }
       if ( $customer ) {
            $post_data['customer'] = $customer;
        } 
        return apply_filters( 'eh_multibanco_generate_charge_request', $post_data, $order, $source_id );
    }

    /**
     * Store extra meta data for an order and adds order notes for orders.
     */
    /*public function eh_process_payment_response( $response, $order, $auto_redirect = true ) {
        
        //$order_id = $order->get_order_number();
        $order_id = (version_compare(WC()->version, '2.7.0', '<')) ? $order->id : $order->get_id();

        // Stores charge data.
        $obj1 = new EH_Stripe_Payment();
        $charge_param = $obj1->make_charge_params($response, $order_id);
        
        EH_Helper_Class::wt_stripe_order_db_operations($order_id, $order, 'add', '_eh_stripe_payment_charge', $charge_param, false); 
        
        $order_id  = version_compare(WC_VERSION, '2.7.0', '<') ? $order->id : $order->get_id();
        $captured = ( isset( $response->captured ) && $response->captured == true) ? 'Captured' : 'Uncaptured';
        
        // Stores charge capture data.
        if ( version_compare(WC_VERSION, '2.7.0', '<') ) {
            update_post_meta( $order_id, '_eh_multibanco_charge_captured', $captured );
        } else {
            $order->update_meta_data( '_eh_multibanco_charge_captured', $captured );
        }
        
        //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $order_time = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600); 

        if ( 'Captured' === $captured ) {
            
            if ( 'pending' === $response->status && $order->get_status() !== 'on-hold' ) {
                $order_stock_reduced = $order->get_meta( '_order_stock_reduced', true );

                if ( ! $order_stock_reduced ) {
                    wc_reduce_stock_levels( $order_id );
                }

                $order->set_transaction_id( $response->id );
                $order->update_status( 'on-hold');
                $order->add_order_note( esc_html__('Payment Status : ', 'payment-gateway-stripe-and-woocommerce-integration') . ucfirst($response->status) .' [ ' . $order_time . ' ] . ' . esc_html__('Source : ', 'payment-gateway-stripe-and-woocommerce-integration') . $response->source->type . '. ' . esc_html__('Charge Status :', 'payment-gateway-stripe-and-woocommerce-integration') . $captured . (is_null($response->balance_transaction) ? '' :'. Transaction ID : ' . $response->balance_transaction) );
            }
            
            if ( 'succeeded' === $response->status && $order->needs_payment() ) {
                $order->payment_complete( $response->id );

                $order->add_order_note( esc_html__('Payment Status : ', 'payment-gateway-stripe-and-woocommerce-integration') . ucfirst($response->status) .' [ ' . $order_time . ' ] . ' . esc_html__('Source : ', 'payment-gateway-stripe-and-woocommerce-integration') . $response->source->type . '. ' . esc_html__('Charge Status :', 'payment-gateway-stripe-and-woocommerce-integration') . $captured . (is_null($response->balance_transaction) ? '' :'. Transaction ID : ' . $response->balance_transaction) );
            }

        } else {
             $order->set_transaction_id( $response->id );

            if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
                wc_reduce_stock_levels( $order_id );
            }

            /* translators: %s: Charge ID */
            /*$order->update_status( 'on-hold', sprintf( esc_html__( 'Stripe Multibanco order meta (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'payment-gateway-stripe-and-woocommerce-integration' ), $response->id) );
        }

        if ($auto_redirect) {

            return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
        }
        else{
            wp_safe_redirect($this->get_return_url($order));
        }
        //return $response;
    }*/

    /**
     * Store extra meta data for an order and adds order notes for orders.
     * Basil: expects PaymentIntent with latest_charge expanded.
     */
    public function eh_process_payment_response( $response, $order, $auto_redirect = true ) {

        $order_id = $order->get_id();
        $obj1     = new EH_Stripe_Payment();

        // Get charge via latest_charge (basil)
        $charge_response = null;
        if ( ! empty( $response->latest_charge ) ) {
            if ( is_object( $response->latest_charge ) && isset( $response->latest_charge->id ) ) {
                $charge_response = $response->latest_charge;
            } else {
                $charge_response = \Stripe\Charge::retrieve( array(
                    'id'     => $response->latest_charge,
                    'expand' => array('balance_transaction'),
                ));
            }
        }

        if ( ! empty($charge_response) ) {
            $charge_param = $obj1->make_charge_params($charge_response, $order_id);
            EH_Helper_Class::wt_stripe_order_db_operations($order_id, $order, 'add', '_eh_stripe_payment_charge', $charge_param, false);

            $captured = ( isset($charge_response->captured) && true === $charge_response->captured ) ? 'Captured' : 'Uncaptured';
            $order->update_meta_data('_eh_multibanco_charge_captured', $captured);
            $order->save();
        }
        //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $order_time          = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600);
        $charge_status       = isset($charge_response->status) ? $charge_response->status : '';
        $payment_method_type = isset($charge_response->payment_method_details->type) ? $charge_response->payment_method_details->type : 'multibanco';
        $balance_txn         = '';
        if ( ! empty($charge_response) && ! is_null($charge_response->balance_transaction) ) {
            $balance_txn = '. Transaction ID : ' . ( isset($charge_response->balance_transaction->id) ? $charge_response->balance_transaction->id : $charge_response->balance_transaction );
        }

        if ( isset($response->status) && 'succeeded' === $response->status ) {

            if ( ! empty($charge_response) && true === $charge_response->paid ) {

                if ( 'processing' !== $order->get_status() || 'completed' === $order->get_status() && true === $charge_response->captured ) {
                    $order->payment_complete($charge_response->id);
                }elseif ( in_array( isset($response->status) ? $response->status : '', array('processing', 'pending', 'requires_action') ) ) {
                    $order->update_status('on-hold', __('Awaiting Multibanco payment.', 'payment-gateway-stripe-and-woocommerce-integration'));
                } else {
                    $order->update_status('on-hold');
                }

                $order->add_order_note(
                    __('Payment Status : ', 'payment-gateway-stripe-and-woocommerce-integration') . ucfirst($charge_status) .
                    ' [ ' . $order_time . ' ] . ' .
                    __('Source : ', 'payment-gateway-stripe-and-woocommerce-integration') . $payment_method_type .
                    '. ' . __('Charge Status :', 'payment-gateway-stripe-and-woocommerce-integration') . $captured .
                    $balance_txn
                );
                WC()->cart->empty_cart();
                EH_Stripe_Log::log_update('live', $charge_response, 'debug code : 1075  ' . get_bloginfo('blogname') . ' - Charge - Order #' . $order->get_order_number());

            } else {
                $order->update_status('on-hold', __('Waiting for the payment to succeed or fail.', 'payment-gateway-stripe-and-woocommerce-integration'));
            }

        } elseif ( in_array( isset($response->status) ? $response->status : '', array('processing', 'pending', 'requires_action') ) ) {
            $order->update_status('on-hold', __('Waiting for the payment to succeed or fail.', 'payment-gateway-stripe-and-woocommerce-integration'));

        } else {
            $order->update_status('failed', __('Stripe payment failed.', 'payment-gateway-stripe-and-woocommerce-integration'));
            wc_add_notice($charge_status, 'error');
            EH_Stripe_Log::log_update('dead', $response, 'debug code : 1077  ' . get_bloginfo('blogname') . ' - Charge - Order #' . $order->get_order_number());
        }

        if ($auto_redirect) {
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } else {
            wp_safe_redirect($this->get_return_url($order));
            exit;
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
     *Creates stripe customer
     */
    public function create_stripe_customer($source, $order_id, $user_email = false) {
        
        $response = \Stripe\Customer::create(array(
                    "description" => "Customer for Order #" . $order_id,
                    "email" => $user_email,
                    "source" => $source
        ));

        if (empty($response->id)) {
            return false;
        }

        return $response;
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
                        
                $order = new WC_Order($order_id);
                $div = $amount * ($total_amount / ((version_compare(WC()->version, '2.7.0', '<')) ? $order->order_total : $order->get_total()));
                $refund_params = array(
                    'amount' => EH_Stripe_Payment::get_stripe_amount($div, $currency),
                    'reason' => 'requested_by_customer',
                    'charge' => $charge_id,
                    'metadata' => array(
                        'order_id' => $order->get_id(),
                        'refund_initiated_from' => 'woocommerce',
                        'Total Tax' => $order->get_total_tax(),
                        'Total Shipping' => (version_compare(WC()->version, '2.7.0', '<')) ? $order->get_total_shipping() : $order->get_shipping_total(),
                        'Customer IP' => $client['IP'],
                        'Agent' => $client['Agent'],
                        'Referer' => $client['Referer'],
                        'Reason for Refund' => $reason
                    )
                );
                        
                try {
                    //$charge_response = \Stripe\Charge::retrieve($charge_id);
                    $refund_response = \Stripe\Refund::create($refund_params);
                    if ($refund_response) {
                        //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                        $refund_time = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600);
                        $obj = new EH_Stripe_Payment();
                        $data = $obj->make_refund_params($refund_response, $amount, ((version_compare(WC()->version, '2.7.0', '<')) ? $order->order_currency : $order->get_currency()), $order_id);
                        
                        EH_Helper_Class::wt_stripe_order_db_operations($order_id, $order, 'add', '_eh_stripe_payment_refund', $data, false); 

                        $order->add_order_note(esc_html__('Reason : ', 'payment-gateway-stripe-and-woocommerce-integration') . $reason . '.<br>' . esc_html__('Amount : ', 'payment-gateway-stripe-and-woocommerce-integration') . get_woocommerce_currency_symbol() . $amount . '.<br>' . esc_html__('Status : refunded ', 'payment-gateway-stripe-and-woocommerce-integration') . ' [ ' . $refund_time . ' ] ' . (is_null($data['transaction_id']) ? '' : '<br>' . esc_html__('Transaction ID : ', 'payment-gateway-stripe-and-woocommerce-integration') . $data['transaction_id']));
                        EH_Stripe_Log::log_update('live', $data, get_bloginfo('blogname') . ' - Refund - Order #' . $order->get_order_number());
                        return true;
                    } else {
                        EH_Stripe_Log::log_update('dead', $data, get_bloginfo('blogname') . ' - Refund Error - Order #' . $order->get_order_number());
                        $order->add_order_note(esc_html__('Reason : ', 'payment-gateway-stripe-and-woocommerce-integration') . $reason . '.<br>' . esc_html__('Amount : ', 'payment-gateway-stripe-and-woocommerce-integration') . get_woocommerce_currency_symbol() . $amount . '.<br>' . esc_html__(' Status : Failed ', 'payment-gateway-stripe-and-woocommerce-integration'));
                        return new WP_Error('error', $data->message);
                    }
                } catch (Exception $error) {
                    $oops = $error->getJsonBody();
                    EH_Stripe_Log::log_update('dead', $oops['error'], get_bloginfo('blogname') . ' - Refund Error - Order #' . $order->get_order_number());
                    $order->add_order_note(esc_html__('Reason : ', 'payment-gateway-stripe-and-woocommerce-integration') . $reason . '.<br>' . esc_html__('Amount : ', 'payment-gateway-stripe-and-woocommerce-integration') . get_woocommerce_currency_symbol() . $amount . '.<br>' . esc_html__('Status : ', 'payment-gateway-stripe-and-woocommerce-integration') . esc_html($oops['error']['message']));
                    return new WP_Error('error', esc_html($oops['error']['message']));
                }
            } else {
                return new WP_Error('error', esc_html__('Uncaptured Amount cannot be refunded', 'payment-gateway-stripe-and-woocommerce-integration'));
            }
        } else {
            return false;
        }
    }

    //webhook callback
   /* public function eh_multibanco_callback_handler() { 
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
       $order_id = (isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id'])) ? sanitize_text_field(wp_unslash($_REQUEST['order_id'])) : '';
        $order = wc_get_order($order_id);

        try{ 

            //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
            if (isset($_REQUEST['source']) && !empty($_REQUEST['source'])) {
                //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
                $source_id = sanitize_text_field(wp_unslash($_REQUEST['source']));
                //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
                if(isset($_REQUEST['redirect_status']) && !empty($_REQUEST['redirect_status'])){
                    //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
                    if (strtolower(sanitize_text_field(wp_unslash($_REQUEST['redirect_status']))) == 'succeeded' || strtolower(sanitize_text_field(wp_unslash($_REQUEST['redirect_status']))) == 'pending') { 
                        //Retrieve source
                        $source_response = \Stripe\Source::retrieve($source_id);
                        $this->process_source_response($source_response, $order);
                    }
                    else{
                        throw new Exception(esc_html__('Unable to process this payment, please try again.', 'payment-gateway-stripe-and-woocommerce-integration'));
                        
                    }

                }
                else{
                    throw new Exception(esc_html__('Unable to process this payment, please try again.', 'payment-gateway-stripe-and-woocommerce-integration'));
                }                          
            }
            else{
                throw new Exception(esc_html__('Unable to process this payment, please try again.', 'payment-gateway-stripe-and-woocommerce-integration'));
            }


        }
        catch(Exception $e){
            /* translators: %s: Error message */
           /* $order->update_status( 'failed', sprintf( esc_html__( 'Multibanco payment failed: %s', 'payment-gateway-stripe-and-woocommerce-integration' ),$e->getMessage() ) );

            /* translators: %s: Error message */
            /*wc_add_notice( sprintf( esc_html__( 'Error: %s', 'payment-gateway-stripe-and-woocommerce-integration' ), $e->getMessage()), 'error' );
            wp_safe_redirect( wc_get_checkout_url() );
        }

    }*/
    /**
     * Callback handler — basil: uses PaymentIntent, not Source redirect.
     */
    public function eh_multibanco_callback_handler() { 
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_id  = isset($_REQUEST['order_id']) ? absint($_REQUEST['order_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_key = isset($_REQUEST['key']) ? sanitize_text_field(wp_unslash($_REQUEST['key'])) : '';

        if ( ! $order_id ) {
            wc_add_notice(__('Invalid order.', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        $order = wc_get_order($order_id);

        // Layer 1: order key validation
        if ( ! $order || ! hash_equals($order->get_order_key(), $order_key) ) {
            wc_add_notice(__('Invalid order key.', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        // Already handled by webhook — send to thank you page
        if ( $order->has_status(array('processing', 'completed', 'on-hold'))) {
            wp_safe_redirect($this->get_return_url($order));
            exit;
        }
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_REQUEST['payment_intent']) && !empty($_REQUEST['payment_intent'])) {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $intent_id       = sanitize_text_field(wp_unslash($_REQUEST['payment_intent']));
            $saved_intent_id = $order->get_meta('_eh_stripe_payment_intent');

            if ( empty($saved_intent_id) || ! hash_equals($saved_intent_id, $intent_id) ) {
                EH_Stripe_Log::log_update('dead', array(
                    'order_id'        => $order_id,
                    'supplied_intent' => $intent_id,
                    'saved_intent'    => $saved_intent_id ?: 'none',
                ), get_bloginfo('blogname') . ' - multibanco: intent mismatch - Order #' . $order_id);

                wc_add_notice(__('Unable to process this payment.', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }

            try {
                // Always verify intent on return — no filter gate
                $intent_result = \Stripe\PaymentIntent::retrieve(array(
                    'id'     => $intent_id,
                    'expand' => array('latest_charge.balance_transaction'),
                ));

                $this->eh_process_payment_response($intent_result, $order);
                wp_safe_redirect($this->get_return_url($order));
                exit;
               
            } catch (Exception $e) {
                
                wc_add_notice(__('Payment processing error.', 'payment-gateway-stripe-and-woocommerce-integration'), 'error');
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }

        } 

        wc_add_notice(__('Your payment is pending. You will receive a confirmation shortly.', 'payment-gateway-stripe-and-woocommerce-integration'), 'notice');
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }

    /*public function process_source_response($source_response, $order = null){ 
        if (!empty($source_response)) { 
            if (isset($source_response->error)) {
                throw new Exception(esc_html($source_response->error->message));
            }
            else{ 
                if (isset($source_response->status) && !empty($source_response->status)) {
                    if ($source_response->status == 'chargeable') { 
                        
                        $charge_response = \Stripe\Charge::create($this->eh_make_charge_params( $order, $source_response->id), array(
                                'idempotency_key' => $order->get_order_key()
                            ));
                        return $this->eh_process_payment_response($charge_response, $order, false);

                    }
                    // case when source is on pending status
                    elseif ('pending' == $source_response->status) {
                        // Update order status to on-hold
                        $order_stock_reduced = $order->get_meta( '_order_stock_reduced', true );
                        if ( ! $order_stock_reduced ) {
                            wc_reduce_stock_levels( $order_id );
                        }
                        $order->update_status( 'on-hold');
                        //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                        $order_time = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600); 
                        /* translators: %s: Order time */
                        /*$order->add_order_note( sprintf( esc_html__('Payment Status : Charge not initiated [ %s ]', 'payment-gateway-stripe-and-woocommerce-integration'), $order_time ) );

                        wp_safe_redirect( $this->get_return_url($order) );
                        exit;


                    }
                    elseif ('consumed' == $source_response->status) {
                        wc_add_notice(esc_html__('Your payment is already processed!!', 'payment-gateway-stripe-and-woocommerce-integration'));
                        wp_safe_redirect( $this->get_return_url($order) );
                        exit;
                    }
                    else{ 
                        /* translators: %s: Source status */
                        /*throw new Exception(sprintf(esc_html__('Source status is %s', 'payment-gateway-stripe-and-woocommerce-integration'), esc_html($source_response->status)));
                    }
                }
                else{
                     throw new Exception( esc_html__('Unable to find source status, please try again.', 'payment-gateway-stripe-and-woocommerce-integration'));
                }
            }
        }
    }*/

     /**
     * Show Multibanco voucher details on order received page.
     * Makes an API call to get latest details (in case customer returns after some time).
     */

    public function show_multibanco_voucher($order_id) {
        $order     = wc_get_order($order_id);
        $intent_id = $order->get_meta('_eh_stripe_payment_intent');

        if ( ! $intent_id ) {
            return;
        }

        $intent = \Stripe\PaymentIntent::retrieve($intent_id);

        if ( isset($intent->next_action->multibanco_display_details) ) {
            $details = $intent->next_action->multibanco_display_details;
            echo '<h3>' . esc_html__('Multibanco Payment Details', 'payment-gateway-stripe-and-woocommerce-integration') . '</h3>';
            echo '<p><strong>' . esc_html__('Entity:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . esc_html($details->entity) . '</p>';
            echo '<p><strong>' . esc_html__('Reference:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . esc_html($details->reference) . '</p>';
            echo '<p><strong>' . esc_html__('Amount:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . wp_kses_post($order->get_formatted_order_total()) . '</p>';
            if ( isset($details->expires_at) ) {
                echo '<p><strong>' . esc_html__('Pay before:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $details->expires_at)) . '</p>';
            }
        }
    }

     /**
     * Show Multibanco voucher details on myaccount order page.
     * Reads from saved meta — no extra API call needed.
     */
    public function show_multibanco_voucher_myaccount( $order ) {
        if ( $order->get_payment_method() !== 'eh_multibanco_stripe' ) {
            return;
        }
        if ( ! $order->has_status('on-hold') ) {
            return;
        }
 
        $entity    = $order->get_meta('_multibanco_entity');
        $reference = $order->get_meta('_multibanco_reference');
        $expires   = $order->get_meta('_multibanco_expires_at');
 
        if ( empty($entity) || empty($reference) ) {
            return;
        }
 
        echo '<h3>' . esc_html__('Multibanco Payment Details', 'payment-gateway-stripe-and-woocommerce-integration') . '</h3>';
        echo '<p><strong>' . esc_html__('Entity:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . esc_html($entity) . '</p>';
        echo '<p><strong>' . esc_html__('Reference:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . esc_html($reference) . '</p>';
        echo '<p><strong>' . esc_html__('Amount:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . wp_kses_post($order->get_formatted_order_total()) . '</p>';
        if ( ! empty($expires) ) {
            echo '<p><strong>' . esc_html__('Pay before:', 'payment-gateway-stripe-and-woocommerce-integration') . '</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $expires)) . '</p>';
        }
    }
  
}