<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles stripe payment request button functionality.
 * 
 */
#[\AllowDynamicProperties]
class Eh_Stripe_Payment_Request_Class {

    protected $eh_stripe_option;

    function __construct() {
        $this->eh_stripe_option = get_option("woocommerce_eh_stripe_pay_settings");

        if(isset($this->eh_stripe_option['eh_payment_request_button_enable_options'])){
            $this->eh_stripe_payment_request_button_options = $this->eh_stripe_option['eh_payment_request_button_enable_options'] ? $this->eh_stripe_option['eh_payment_request_button_enable_options'] : array();
        }
         
        //applepay enabled options
        if(isset($this->eh_stripe_option['eh_stripe_apple_pay_options'])){
            $this->eh_stripe_apple_pay_options = $this->eh_stripe_option['eh_stripe_apple_pay_options'] ? $this->eh_stripe_option['eh_stripe_apple_pay_options'] : array();
        }


        // Get the position of express buttons
        $express_button_position = isset($this->eh_stripe_option['eh_stripe_express_button_position']) ? $this->eh_stripe_option['eh_stripe_express_button_position'] : 'below';
        $priority = ('below' === $express_button_position) ? 20 : 0;

        //check whether apple pay or gpay is enabled
        if((isset($this->eh_stripe_option['eh_stripe_apple_pay']) && 'yes' === $this->eh_stripe_option['eh_stripe_apple_pay']) || (isset($this->eh_stripe_option['eh_payment_request']) && ($this->eh_stripe_option['eh_payment_request'] === 'yes'))){

            add_action('woocommerce_proceed_to_checkout', array($this, 'eh_add_payment_request_button'), $priority);
            
            if('above'  === $express_button_position){ 
                add_action('woocommerce_before_checkout_form', array($this, 'eh_add_payment_request_button'));
                add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'eh_add_payment_request_button' ));
            }else{ 
                add_action('woocommerce_review_order_after_submit', array($this, 'eh_add_payment_request_button'),10);
                add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'eh_add_payment_request_button' ));
            }

        }
        
            

        add_action('wp_enqueue_scripts', array($this, 'payment_request_scripts'));

        add_action( 'wc_ajax_eh_spg_gen_payment_request_button_cart', array( $this, 'payment_request_button_cart_items' ) );
        add_action( 'wc_ajax_eh_spg_payment_request_get_shippings', array($this, 'eh_get_shipping_methods' ) );
        add_action( 'wc_ajax_eh_spg_payment_request_update_shippings', array( $this, 'eh_update_shipping_method' ) );
        add_action( 'wc_ajax_eh_spg_gen_payment_request_create_order', array( $this, 'eh_create_order' ) );
        add_action( 'wc_ajax_eh_spg_add_to_cart', array( $this, 'eh_add_to_cart' ) );
        add_action( 'template_redirect', array( $this, 'eh_set_session' ) );        
    }
 

    /**
     * Sets the WC customer session if one is not set, needed for nonce verification
     * 
     */
    public function eh_set_session() {
		if ( ! is_product() || ( isset( WC()->session ) && WC()->session->has_session() ) ) {
			return;
		}
		WC()->session->set_customer_session_cookie( true );
    }
    

    /**
     * Loads required scripts 
     * 
     */
    public function payment_request_scripts() {

        if( ( isset($this->eh_stripe_option['eh_payment_request']) && ($this->eh_stripe_option['eh_payment_request'] === 'yes') ) || ( isset($this->eh_stripe_option['eh_stripe_apple_pay']) && ($this->eh_stripe_option['eh_stripe_apple_pay'] === 'yes') ) ){
           
            if($this->is_payment_request_button_enabled() || $this->is_apple_pay_enabled() ){
                
                wp_enqueue_style('eh_apple_pay_style', EH_STRIPE_MAIN_URL_PATH . 'assets/css/apple-pay.css',EH_STRIPE_VERSION);
                wp_enqueue_script('eh_payment_request', EH_STRIPE_MAIN_URL_PATH . 'assets/js/eh-payment-request-button.js',array('stripe_v3_js','jquery'),EH_STRIPE_VERSION);

                //Test mode
                if ('test' == $this->eh_stripe_option['eh_stripe_mode']) {
                    //get tokens based on plugin authentication method
                    if(Eh_Stripe_Admin_Handler::wtst_oauth_compatible($this->eh_stripe_option['eh_stripe_mode'])){   
                        $tokens = EH_Stripe_Token_Handler::wtst_get_stripe_tokens('test');
                        $secret_key = $tokens['wt_stripe_access_token'];
                        $public_key = $tokens['wt_stripe_publishable_key'];
                    
                    }
                    else{                 
                        $public_key = $this->eh_stripe_option['eh_stripe_test_publishable_key'];
                        $secret_key = $this->eh_stripe_option['eh_stripe_test_secret_key'];
                    }
                //Live mode    
                } else {
                    if(Eh_Stripe_Admin_Handler::wtst_oauth_compatible($this->eh_stripe_option['eh_stripe_mode'])){   
                        $tokens = EH_Stripe_Token_Handler::wtst_get_stripe_tokens('live');
                        $secret_key = $tokens['wt_stripe_access_token']; 
                        $public_key = $tokens['wt_stripe_publishable_key'];                     
                    }
                    else{                     
                        $public_key = $this->eh_stripe_option['eh_stripe_live_publishable_key'];
                        $secret_key = $this->eh_stripe_option['eh_stripe_live_secret_key'];
                    }
                }
                if ( empty( $site ) ) {
                    $site = $_SERVER['SERVER_NAME'];
                }
                
                $gpay_button_type   = isset($this->eh_stripe_option['eh_payment_request_button_type'])   ? $this->eh_stripe_option['eh_payment_request_button_type']   : 'buy';
                $gpay_button_theme  = isset($this->eh_stripe_option['eh_payment_request_button_theme'])  ? $this->eh_stripe_option['eh_payment_request_button_theme']  : 'black';
                $button_height = isset($this->eh_stripe_option['eh_payment_request_button_height']) ? $this->eh_stripe_option['eh_payment_request_button_height'] : '44';

                $apple_pay_color = isset($this->eh_stripe_option['eh_stripe_apple_color'])   ? $this->eh_stripe_option['eh_stripe_apple_color']   : 'black';
                $apple_pay_type = isset($this->eh_stripe_option['eh_stripe_apple_pay_type'])   ? $this->eh_stripe_option['eh_stripe_apple_pay_type']   : 'plain';
                $eh_payment_request_params = array(
                    'key'                                           => $public_key,
                    'gpay_enabled'                                  => (true === $this->is_payment_request_button_enabled() ? 'yes' : 'no'),
                    'apple_pay_enabled'                             => (true === $this->is_apple_pay_enabled() ? 'yes' : 'no'),
                    'label'                                         => $site,
                    'gpay_button_type'                              => $gpay_button_type,
                    'gpay_button_theme'                             => $gpay_button_theme,
                    'apple_pay_color'                               => $apple_pay_color,
                    'apple_pay_type'                                => $apple_pay_type,
                    'button_height'                                 => (int) $button_height,
                    'currency_code'                                 => strtolower(get_woocommerce_currency()),
                    'country_code'                                  => substr( get_option( 'woocommerce_default_country' ), 0, 2 ),
                    'wp_ajaxurl'                                    => admin_url("admin-ajax.php"),
                    'wc_ajaxurl'                                    => WC_AJAX::get_endpoint( '%%change_end%%' ),
                    'needs_shipping'                                => WC()->cart->needs_shipping() ? 'yes' : 'no',
                    'eh_checkout_nonce'                             => wp_create_nonce( 'woocommerce-process_checkout' ),
                    'eh_add_to_cart_nonce'                          => wp_create_nonce( '_eh_add_to_cart_nonce' ),
                    'eh_payment_request_cart_nonce'                 => wp_create_nonce( '_eh_payment_request_button_cart_nonce' ),
                    'eh_payment_request_get_shipping_nonce'         => wp_create_nonce( '_eh_payment_request_get_shipping_nonce' ),
                    'eh_payment_request_update_shipping_nonce'      => wp_create_nonce( '_eh_payment_request_update_shipping_nonce' ),
                    'is_cart_page'                                  => is_cart() ? 'yes' : 'no',
                    'product_data'                                  => $this->product_data(),
                    'product'                                       => is_product(),
                );
                $eh_payment_request_params['version'] = EH_Stripe_Token_Handler::wt_get_api_version();  
                wp_localize_script( 'eh_payment_request', 'eh_payment_request_params', $eh_payment_request_params);
            }
           
            $gateways = WC()->payment_gateways->get_available_payment_gateways();
            if ( isset( $gateways['eh_stripe_pay'] ) ) { 
                $gateways['eh_stripe_pay']->payment_scripts();
            }
            else{
                $gateways = WC()->payment_gateways->payment_gateways();
                if(isset($gateways['eh_stripe_pay'])){ 
                    $gateways['eh_stripe_pay']->payment_scripts();
                }
            }

        }
    }
    public function eh_supported_product_types() {
		return apply_filters(
			'eh_supported_product_types',
			array(
				'simple','variable','variation','booking','bundle','composite','mix-and-match',
			)
		);
    }
    
    public function eh_check_for_allowed_product() {

        if(is_product()){
            global $post;

            $product = wc_get_product( $post->ID );

            $product_type = (WC()->version < '3.0') ? $product->product_type : $product->get_type();
            
            if ( ! in_array( $product_type, $this->eh_supported_product_types() ) ) {
                return false;
            }
           
            
        }else{
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

                $product_type = (WC()->version < '3.0') ? $_product->product_type : $_product->get_type();
                if ( ! in_array( $product_type, $this->eh_supported_product_types() ) ) {
                    return false;
                }
              
            }
        }
        return true;
       
    }

    /**
     * Checks which pages payment request button is to be enabled
     * 
     */
    public function is_payment_request_button_enabled(){
       
        if ((isset($this->eh_stripe_option['eh_payment_request']) && ('yes' === $this->eh_stripe_option['eh_payment_request'] )) && ((is_cart() && isset($this->eh_stripe_payment_request_button_options) && in_array('cart', $this->eh_stripe_payment_request_button_options)) || (is_checkout() && isset($this->eh_stripe_payment_request_button_options) && in_array('checkout', $this->eh_stripe_payment_request_button_options)) || (is_product() && isset($this->eh_stripe_payment_request_button_options) && in_array('product', $this->eh_stripe_payment_request_button_options))) ) {
            return true;
        }else{
            return false;
        }
    }
   
    /**
     * Includes payment request button element div
     * 
     */
    public function eh_add_payment_request_button() {

        if($this->is_payment_request_button_enabled() || $this->is_apple_pay_enabled() ){

            if(! $this->eh_check_for_allowed_product()){
                return;
            }

            $payment_request_button = '
                    <div id="eh-stripe-payment-request-button">
                    
                    <!-- A Stripe Element will be inserted here. -->
                </div>';
            echo $payment_request_button;
        }
    }

    /**
     * Includes payment request button seperator div
     * 
     */
    public function display_payment_request_button_separator() {

        if($this->is_payment_request_button_enabled()){

            if(! $this->eh_check_for_allowed_product()){
                return;
            }

            echo '<div id="eh-payment-request-button-seperator"><p style = "margin-top:1.5em;text-align:center;"> '. __( 'OR', 'payment-gateway-stripe-and-woocommerce-integration' ) .' </p></div>';
        }
    }
    
    /**
     * Gets cart details
     * 
     */
    public function payment_request_button_cart_items(){

        if(!EH_Helper_Class::verify_nonce(EH_STRIPE_PLUGIN_NAME, '_eh_payment_request_button_cart_nonce'))
        {
            wp_die(__('Access Denied', 'payment-gateway-stripe-and-woocommerce-integration'));
        }
        
        $total = EH_Stripe_Payment::get_stripe_amount( WC()->cart->total); 
        $total = apply_filters("wt_payment_request_total", $total);        
        wp_send_json( array( 'line_items' => $this->get_params_cc_payment_request() ,'total' => (int) $total ) );
    }

    /**
     * Gets product details
     * 
     */
    function product_data(){
        if(is_product()){
            global $post;

            $decimals = 2;
            $items    = array();
            $data     = array();
            $subtotal = 0;
            
            $_product = wc_get_product( $post->ID );
            $item = array(
                'label'  =>  (WC()->version < '3.0') ? $_product->name : $_product->get_name(),
                'amount' =>  (int) EH_Stripe_Payment::get_stripe_amount(wc_format_decimal( ((WC()->version < '3.0') ? $_product->price : $_product->get_price()), $decimals )),
            );
            $items[] = $item;

            if ( wc_tax_enabled() ) {
                $items[] = array(
                    'label'  => esc_html( __( 'Tax', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
                    'amount' => 0,
                );
            }
    
            if ( wc_shipping_enabled() && $_product->needs_shipping() ) {
                $items[] = array(
                    'label'  => esc_html( __( 'Shipping', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
                    'amount'  => 0,
                );
            }

            $total = EH_Stripe_Payment::get_stripe_amount(wc_format_decimal( ((WC()->version < '3.0') ? $_product->price : $_product->get_price()), $decimals ));
            $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
            
            $data['displayItems'] = $items;

            $data['total']       = array(
                'label'   =>  $site,
                'amount'  =>  (int) $total ,
                'pending' => false,
            );

            $data['needs_shipping'] = ( wc_shipping_enabled() && $_product->needs_shipping() );
            return $data;
    
        }
    }

    /**
     * Gets the line items.
     * 
     */
    public function get_params_cc_payment_request() {
        $decimals = 2;
        $items    = array();
        $subtotal = 0;
        
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $amount         = wc_format_decimal( $cart_item['line_subtotal'], $decimals );
            $subtotal       += $cart_item['line_subtotal'];
            $quantity_label = 1 < $cart_item['quantity'] ? ' (x' . $cart_item['quantity'] . ')' : '';
            $_product =  wc_get_product( $cart_item['data']->get_id()); 

            $item = array(
                    'name'  => ((WC()->version < '3.0') ? $_product->name : $_product->get_name()). $quantity_label,
                    'amount' =>  (int) EH_Stripe_Payment::get_stripe_amount($amount),
            );

            $items[] = $item;
        }

        $discounts   = EH_Stripe_Payment::get_stripe_amount(  WC()->cart->get_cart_discount_total());
        $tax         = EH_Stripe_Payment::get_stripe_amount( WC()->cart->tax_total + WC()->cart->shipping_tax_total);
        $shipping    = EH_Stripe_Payment::get_stripe_amount( WC()->cart->shipping_total);
        $items_total = wc_format_decimal( WC()->cart->cart_contents_total, WC()->cart->dp ) + $discounts;

        if ( wc_tax_enabled() ) {
                $items[] = array(
                        'name'  => esc_html( __( 'Tax', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
                        'amount' => (int) $tax,
                );
        }

        if ( WC()->cart->needs_shipping() ) {
                $items[] = array(
                        'name'  => esc_html( __( 'Shipping', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
                        'amount' => (int) $shipping,
                );
        }

        if ( WC()->cart->has_discount() ) {
                $items[] = array(
                        'name'  => esc_html( __( 'Discount', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
                        'amount' => (int) $discounts,
                );
        }
        $total = EH_Stripe_Payment::get_stripe_amount( WC()->cart->total, get_woocommerce_currency());
        if( ! $total ) return;
        $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $params =  array(
            'displayItems' => $items,
            'total'        => array(
                'label'   =>  $site,
                'amount'  =>  (int) $total ,
                'pending' => false,
            ),
        );
        $params = apply_filters('wt_stripe_alter_payment_request_params', $params);
        return $params;

    }

    /**
     * Creates order.
     * 
     */
    public function eh_create_order() {

		if ( WC()->cart->is_empty() ) {
			wp_send_json_error( __( 'Empty cart', 'payment-gateway-stripe-and-woocommerce-integration' ) );
		}

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}
        add_filter('wt_stripe_gateway_available', array($this, 'wt_stripe_gateway_available'));

		WC()->checkout()->process_checkout();

    }

    /**
     * Add product to cart when payment request button is clicked in product page.
     * 
     */
    public function eh_add_to_cart(){

        if(!EH_Helper_Class::verify_nonce(EH_STRIPE_PLUGIN_NAME, '_eh_add_to_cart_nonce'))
        {
            wp_die(__('Access Denied', 'payment-gateway-stripe-and-woocommerce-integration'));
        }
        
        $product_id   = absint( $_POST['product_id'] );
        $variation_id = absint( $_POST['variation_id'] );
        $qty          = ! isset( $_POST['qty'] ) ? 1 : absint( $_POST['qty'] );

        /**
         * 
         * Action fires before adding the product to the cart using payment request button
         * @param product_id int currently viewing product id
         */
        do_action("wt_stripe_button_before_add_to_cart", $product_id);
        $product      = wc_get_product( $product_id );
        $product_type = (WC()->version < '3.0') ? $product->product_type : $product->get_type();

        // First empty the cart to prevent wrong calculation.
        WC()->cart->empty_cart();


        if ('variable' === $product_type ) {

            WC()->cart->add_to_cart( $product->get_id(), $qty, $variation_id);
        }

        if ( 'simple' === $product_type) {
            WC()->cart->add_to_cart( $product->get_id(), $qty );
        }

        /**
         * 
         * Action fires after adding the product to the cart using payment request button
         * @param currently viewing product id
         */
        do_action("wt_stripe_button_after_add_to_cart", $product_id);
        WC()->cart->calculate_totals();

        $data           = array();
        $data          += $this->get_params_cc_payment_request();
        $data['result'] = 'success';
        $data = apply_filters("wt_stripe_add_to_cart_params", $data);
        wp_send_json( $data );
    }

    /**
	 * Get shipping options.
	 *
     */
    public function eh_get_shipping_methods() {

        if(!EH_Helper_Class::verify_nonce(EH_STRIPE_PLUGIN_NAME, '_eh_payment_request_get_shipping_nonce'))
        {
            wp_die(__('Access Denied', 'payment-gateway-stripe-and-woocommerce-integration'));
        }
        
        try {

            $address = array(
                'country'   => sanitize_text_field($_POST['country']),
                'state'     => sanitize_text_field($_POST['state']),
                'postcode'  => sanitize_text_field($_POST['postcode']),
                'city'      => sanitize_text_field($_POST['city']),
                'address'   => sanitize_text_field($_POST['address']),
                'address_2' => sanitize_text_field($_POST['address_2']),
            );

            $this->calculate_shipping( $address );

            // Set the shipping options.
            $currency = get_woocommerce_currency();
            $data     = array();

            $packages = WC()->shipping->get_packages();

            if ( ! empty( $packages ) && WC()->customer->has_calculated_shipping() ) {
                foreach ( $packages as $package_key => $package ) {
                    if ( empty( $package['rates'] ) ) {
                        throw new Exception( __( 'Unable to find shipping method for address.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
                    }

                    foreach ( $package['rates'] as $key => $rate ) {
                        $data['shipping_options'][] = array(
                            'id'       => $rate->id,
                            'displayName'    => $rate->label,
                            'amount' => (int) EH_Stripe_Payment::get_stripe_amount( $rate->cost ),
                                            
                        );
                    }
                }
            } else {
                throw new Exception( __( 'Unable to find shipping method for address.', 'payment-gateway-stripe-and-woocommerce-integration' ) );
            }    

            if ( isset( $data[0] ) ) {
                // Auto select the first shipping method.
                WC()->session->set( 'chosen_shipping_methods', array( $data[0]['id'] ) );
            }

            if(isset($_REQUEST['is_product']) && 'yes' !== sanitize_text_field($_REQUEST['is_product'])){
                WC()->cart->calculate_totals();

                $data += $this->get_params_cc_payment_request();
            }

            $data['result'] = 'success';
            
            wp_send_json( $data );
           
        } catch ( Exception $e ) {
            $data += $this->get_params_cc_payment_request();
            $data['result'] = 'invalid_shipping_address';

            wp_send_json( $data );
        }
    }

    /**
	 * Get shipping options.
	 *
     */
    public function calculate_shipping( $address = array() ) {
        $country  = strtoupper( $address['country'] );
        $state    = strtoupper( $address['state'] );
        $postcode = $address['postcode'];
        $city     = $address['city'];

        WC()->shipping->reset_shipping();

        //alter postal code
        $postcode = $this->alter_post_code( $postcode, $country );


        if ( $postcode &&  WC_Validation::is_postcode( $postcode, $country ) ) {
            $postcode = wc_format_postcode( $postcode, $country );
        } 

        if ( $country ) {
                WC()->customer->set_location( $country, $state, $postcode, $city );
                WC()->customer->set_shipping_location( $country, $state, $postcode, $city );
        } else {
                WC()->customer->set_to_base();
                WC()->customer->set_shipping_to_base();
        }

        WC()->customer->set_calculated_shipping( true );
        WC()->customer->save();

        $packages = array();

        $packages[0]['contents']                 = WC()->cart->get_cart();
        $packages[0]['contents_cost']            = 0;
        $packages[0]['cart_subtotal']            = WC()->cart->display_prices_including_tax() ? WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() : WC()->cart->get_subtotal();
        $packages[0]['applied_coupons']          = WC()->cart->applied_coupons;
        $packages[0]['user']['ID']               = get_current_user_id();
        $packages[0]['destination']['country']   = $country;
        $packages[0]['destination']['state']     = $state;
        $packages[0]['destination']['postcode']  = $postcode;
        $packages[0]['destination']['city']      = $city;

        foreach ( WC()->cart->get_cart() as $item ) {
                if ( $item['data']->needs_shipping() ) {
                        if ( isset( $item['line_total'] ) ) {
                                $packages[0]['contents_cost'] += $item['line_total'];
                        }
                }
        }

        $packages = apply_filters( 'woocommerce_cart_shipping_packages', $packages );

        WC()->shipping->calculate_shipping( $packages );
    }

    public function alter_post_code($post_code, $country){
        /**
         * payment request button doesn't return complete postal code depending on countries to maintain privacy. This cause a validation error from WC, to prevent the validation we alter the partial postal code to match the postal code format
         */
        if ( 'GB' === $country ) {
            // Replaces a redacted string with something like LN10***.
            return str_pad( preg_replace( '/\s+/', '', $post_code ), 7, '*' );
        }
        if ( 'CA' === $country ) {
            // Replaces a redacted string with something like L4Y***.
            return str_pad( preg_replace( '/\s+/', '', $post_code ), 6, '*' );
        }

        return $post_code;
    }

    /**
	 * Get shipping method on address change.
	 *
     */
    public function eh_update_shipping_method() {

        if(!EH_Helper_Class::verify_nonce(EH_STRIPE_PLUGIN_NAME, '_eh_payment_request_update_shipping_nonce'))
        {
            wp_die(__('Access Denied', 'payment-gateway-stripe-and-woocommerce-integration'));
        }
        
        $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
        $shipping_method         = filter_input( INPUT_POST, 'shipping_method', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
        if ( is_array( $shipping_method ) ) {
            foreach ( $shipping_method as $i => $value ) {
                $chosen_shipping_methods[ $i ] = wc_clean( $value );
            }
        }

        WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

        WC()->cart->calculate_totals();

        $data           = array();
        $data          += $this->get_params_cc_payment_request();
        $data['result'] = 'success';

        wp_send_json( $data );
    }

    public function is_apple_pay_enabled(){
              
        if ( ( isset($this->eh_stripe_option['eh_stripe_apple_pay']) && ('yes' === $this->eh_stripe_option['eh_stripe_apple_pay']) ) && ((is_cart() && isset($this->eh_stripe_apple_pay_options) && in_array('cart', $this->eh_stripe_apple_pay_options))  || (is_checkout() && isset($this->eh_stripe_apple_pay_options) && in_array('checkout', $this->eh_stripe_apple_pay_options))  || (is_product() && isset($this->eh_stripe_apple_pay_options) && in_array('product', $this->eh_stripe_apple_pay_options)))) {
            return true;
        }else{
            return false;
        }
    }

    //adds apple pay button based on the settings
    public function add_apple_pay_button() {
        if($this->is_apple_pay_enabled()){

            if(! $this->eh_check_for_allowed_product()){
                return;
            }
        
            $color = $this->eh_stripe_option['eh_stripe_apple_color'];
            $split = '--OR--';
            if(isset($this->eh_stripe_option['eh_stripe_apple_pay_spiliter']))
            {
                $split = $this->eh_stripe_option['eh_stripe_apple_pay_spiliter'];
            }
           
            if ($this->eh_stripe_option['eh_stripe_apple_pay_position_checkout'] === 'below') {
                $below = '';
                $above = '<div class="apple-pay-spliter"><small>'.$split.'</small></div>';
            } else {
                $below = '<div class="apple-pay-spliter"><small>'.$split.'</small></div>';
                $above = '';
            }

            $desc = '';
            if ($this->eh_stripe_option['eh_stripe_apple_pay_description'] !== '') {
                $desc = '<div class="eh_apple_pay_description" ><small>-- ' . $this->eh_stripe_option['eh_stripe_apple_pay_description'] . ' --</small></div>';
            }

            $type = '';
            if(isset($this->eh_stripe_option['eh_stripe_apple_pay_type']))
            {
                $type = $this->eh_stripe_option['eh_stripe_apple_pay_type'];
            }
            
            $lang = '';
            if(isset($this->eh_stripe_option['eh_stripe_apple_pay_language']))
            {
                $lang = $this->eh_stripe_option['eh_stripe_apple_pay_language'];
            }
            
            $apple_button = '
                        <div class="apple-pay-button-div" style="display:none">
                                ' . $above . '
                                ' . $desc . '
                                <button class="apple-pay-button" lang="'.strtolower($lang).'" style="-webkit-appearance: -apple-pay-button; -apple-pay-button-type: '.$type.'; -apple-pay-button-style: '. $color .'"></button>
                                ' . $below . '
                            </div>';
            echo $apple_button;
        }
    }

    public function wt_stripe_gateway_available($enabled)
    {
        return true;
    }

}