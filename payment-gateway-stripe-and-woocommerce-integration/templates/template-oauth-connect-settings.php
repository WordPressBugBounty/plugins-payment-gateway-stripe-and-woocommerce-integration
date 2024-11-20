<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wtst-settings-outer wtst_init" >
    <div class="wtst-oauth-connect-container">
        <div class="wtst-connect-title"><?php esc_html_e("Connect your Stripe account", "payment-gateway-stripe-and-woocommerce-integration") ?></div>
        <div class="wtst-connect-img">
            <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH . 'assets/img/connect-img.svg') ?>">
        </div>
        <div class="wtst-connect-btn"><?php
        $mode = 'live';
        //Redirect customer to Live install link
        $install_link = Eh_Stripe_Admin_Handler::wt_get_install_link($mode);
            ?><a target="_blank" class="button-primary wtst-oauth" href="<?php  echo esc_url($install_link); ?>"><?php esc_html_e("Connect to Stripe", "payment-gateway-stripe-and-woocommerce-integration") ?></a>
        </div>        
        <div class="wtst-connect-btn">
            <a class="wtst-connect-later" href="#"><?php esc_html_e("Skip for now", "payment-gateway-stripe-and-woocommerce-integration") ?></a>
        </div>

    </div>

</div>