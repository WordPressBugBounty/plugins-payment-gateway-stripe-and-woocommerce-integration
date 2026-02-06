<?php

if (!defined('ABSPATH')) {
    exit;
}  

/**
 * EH_Stripe_Applepay class.
 *
 * @extends EH_Stripe_Payment
 */
#[\AllowDynamicProperties]
class EH_Stripe_Applepay extends EH_Stripe_Payment {

    public function __construct() {
		$this->id        = 'eh_stripe_pay';
        $this->init_form_fields();
        $this->init_settings();
	}

    public function init_form_fields() {
        
        $this->form_fields = array(

            'eh_stripe_apple_form_title' => array(
                'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Apple Pay','payment-gateway-stripe-and-woocommerce-integration' ).'<span>'),
                'type' => 'title',
                'description' => __('Accepts payments via Apple Pay.', 'payment-gateway-stripe-and-woocommerce-integration') .' <div class="wt_info_div"><p> '.__('To use Apple Pay, you need to register all your web domains that display the Apple Pay button with Apple and verify ownership of each domain.', 'payment-gateway-stripe-and-woocommerce-integration').' </p> <p>'.__('Steps to register:', 'payment-gateway-stripe-and-woocommerce-integration').'</p><ol><li>Register your domain with Apple.</li><ul class="wt_notice_bar_style"><li> '.__('To do this, navigate to <a href="https://dashboard.stripe.com/settings/payment_method_domains" target="_blank">Settings > Payments > Payment method domains </a> from your Stripe dashboard and add your domain. All domains, whether in production or testing, must be registered. Don’t register your domain more than once per account.', 'payment-gateway-stripe-and-woocommerce-integration').' </li></ul><li>Verify ownership of your domain with Apple Pay.</li><ul class="wt_notice_bar_style"><li>'.__('Download the <a href="https://stripe.com/files/apple-pay/apple-developer-merchantid-domain-association"> domain association file </a> and host it at /.well-known/apple-developer-merchantid-domain-association on your site. For example, if you’re registering https://example.com, make that file available at https://example.com/.well-known/apple-developer-merchantid-domain-association.', 'payment-gateway-stripe-and-woocommerce-integration').'</li></ul></ol>  <p> '.__('Payment methods are only available when you use a', 'payment-gateway-stripe-and-woocommerce-integration').' <a href="https://docs.stripe.com/elements/express-checkout-element#supported-browsers" target="_blank">'.__(' supported browser', 'payment-gateway-stripe-and-woocommerce-integration').'</a> and pay in a supported currency.</p> </div><p><a target="_blank" href="https://www.themehigh.com/docs/stripe-payment-plugin-for-woocommerce-free/#apple_pay"> '.__('Read documentation', 'payment-gateway-stripe-and-woocommerce-integration').' </a></p>',
            ),
            'eh_stripe_apple_pay_title' => array(
                'class'=> 'eh-css-class',
                'type' => 'title',
            ),
            'eh_stripe_apple_pay' => array(
                'title' => __('Apple Pay', 'payment-gateway-stripe-and-woocommerce-integration'),
                'label' => __('Enable', 'payment-gateway-stripe-and-woocommerce-integration'),
                'type' => 'checkbox',
                'description' => __('Enable Apple Pay to allow customers to make payments using their Apple Wallet.', 'payment-gateway-stripe-and-woocommerce-integration'),
                'default' => 'no',
                'desc_tip' => true
            ),

            'eh_stripe_apple_pay_options' => array(
                'title' => __('Show on pages', 'payment-gateway-stripe-and-woocommerce-integration'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'css' => 'width: 350px;',
                'desc_tip' => __('Select where to display the Apple Pay button.', 'payment-gateway-stripe-and-woocommerce-integration'),
                'options' => array(
                    'cart' => 'Cart page',
                    'checkout' => 'Checkout page',
                    'product' => 'Product page'
                ),
                'default' => array(
                    'checkout',
                    'cart',
                    'product'
                )
            ),


            'eh_stripe_apple_pay_style_title' => array(
                'type' => 'title',
                'class'=> 'eh-table-css-class',
                'title' => sprintf('<span style="font-weight: bold; font-size: 15px; color:#23282d;">'.__( 'Button Settings ','payment-gateway-stripe-and-woocommerce-integration' ).'</span> <a  class="thickbox" href="'.EH_STRIPE_MAIN_URL_PATH . 'assets/img/applepay_preview.png?TB_iframe=true&width=100&height=100"> <small> [Preview] </small> </a>'),
            ),

            'eh_stripe_apple_color' => array(
                'title'       => __( 'Color', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'Select the color for the Apple Pay button.', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'default'     => 'black',
                'desc_tip'    => true,
                'options'     => array(
                        'white' => __( 'White', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'black' => __( 'Black', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'white-outline' => __( 'White Outline', 'payment-gateway-stripe-and-woocommerce-integration' ),
                )
            ),
            'eh_stripe_apple_pay_type' => array(
                'title'       => __( 'Text', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'Select the text to display on the Apple Pay button.', 'payment-gateway-stripe-and-woocommerce-integration' ),
                'default'     => 'plain',
                'desc_tip'    => true,
                'options'     => array(
                        'plain'       => __( 'Plain', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'buy'       => __( 'Buy', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'add-money'    => __( 'Add Money', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'book'    => __( 'Book', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'check-out'    => __( 'Checkout', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'contribute'    => __( 'Contribute', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'donate'    => __( 'Donate', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'order'    => __( 'Order', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'reload'    => __( 'Reload','payment-gateway-stripe-and-woocommerce-integration' ),
                        'rent'    => __( 'Rent', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'support'    => __( 'Support', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'tip'    => __( 'Tip', 'payment-gateway-stripe-and-woocommerce-integration' ),
                        'top-up'    => __( 'Top up', 'payment-gateway-stripe-and-woocommerce-integration' ),
                )
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