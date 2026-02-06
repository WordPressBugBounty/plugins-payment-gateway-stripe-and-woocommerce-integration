<?php

if (!defined('ABSPATH')) {
    exit;
}  

/**
 * EH_Stripe_Payment_Request class.
 *
 * @extends EH_Stripe_Payment
 */
class EH_Stripe_Payment_Request extends EH_Stripe_Payment {

    public function __construct() {
		$this->id        = 'eh_stripe_pay';
        $this->init_form_fields();
        $this->init_settings();
	}

    public function init_form_fields() {
        
        $this->form_fields = array(
            'eh_payment_request_form_title' => array(
                'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Payment Request Button','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                'type' => 'title',
                /* translators: %1$s: "To use Google Pay," text, %2$s: Domain registration instructions, %3$s: HTTPS requirement text, %4$s: "Payment methods are only available when you use a" text, %5$s: "supported browser" text */
                'description' => sprintf(__( 'Google Pay allows customers to make payments using any credit or debit card saved to their Google Account. It works when customers have Google Pay set up on their devices <div class="wt_info_div"><p> %1$s </p><ol><li>Enable Google Pay in your Stripe <a href="https://dashboard.stripe.com/settings/payment_methods">payment methods settings</a>.</li><li>Register your domain</li><ul class="wt_notice_bar_style"><li> %2$s </li></ul><li>%3$s</li></ol>  <p> %4$s <a href="https://docs.stripe.com/elements/express-checkout-element#supported-browsers" target="_blank">%5$s</a> and pay in a supported currency.</p> </div>.<p> <a target="_blank" href="https://www.themehigh.com/docs/stripe-payment-plugin-for-woocommerce-free/#google_pay"> Read documentation </a></p> ','payment-gateway-stripe-and-woocommerce-integration' ), __('To use Google Pay,', 'payment-gateway-stripe-and-woocommerce-integration'), __('To do this, navigate to <a href="https://dashboard.stripe.com/settings/payment_method_domains"> Settings > Payments > Payment method domains </a> from your Stripe dashboard and add your domain. All domains, whether in production or testing, must be registered. Don\'t register your domain more than once per account.', 'payment-gateway-stripe-and-woocommerce-integration'), __('To accept Google Pay payments on the web, you need to serve from an HTTPS webpage with a TLS domain-validated certificate.', 'payment-gateway-stripe-and-woocommerce-integration'), __('Payment methods are only available when you use a ', 'payment-gateway-stripe-and-woocommerce-integration'), __(' supported browser', 'payment-gateway-stripe-and-woocommerce-integration')),

            ),
            'eh_payment_request_title' => array(
                'class'=> 'eh-css-class',
                'type' => 'title',
            ),
            'eh_payment_request' => array(
                'title'       => __( 'Google Pay', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'label'       => __( 'Enable', 'payment-gateway-stripe-and-woocommerce-integration' ), 
                'type'        => 'checkbox',
                'desc_tip'    => __( 'Enable to accept secure payments via Google Pay.', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'default'     => 'no',
            ),
            'eh_payment_request_button_enable_options' => array(
                'title' => __('Show on page', 'payment-gateway-stripe-and-woocommerce-integration'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'css' => 'width: 350px;',
                'desc_tip' => __('Select where to display the GPay button.', 'payment-gateway-stripe-and-woocommerce-integration'),
                'options' => array(
                    'product' => 'Product page',
                    'cart' => 'Cart page',
                    'checkout' => 'Checkout page',
                ),
                'default' => array(
                    'product',
                    'cart',
                    'checkout'
                )
            ),
            'eh_payment_request_style_title' => array(
                'type' => 'title',
                'class'=> 'eh-table-css-class',
                'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Button Settings','payment-gateway-stripe-and-woocommerce-integration' ).'<span> <a  class="thickbox" href="'.EH_STRIPE_MAIN_URL_PATH . 'assets/img/googlepay_preview.png?TB_iframe=true&width=100&height=100"> <small> [Preview] </small> </a>'),
            ),

            'eh_payment_request_button_type' => array(
                'title'       => __( 'Type', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'Select the desired button type to customize your Google Pay experience.', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'default'     => 'buy',
                'desc_tip'    => true,
                'options'     => array(
                    'book' => __( 'Book', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'buy'     => __( 'Buy', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'checkout' => __( 'Checkout', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'donate'  => __( 'Donate', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'order' => __( 'Order', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'pay' => __( 'Pay', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'plain' => __( 'Plain', 'payment-gateway-stripe-and-woocommerce-integration' ),

                ),
            ),
            'eh_payment_request_button_theme' => array(
                'title'       => __( 'Theme', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'Select a theme to customize the appearance of the payment button.', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'default'     => 'black',
                'desc_tip'    => true,
                'options'     => array(
                    'black'          => __( 'Black', 'payment-gateway-stripe-and-woocommerce-integration' ),
                    'white'         => __( 'White', 'payment-gateway-stripe-and-woocommerce-integration' ),
                ),
            ),

           
        );
    }
    public function admin_options() {
    
        parent::admin_options();
    }

    public function process_admin_options(){
        
        parent::process_admin_options();
    }

}