<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles admin side.
 * @since 3.3.4
 * 
 */
class Eh_Stripe_Admin_Handler  {

    /**
	 * Constructor
	 */
	public function __construct() { 
      
        add_action('admin_menu', array($this,'register_stripe_menu_page'));
        add_filter( 'woocommerce_screen_ids', array($this,'add_eh_screen_id' ));
        add_action('admin_enqueue_scripts', array($this,'register_admin_scripts'));
        add_action( 'admin_notices', array( $this, 'eh_menu_admin_notices'), 5 );
        add_action('init', 'add_thickbox' );
        //add_action('admin_notices', array($this, 'add_wt_stripe_notice' ), 99);
        add_action('wp_ajax_wtst_oauth_connect_later', array($this, 'wtst_oauth_connect_later' ));
        add_action('wp_ajax_wtst_oauth_disconnect', array($this, 'wtst_oauth_disconnect' ));
        add_action('wp_ajax_wtst_dismiss_oauth_notice', array($this, 'wtst_dismiss_oauth_notice' ));
        add_action('wp_ajax_wtst_dismiss_sofort_notice', array($this, 'wtst_dismiss_sofort_notice' ));
        add_action('after_plugin_row_payment-gateway-stripe-and-woocommerce-integration/payment-gateway-stripe-and-woocommerce-integration.php', array($this, 'wt_oauth_upgrade_notice'), 10, 3);

        add_action('in_plugin_update_message-payment-gateway-stripe-and-woocommerce-integration/payment-gateway-stripe-and-woocommerce-integration.php', array($this, 'wt_stripe_upgrade_notice'), 10, 2);

    }

    /**
     * To notify about oAuth app migration inside upgrade notice
     * 
     * */
    public function wt_oauth_upgrade_notice($plugin_file, $plugin_data, $context) { 

        if(true !== Eh_Stripe_Admin_Handler::wtst_oauth_compatible() && !Eh_Stripe_Admin_Handler::wtst_new_installation()){
            echo sprintf(__('%1$sCritical update!%2$sWe are enhancing security and require you to switch from API keys to a more %3$ssecure mode of authentication%4$s for connecting with Stripe account. OAuth provides better control and limits access to only the necessary data, protecting your business from unauthorized access.%5$sUpdate to 4.0.0 to re-authenticate your stripe connection.%6$s', 'payment-gateway-stripe-and-woocommerce-integration'), '<tr class="plugin-update-tr installer-plugin-update-tr wt-cli-plugin-inline-notice-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="update-message notice notice-error inline wt-oauth-notice-section"><h2>', '</h2><p>', '<a href="https://support.stripe.com/questions/plugin-user-migration-guide">', '</a>', '</p><p>', '</p></div></td></tr>');
        }
    }

    /**
	 * register admin scripts.
     * @since 3.3.6
	 */
    public function register_admin_scripts(){
       
        wp_register_style('eh-stripe-admin-style', EH_STRIPE_MAIN_URL_PATH . '/assets/css/eh-admin.css',array(),EH_STRIPE_VERSION);
        wp_enqueue_style('eh-stripe-admin-style');

    }
  
    /**
	 * register menu options.
     * @since 3.3.4
	 */
    function register_stripe_menu_page() {
        
        add_menu_page(
            __( 'WebToffee Stripe','payment-gateway-stripe-and-woocommerce-integration' ), 
            __( 'WebToffee Stripe','payment-gateway-stripe-and-woocommerce-integration' ), 
            'manage_options', 
            'wt_stripe_menu',
            null,
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="30" height="25" viewBox="0 0 40 40">
            <path fill="black" d="M19.5996094 7.33078125C19.6834375 7.33078125 19.7680469 7.31859375 19.8502344 7.29171875 21.1832813 6.87101562 22.5951563 6.66671875 24.1666406 6.66671875 25.5948437 6.66671875 26.8847656 6.83195312 28.1095312 7.17210937 28.5164063 7.286875 28.9713281 6.98898437 29.1365625 6.60976562 29.8103906 5.06921875 30.4459375 3.615 30.4459375 2.64820312 30.4459375 1.32984375 29.6272656.000078125 27.7970312.000078125 26.5714063.000078125 26.1157031.578671875 25.8675 1.07507812 25.8544531 1.06039062 25.8414844 1.045 25.8292188 1.03109375 25.4882031.6453125 24.9177344-7.10542736e-15 23.5611719-7.10542736e-15 22.0344531-7.10542736e-15 21.0432812 1.33140625 20.5639062 2.42023437 18.8809375 2.50328125 17.8873437 3.254375 17.8873437 4.46367187 17.8873437 5.15539062 18.270625 5.92117187 18.8883594 6.93195312 19.0429688 7.18515625 19.315625 7.33078125 19.5996094 7.33078125zM15.0382812 22.5774219C16.5714844 22.7458594 17.7857031 23.2846094 19.003125 23.8721875 19.94875 24.3278906 20.9261719 24.7991406 22.1639062 25.0505469 23.9990625 25.4224219 27.4145312 26.115 29.0503125 28.1153125 29.2114844 28.3122656 29.4490625 28.4213281 29.695625 28.4213281 29.7647656 28.4213281 29.8339844 28.4132031 29.903125 28.3953125 32.3347656 27.7710937 34.1316406 27.4065625 35.2001562 27.5139844L35.4426562 27.5327344C35.4614062 27.534375 35.4792969 27.5335156 35.4971875 27.5335156 35.8845312 27.5709375 36.3467969 27.1664844 36.3467969 26.7001562 36.3467969 26.5357812 36.29875 26.3819531 36.2165625 26.2525781 35.5492187 24.0732031 34.3260937 21.9833594 33.9208594 21.3225781 33.8875 13.5157812 32.1809375 8.33351562 24.1666406 8.33351562 16.1514844 8.33351562 14.4457812 13.5166406 14.4124219 21.3250781 14.27 21.5659375 14.2578125 21.8621875 14.3807031 22.1144531 14.5060156 22.3714844 14.7542187 22.545625 15.0382812 22.5774219zM24.1666406 19.1667188C22.7880469 19.1667188 21.6666406 18.0453125 21.6666406 16.6667188 21.6666406 15.5817188 22.3653906 14.6653906 23.3332813 14.3202344L23.3332813 14.1667188C23.3332813 13.7060938 23.7060156 13.3333594 24.1666406 13.3333594 24.6272656 13.3333594 25 13.7060938 25 14.1667188L25.8333594 14.1667188C26.2939844 14.1667188 26.6667187 14.5394531 26.6667187 15.0000781 26.6667187 15.4607031 26.2939844 15.8334375 25.8333594 15.8334375L24.1667187 15.8334375C23.7069531 15.8334375 23.3333594 16.2069531 23.3333594 16.6667969 23.3333594 17.1265625 23.706875 17.5001563 24.1667187 17.5001563 25.5453125 17.5001563 26.6667187 18.6215625 26.6667187 20.0001563 26.6667187 21.0851562 25.9679687 22.0014844 25.0000781 22.3466406L25.0000781 22.5001563C25.0000781 22.9607813 24.6273437 23.3335156 24.1667187 23.3335156 23.7060937 23.3335156 23.3333594 22.9607812 23.3333594 22.5001563L22.5 22.5001563C22.039375 22.5001563 21.6666406 22.1274219 21.6666406 21.6667969 21.6666406 21.2061719 22.039375 20.8334375 22.5 20.8334375L24.1666406 20.8334375C24.6264062 20.8334375 25 20.4599219 25 20.0000781 25 19.5403125 24.6264844 19.1667188 24.1666406 19.1667188zM7.78320312 23.0403125L.99609375 21.6828906C.75359375 21.6340625.498046875 21.6975781.304375 21.8553906.112265625 22.0140625 0 22.2500781 0 22.4999219L0 35.8332812C0 36.2939063.372734375 36.6666406.833359375 36.6666406L6.0546875 36.6666406C7.29164062 36.6666406 8.35609375 35.74375 8.5303125 34.520625L9.76890625 25.8454687C9.95609375 24.5319531 9.0853125 23.2998437 7.78320312 23.0403125z"/>
            <path fill="black" d="M35,29.1665625 C33.9595312,29.1665625 32.2047656,29.5375 30.6270312,29.9333594 C30.2407031,30.0303125 29.9907031,30.385 29.9994531,30.783125 C29.9998437,30.7997656 30,30.8164062 30,30.8332031 C30,32.09375 29.445,33.2005469 28.4391406,33.9517187 C27.4642188,34.6792969 26.2125781,35.0185938 24.5003125,35.0185938 C22.2578125,35.0185938 19.2059375,34.4378906 15.1692969,33.2441406 C14.7526562,33.1209375 14.4582031,32.7067969 14.5396875,32.28 C14.6335937,31.7878125 15.1303125,31.4930469 15.5957031,31.6322656 C19.4466406,32.7732031 22.4414062,33.3517969 24.5003125,33.3517969 C26.2532813,33.3517969 27.0198437,32.931875 27.4430469,32.6153125 C28.0257031,32.1807031 28.3333594,31.5646875 28.3333594,30.8330469 C28.3333594,30.6932812 28.3078906,30.5674219 28.2902344,30.4371094 C27.9747656,28.1364062 24.9453906,27.3149219 21.8327344,26.6826562 C20.3874219,26.3896875 19.3115625,25.8713281 18.2715625,25.3691406 C16.9889844,24.750625 15.7780469,24.1663281 14.1667188,24.1663281 C13.5278906,24.1663281 12.8532031,24.2128125 12.160625,24.3042187 C11.7232813,24.361875 11.4076563,24.7540625 11.440625,25.1939062 C11.4648438,25.5169531 11.4582813,25.8067969 11.4176563,26.0811719 L10.1790625,34.75875 C10.1676563,34.836875 10.1448438,34.9101562 10.1253125,34.9841406 C10.1236719,34.9914844 10.0829688,35.1444531 10.0813281,35.1509375 C9.9853125,35.5423437 10.1822656,35.946875 10.5500781,36.11125 C14.5149219,37.8853125 19.8078906,39.9996094 22.4999219,39.9996094 C25.7323437,39.9996094 32.3876562,36.728125 36.7903125,34.5634375 C37.9267187,34.0049219 38.8684375,33.5413281 39.4840625,33.2707812 C39.7823438,33.1397656 39.99625,32.8557812 39.9999308,32.53 C40.0211719,30.5511719 37.9595312,29.1665625 35,29.1665625 Z"/>
            </svg>'),57
        );
        add_submenu_page( 
            'wt_stripe_menu',
            __( 'General Settings','payment-gateway-stripe-and-woocommerce-integration' ), 
            __( 'General Settings','payment-gateway-stripe-and-woocommerce-integration' ),
            'manage_options', 
            'wt_stripe_menu', 
            array( $this, 'eh_stripe_menu_page' )
        );
       
        $eh_stripe = get_option("woocommerce_eh_stripe_pay_settings");
        
        if(isset($eh_stripe['overview'])){
            if ('yes' === $eh_stripe['overview']) {

                add_submenu_page('wt_stripe_menu',
                    __( 'Stripe Overview','payment-gateway-stripe-and-woocommerce-integration' ),
                    __( 'Stripe Overview','payment-gateway-stripe-and-woocommerce-integration' ),
                    'manage_options', 'eh-stripe-overview',
                    array('EH_Stripe_Overview','eh_stripe_template_display')
                );
            }
        }
       
    }
    /**
	 * Adds admin notice with useful links when plugin is not enabled.
     * @since 3.3.6
	 */
    function eh_menu_admin_notices(){

        //makes admin notice dismissible
        $dismiss_notice = filter_input( INPUT_GET, 'dismiss_notice', FILTER_SANITIZE_NUMBER_INT );
        
        $eh_stripe = get_option("woocommerce_eh_stripe_pay_settings");

		if ( $dismiss_notice ) {
			update_option( 'notice_dismissed', true );
        }
        
        $notice_dismissed = get_option( 'notice_dismissed' );


        // adds saved changes message when form data is submitted
        if(isset($_GET['msg']) && 1 == $_GET['msg']){
            echo  '<div class="notice notice-success"> <p>'.__( "Your settings have been saved.", "payment-gateway-stripe-and-woocommerce-integration" ).'</p> </div>';
        }
        

        if(isset($_REQUEST['oauth_error']) && !empty($_REQUEST['oauth_error'])){
            $message = 'An error occurred while connecting to Stripe. <strong>'. esc_html($_REQUEST['oauth_error'] ).'.</strong>';
            echo "<div class='notice notice-error is-dismissible' style='background-color: #ffd5d6;'><p>$message</p></div>";            
        }

        //Banner to notify connect to Stripe app

        if(true !== Eh_Stripe_Admin_Handler::wtst_oauth_compatible() && !Eh_Stripe_Admin_Handler::wtst_new_installation()){
            $mode = isset($eh_stripe['eh_stripe_mode']) ? $eh_stripe['eh_stripe_mode'] : 'live'; 
            $install_link = Eh_Stripe_Admin_Handler::wt_get_install_link($mode);


            $message = sprintf(__('%1$sUrgent: Switch to OAuth for Secure Stripe Integration%2$sWe are enhancing security and requires you to switch from using API keys to OAuth 2.0 for connecting with Stripe account. OAuth provides better control and limits access to only the necessary data, protecting your business from unauthorized access. <p>Ensure to connect your Stripe account using the new authentication method before the year ends.</p> %3$sClick %4$s‘Connect Now’%5$s to update your integration today! Need help? Check out our %6$sintegration article%7$s. Please upgrade soon to avoid service disruptions.%8$sConnect Now%9$s', 'payment-gateway-stripe-and-woocommerce-integration'), '<p><h2>', '</h2>', '</p><p>', '<b>', '</b>', '<a href="https://www.webtoffee.com/switch-stripe-integration-oauth/" style="text-decoration: none;">', '</a>', '</p><p><a href="' . esc_url($install_link) .'" class="button button-primary">', '</a></p>');
            
            $sofort_message = sprintf(__('%1$sUpdate: SOFORT Payments Discontinued%2$sStarting %3$sNovember 29, 2024,%4$s %5$sbusinesses will no longer be able to accept SOFORT payments%6$s as it is being consolidated into Klarna. To continue offering bank transfer payments, please switch to %3$sKlarna’s "Pay Now"%4$s option or other methods like %3$sSEPA Direct Debit%4$s. Any existing SEPA Direct Debit mandates will remain active.', 'payment-gateway-stripe-and-woocommerce-integration'), '<p><h2>', '</h2>', '<b>', '</b>','<a  style="text-decoration: none;" href="https://support.stripe.com/questions/sofort-is-being-consolidated-into-klarna-and-discontinued-as-a-standalone-payment-method">', '</a>', '</p><p><a href="' . esc_url($install_link) .'" class="button button-primary">', '</a></p>');
            
            if(isset($_GET['page']) &&  'wt_stripe_menu' === sanitize_text_field($_GET['page'])){
                //echo wp_kses_post("<div class='notice notice-error' style='background-color: #ffd5d6;'><p>$message</p></div>");   

                if (false === get_transient('wtst_dismiss_sofort_notice')) {        
                    echo wp_kses_post("<div class='notice notice-warning is-dismissible' id='sofort-notice'><p>$sofort_message</p></div>"); 
                }           

            }
            else{
                if (false === get_transient('wtst_dismiss_oauth_notice') && 3 !== get_transient("wtst_oauth_notice_dismissable_count")) {
                    echo wp_kses_post("<div class='notice notice-warning is-dismissible' id='wtst-oauth-notice' ><p>$message</p></div>"); 
                }           

            } 

            ?><script type="text/javascript">
                jQuery("#wtst-oauth-notice").on("click", '.notice-dismiss', function () { 
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wtst_dismiss_oauth_notice'
                        },
                      
                    });
                });

                jQuery("#sofort-notice").on("click", '.notice-dismiss', function () { 
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wtst_dismiss_sofort_notice'
                        },
                      
                    });
                });

                
            </script><?php           

        }

    }
    
    /**
	 * Renders stripe settings page
     * @since 3.3.6
	 */
    public function eh_stripe_menu_page(){

        $nonce = wp_create_nonce( 'eh_stripe_oauth_connect' );
        wp_enqueue_script('eh_stripe_oauth', EH_STRIPE_MAIN_URL_PATH .'includes/admin/js/wtst-admin.js',array(),EH_STRIPE_VERSION,true);
        $stripe_params['nonce'] = $nonce;
        $stripe_params['mode_change_text'] = __("Switching to test mode disables live payments. Switch to live mode to receive payments.", "payment-gateway-stripe-and-woocommerce-integration");
        $stripe_params['mode_change_primary_btn'] = __("Switch to test mode", "payment-gateway-stripe-and-woocommerce-integration");

        $stripe_params['disconnect_primary_btn_title'] = __("Disconnect", "payment-gateway-stripe-and-woocommerce-integration");
        $stripe_params['disconnect_secondary_btn_title'] = __("Cancel", "payment-gateway-stripe-and-woocommerce-integration");
        $stripe_params['disconnect_title'] = __("Are you sure?", "payment-gateway-stripe-and-woocommerce-integration");
        $stripe_params['disconnect_text'] = __("Disconnecting your Stripe account from WebToffee stops payments. To fully remove the WebToffee app, head to 'Installed apps' in your Stripe dashboard.", "payment-gateway-stripe-and-woocommerce-integration");
        wp_localize_script('eh_stripe_oauth', 'eh_stripe_oauth_val',  $stripe_params);

        $stripe_settings = get_option( 'woocommerce_eh_stripe_pay_settings' );
        $mode = (!empty($stripe_settings) && isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live');

        //For new installation, if the user not clicked connect later and oauth authentication is pending for both live and test mode render the UI
        if(self::wtst_new_installation() && "yes" != get_transient("wtst_oauth_connect_later") && (!self::wtst_oauth_compatible('test') && !self::wtst_oauth_compatible('live'))){

            include_once(EH_STRIPE_MAIN_PATH . "/templates/template-oauth-connect-settings.php");
        }
        // Render the UI for existing users
        else{
            //Setting page if oAuth authentication completed, or the user clicks I'll do later button
            //if(self::wtst_oauth_compatible() || "yes" === get_transient("wtst_oauth_connect_later")){
                ?><div class="wtst_wrap">

                    <h2 style="width: 100%;"><?php _e('Stripe payment','payment-gateway-stripe-and-woocommerce-integration'); ?></h2><?php
                    $webtoffee_logo='&nbsp;&nbsp;<img src="'.EH_STRIPE_MAIN_URL_PATH.'assets/img/wt_logo.png" style="" />&nbsp;';

                    ?><div class="wfte_branding">
                        <div class="wfte_branding_label"><?php _e('Developed by', 'payment-gateway-stripe-and-woocommerce-integration'); print $webtoffee_logo;?>
                        </div>
                        <!-- <div style="width: 100%; padding: 5px;">
                            <?php echo $webtoffee_logo; ?>
                        </div> -->
                    </div>

                    <?php
                    if( isset( $_GET[ 'tab' ] ) ) {
                        $active_tab = $_GET[ 'tab' ];
                    } else{
                        $active_tab = 'general_settings';
                    }

                    $arr_local_gateways = array('local', 'alipay', 'sepa', 'klarna', 'afterpay', 'wechat', 'ideal', 'bancontact', 'eps', 'p24', 'bacs', 'becs', 'fpx', 'boleto', 'oxxo', 'grabpay', 'affirm');
                    ?>
                        <h2 class="nav-tab-wrapper eh-nav-tab">
                            <a href="?page=wt_stripe_menu&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'eh-nav-tab-active stripe' : 'stripe'; ?>"><?php  _e('General Settings','payment-gateway-stripe-and-woocommerce-integration'); ?></a>
                            <a href="?page=wt_stripe_menu&tab=credit_card" class="nav-tab <?php echo $active_tab == 'credit_card' ? 'eh-nav-tab-active stripe' : 'stripe'; ?>"><?php  _e('Credit/Debit Cards','payment-gateway-stripe-and-woocommerce-integration'); ?></a>
                            <a href="?page=wt_stripe_menu&tab=applepay" class="nav-tab <?php echo $active_tab == 'applepay' ? 'eh-nav-tab-active stripe' : 'stripe'; ?>"><?php  _e('Apple Pay','payment-gateway-stripe-and-woocommerce-integration'); ?></a>
                            <a href="?page=wt_stripe_menu&tab=payment_request" class="nav-tab <?php echo $active_tab == 'payment_request' ? 'eh-nav-tab-active stripe' : 'stripe'; ?>"><?php  _e('G Pay/Payment Request Button','payment-gateway-stripe-and-woocommerce-integration'); ?></a>
                             <a href="?page=wt_stripe_menu&tab=checkout" class="nav-tab <?php echo $active_tab == 'checkout' ? 'eh-nav-tab-active stripe' : 'stripe'; ?>"><?php  _e('Stripe Checkout','payment-gateway-stripe-and-woocommerce-integration'); ?></a>
                            <a href="?page=wt_stripe_menu&tab=alipay" class="nav-tab <?php echo (in_array($active_tab, $arr_local_gateways)) ? 'eh-nav-tab-active stripe' : 'stripe'; ?>"><?php  _e('Local Gateways','payment-gateway-stripe-and-woocommerce-integration'); ?></a>

                          <a href="?page=wt_stripe_menu&tab=help_tab" class="nav-tab <?php echo $active_tab == 'help_tab' ? 'eh-nav-tab-active stripe' : 'stripe'; ?>"><?php  _e('Help Guide','payment-gateway-stripe-and-woocommerce-integration'); ?></a>
                        </h2>
                       


                        <?php
                            if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab'];
                            else $tab = 'general_settings';

                            ?> <div class="eh_settings_left"><?php
                            switch ( $tab ){
                                case 'general_settings' :
                                    
                                    $stripe_settings = get_option( 'woocommerce_eh_stripe_pay_settings' );
                                    $mode = (!empty($stripe_settings) && isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live');

                                    //Setting page if oAuth authentication not completed
                                    if(!self::wtst_oauth_compatible($mode)){ 
                                        $install_link = Eh_Stripe_Admin_Handler::wt_get_install_link($mode);
                                        /*Connect banner*/
                                        if(self::wtst_new_installation()){
                                            ?><!-- <div class="wtst-oauth-banner">
                                                
                                                
                                                <div class="wtst-oauth-banner-img" style="background-image: url( <?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/oauth-banner.svg'); ?>); "></div>
                                                <div class="wtst-oauth-banner-container">
                                                    <div><?php  echo esc_html_e("You haven’t connected your Stripe account yet. Connect now to start receiving payments", "payment-gateway-stripe-and-woocommerce-integration") ?></div>
                                                    <div><a target="_blank" class="button-primary wtst-oauth" href="<?php  echo esc_url($install_link); ?>"><?php esc_html_e("Connect to Stripe", "payment-gateway-stripe-and-woocommerce-integration") ?></a></div>
                                                </div>
                                               
                                            </div> --> <?php
                                        }
                                        else{
                                            $message = sprintf(__('%1$sUrgent: Switch to OAuth for Secure Stripe Integration%2$sWe are enhancing security and requires you to switch from using API keys to OAuth 2.0 for connecting with Stripe account. OAuth provides better control and limits access to only the necessary data, protecting your business from unauthorized access. <p>Ensure to connect your Stripe account using the new authentication method before the year ends.</p> %3$sClick %4$s‘Connect Now’%5$s to update your integration today! Need help? Check out our %6$sintegration article%7$s. Please upgrade soon to avoid service disruptions.%8$sConnect Now%9$s', 'payment-gateway-stripe-and-woocommerce-integration'), '<p><h2>', '</h2>', '</p><p>', '<b>', '</b>', '<a href="https://www.webtoffee.com/switch-stripe-integration-oauth/" style="text-decoration: none;">', '</a>', '</p><p><a href="' . esc_url($install_link) .'" class="button button-primary">', '</a></p>');
                                            echo wp_kses_post("<div class='wtst-notice wtst-notice-error ' ><div style='padding:10px'>$message</div></div>");
                                        }
                                    }
                                    ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                        <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                        
                                        <?php
                                        WC_Admin_Settings::get_settings_pages(); 
                                        $obj = new EH_Stripe_General_Settings();
                                        if( ! empty( $_POST ) ) {
                                        
                                            $obj->process_admin_options();
                                            wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                        }
                                        $obj->admin_options();

                                        //wp_enqueue_style( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array(), '10' );
                                        wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array( 'jquery' ), '10', true );                                        
                                        wc_enqueue_js("
                                            $('#woocommerce_eh_stripe_pay_eh_stripe_mode').closest('tr').hide();
                                            $('.description').css({'font-style':'normal'});
                                            $('.description').css({'font-style':'normal'});
                                            $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                        
                                            jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_test_secret_key, #woocommerce_eh_stripe_pay_eh_stripe_live_secret_key ' ).attr('autocomplete','new-password');

                                            var test    = jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_test_publishable_key, #woocommerce_eh_stripe_pay_eh_stripe_test_secret_key' ).closest( 'tr' ),
                                            live = jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_live_publishable_key, #woocommerce_eh_stripe_pay_eh_stripe_live_secret_key' ).closest( 'tr' );

                                            jQuery( '#eh_test_mode' ).on( 'click', function() {
                                                var test    = jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_test_publishable_key, #woocommerce_eh_stripe_pay_eh_stripe_test_secret_key' ).closest( 'tr' ),
                                                live = jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_live_publishable_key, #woocommerce_eh_stripe_pay_eh_stripe_live_secret_key' ).closest( 'tr' );                                        
                                                /*test.hide();
                                                live.show();
                                                jQuery('#eh_test_mode').hide();                                   
                                                jQuery('#eh_live_mode').show(); */                                     
                                                jQuery('#woocommerce_eh_stripe_pay_eh_stripe_mode').val('live');
                                                jQuery('.eh_mainform').submit();

                                            });                                          


                                            if('test' === jQuery('#woocommerce_eh_stripe_pay_eh_stripe_mode').val()){ 
                                                test.show(); 
                                                live.hide();  
                                                jQuery('#eh_test_mode').show();  
                                                jQuery('#eh_live_mode').hide();                                                
                                            }
                                            else{ 
                                                test.hide();
                                                live.show(); 
                                                jQuery('#eh_test_mode').hide();                                   
                                                jQuery('#eh_live_mode').show();                                               
                                            }

                                           
                                        ");

                                        ?>
                                        <p class="submit">
                                            <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                            <?php endif; ?>
                                            
                                        </p>
                                    </form>

                                    <style type="text/css">
                                        .wtst-connect::after {
                                            background-image: url(<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/green-tick.svg'); ?>);
                                        }
                                    </style><?php
                                    

                                break;

                                case 'credit_card' :
                                    ?>

                                        <form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                            <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                            
                                            <?php
                                            WC_Admin_Settings::get_settings_pages(); 
                                            if( ! empty( $_POST ) ) {
                                            
                                                $gateways = WC()->payment_gateways()->payment_gateways();
                                                $gateways[ 'eh_stripe_pay']->process_admin_options();
                                                wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                            }
                                            
                                            $obj = new EH_Stripe_Payment();
                                            
                                            $obj->admin_options();
                                            
                                            wc_enqueue_js("
                                                $('.description').css({'font-style':'normal'});
                                                $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                                jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_statement_descriptor, #woocommerce_eh_stripe_pay_eh_stripe_live_secret_key ' ).attr('maxlength','22');
                                            ");

                                            ?>
                                            <p class="submit">
                                                <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                    <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                                <?php endif; ?>
                                                
                                            </p>
                                        </form>
                                    <?php
                                break;

                                case 'applepay' :
                                    ?>
            
                                        <form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                            <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                            
                                            <?php
                                            WC_Admin_Settings::get_settings_pages();
                                            $obj = new EH_Stripe_Applepay(); 
                                            if( ! empty( $_POST ) ) {
                                               
                                                $obj->process_admin_options();
                                                wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                            }
                                            
                                            $obj->admin_options();
                                            
                                            wc_enqueue_js("
                                                $('.description').css({'font-style':'normal'});
                                                $('.eh-desp-class').css({'font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                                $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                           ");

                                            ?>
                                            <p class="submit">
                                                <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                    <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                                <?php endif; ?>
                                                
                                            </p>
                                        </form>
                                    <?php
                                     
                                break;   

                                case 'payment_request' :
                                    ?>
            
                                        <form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                            <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                            
                                            <?php
                                            WC_Admin_Settings::get_settings_pages();
                                            $obj = new EH_Stripe_Payment_Request(); 
                                            if( ! empty( $_POST ) ) {
                                               
                                                $obj->process_admin_options();
                                                wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                            }
                                            
                                            $obj->admin_options();
                                            
                                            wc_enqueue_js("
                                                $('.description').css({'font-style':'normal'});
                                                $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                                $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                           ");

                                            ?>
                                            <p class="submit">
                                                <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                    <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                                <?php endif; ?>
                                                
                                            </p>
                                        </form>
                                    <?php
                                     
                                break; 

                                case 'alipay' :

                                    $this->eh_local_gateways();
                                    ?>

                                        <form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                            <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                            
                                            <?php
                                            WC_Admin_Settings::get_settings_pages(); 
                                            if( ! empty( $_POST ) ) {
                                            
                                                $gateways = WC()->payment_gateways()->payment_gateways();
                                                $gateways[ 'eh_alipay_stripe']->process_admin_options();
                                                wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                            }
                                            
                                            $obj = new EH_Alipay_Stripe_Gateway();
                                            
                                            $obj->admin_options();

                                            wc_enqueue_js("
                                                $('.description').css({'font-style':'normal'});
                                                $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                                $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                           ");
                                            
                                            
                                            ?>
                                            <p class="submit">
                                                <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                    <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                                <?php endif; ?>
                                                
                                            </p>
                                        </form>
                                    <?php
                                    
                                break;   
                                case 'checkout' :
                                    ?>

                                        <form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                            <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                            
                                            <?php
                                            WC_Admin_Settings::get_settings_pages(); 
                                            if( ! empty( $_POST ) ) {
                                            
                                                $gateways = WC()->payment_gateways()->payment_gateways();
                                                $gateways[ 'eh_stripe_checkout']->process_admin_options();
                                                wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                            }
                                            $obj = new Eh_Stripe_Checkout();
                                            
                                            $obj->admin_options();
                                            wc_enqueue_js("
                                            $('.description').css({'font-style':'normal'});
                                            $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                            $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                            var billing_adr_tr = $('#woocommerce_eh_stripe_checkout_eh_collect_billing').closest('fieldset');
                                            $('#woocommerce_eh_stripe_checkout_eh_collect_shipping').closest('fieldset').contents().insertBefore($(billing_adr_tr).find('br'));
                                             
                                             $('.eh-stripe-address-title').css({'font-size': '14px', 'font-weight' : '400'});   
                                              $('#woocommerce_eh_stripe_checkout_eh_collect_shipping').css({'margin-left': '75px'});
                                            ");
                                            
                                            ?>
                                            <p class="submit">
                                                <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                    <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                                <?php endif; ?>
                                                
                                            </p>
                                        </form>
                                    <?php
                                break; 

                                case 'sepa' :
                                   $this->eh_local_gateways();
                                    ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                        <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                        
                                        <?php
                                        WC_Admin_Settings::get_settings_pages(); 
                                        if( ! empty( $_POST ) ) {
                                        
                                            $gateways = WC()->payment_gateways()->payment_gateways();
                                            $gateways[ 'eh_sepa_stripe']->process_admin_options();
                                            wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                        }
                                        
                                        $obj = new EH_Sepa_Stripe_Gateway();
                                        
                                        $obj->admin_options();

                                        wc_enqueue_js("
                                            $('.description').css({'font-style':'normal'});
                                            $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                            $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                       ");
                                        
                                        
                                        ?>
                                        <p class="submit">
                                            <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                            <?php endif; ?>
                                            
                                        </p>
                                    </form> <?php
                                        
                                    break; 


                                case 'klarna' :
                                    $this->eh_local_gateways();
                                    ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                        <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                        
                                        <?php
                                        WC_Admin_Settings::get_settings_pages(); 
                                        if( ! empty( $_POST ) ) {
                                        
                                            $gateways = WC()->payment_gateways()->payment_gateways();
                                            $gateways['eh_klarna_stripe']->process_admin_options();
                                            wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                        }
                                        
                                        $obj = new EH_Klarna_Gateway();
                                        
                                        $obj->admin_options();

                                        wc_enqueue_js("
                                            $('.description').css({'font-style':'normal'});
                                            $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                            $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                       ");
                                        
                                        
                                        ?>
                                        <p class="submit">
                                            <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                            <?php endif; ?>
                                            
                                        </p>
                                    </form> <?php
                                        
                                    break; 
                                    
                                    case 'help_tab' :
                                        ?>
                                             <form method="post" class="eh_mainform" action="" enctype="multipart/form-data">   
                                                 <div class="eh-tab-content">
                                    
                                                     <div class="eh_sub_tab_container">		
                                                         <div class="eh_sub_tab_content" data-id="help-links" style="display:block;">
                                                             <h3><?php _e('Help Links','payment-gateway-stripe-and-woocommerce-integration'); ?></h3>
                                                             <ul class="eh-help-links">
                                                                 <li>
                                                                     <img src="<?php echo EH_STRIPE_MAIN_URL_PATH;?>assets/img/documentation.png">
                                                                     <h3><?php _e('Documentation','payment-gateway-stripe-and-woocommerce-integration'); ?></h3>
                                                                     <p><?php _e('Refer to our documentation to set up and get started.','payment-gateway-stripe-and-woocommerce-integration'); ?></p>
                                                                     <a target="_blank" href="https://www.webtoffee.com/woocommerce-stripe-payment-gateway-plugin-user-guide/" class="button button-primary">
                                                                         <?php _e('Documentation','payment-gateway-stripe-and-woocommerce-integration'); ?>        
                                                                     </a>
                                                                 </li>
                                                                 <li>
                                                                     <img src="<?php echo EH_STRIPE_MAIN_URL_PATH;?>assets/img/support.png">
                                                                     <h3><?php _e('Support','payment-gateway-stripe-and-woocommerce-integration'); ?></h3>
                                                                     <p><?php _e('We would love to help you on any queries or issues.','payment-gateway-stripe-and-woocommerce-integration'); ?></p>
                                                                     <a target="_blank" href="https://wordpress.org/support/plugin/payment-gateway-stripe-and-woocommerce-integration/" class="button button-primary">
                                                                         <?php _e('Contact us','payment-gateway-stripe-and-woocommerce-integration'); ?>
                                                                     </a>
                                                                 </li>               
                                                             </ul>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </form>
                                         <?php
                                    break;
                                
                                case 'afterpay' :
                                    $this->eh_local_gateways();
                                    ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                        <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                        
                                        <?php
                                        WC_Admin_Settings::get_settings_pages(); 
                                        if( ! empty( $_POST ) ) {
                                        
                                            $gateways = WC()->payment_gateways()->payment_gateways();
                                            $gateways['eh_afterpay_stripe']->process_admin_options();
                                            wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                        }
                                        
                                        $obj = new EH_Afterpay();
                                        
                                        $obj->admin_options();

                                        wc_enqueue_js("
                                            $('.description').css({'font-style':'normal'});
                                            $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                            $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                       ");
                                        
                                        
                                        ?>
                                        <p class="submit">
                                            <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                            <?php endif; ?>
                                            
                                        </p>
                                    </form> <?php
                                        
                                    break; 
                                                            
                                case 'wechat' :
                                    $this->eh_local_gateways();
                                    ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                        <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                        
                                        <?php
                                        WC_Admin_Settings::get_settings_pages(); 
                                        if( ! empty( $_POST ) ) {
                                        
                                            $gateways = WC()->payment_gateways()->payment_gateways();
                                            $gateways['eh_wechat_stripe']->process_admin_options();
                                            wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); exit();
                                        }
                                        
                                        $obj = new EH_Wechat();
                                        
                                        $obj->admin_options();

                                        wc_enqueue_js("
                                            $('.description').css({'font-style':'normal'});
                                            $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                            $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                       ");
                                        
                                        
                                        ?>
                                        <p class="submit">
                                            <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                                <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                            <?php endif; ?>
                                            
                                        </p>
                                    </form> <?php
                                        
                                    break; 

                          
                            case 'ideal' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_ideal_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Ideal();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment_gateway_stripe_and_woocommerce_integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                            case 'bancontact' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_bancontact_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Bancontact();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                                                                               
                            case 'eps' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_eps_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_EPS();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                                                 
                            case 'p24' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_p24_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_P24();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                                                                        

                            case 'bacs' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_bacs']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Bacs();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                               
                            case 'becs' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_becs_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_BECS();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                               
                            case 'fpx' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_fpx_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_FPX();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                               
                            case 'boleto' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_boleto_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Boleto();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                               
                            case 'oxxo' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_oxxo_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Oxxo();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                               
                            case 'grabpay' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_grabpay_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Grabpay();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 
                               
                            /*case 'multibanco' :
                                $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" enctype="multipart/form-data">
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_multibanco_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Multibanco();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment-gateway-stripe-and-woocommerce-integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; */

                            case 'affirm' :
                                    $this->eh_local_gateways();
                                ?><form method="post" class="eh_mainform" action="" >
                                    <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                                    
                                    <?php
                                    WC_Admin_Settings::get_settings_pages(); 
                                    if( ! empty( $_POST ) ) {
                                    
                                        $gateways = WC()->payment_gateways()->payment_gateways();
                                        $gateways['eh_affirm_stripe']->process_admin_options();
                                        wp_redirect($_SERVER['REQUEST_URI'].'&msg=1'); 
                                    }
                                    
                                    $obj = new EH_Affirm();
                                    
                                    $obj->admin_options();

                                    wc_enqueue_js("
                                        $('.description').css({'font-style':'normal'});
                                        $('.eh-desp-class').css({'font-style': 'italic','font-weight': '400','font-size': '12px','width':'100%','margin-top': '10px'});
                                        $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '5px','width': '95%'}); 
                                   ");
                                    
                                    
                                    ?>
                                    <p class="submit">
                                        <?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
                                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'payment_gateway_stripe_and_woocommerce_integration' ); ?>" />
                                        <?php endif; ?>
                                        
                                    </p>
                                </form> <?php
                                    
                                break; 

                                                                           
                           }
                        ?>
                    </div>
                    <div class="eh_settings_right">
                        <?php include(EH_STRIPE_MAIN_PATH . "includes/eh-goto-pro.php"); ?>
                    </div>
        </div>
        <?php    
        }  
    }
    
    
    /**
	 * includes screen ids of settings pages with woocommerce screen ids
     * @since 3.3.6
	 */
    public function add_eh_screen_id( $screen_ids ) {
        $screen_ids[] = 'toplevel_page_wt_stripe_menu';
        $screen_ids[] = 'webtoffee-stripe_page_stripe_alipay_menu';
        $screen_ids[] = 'webtoffee-stripe_page_stripe_checkout_menu';
        return $screen_ids;

    }


    // Display local gateways
    public function eh_local_gateways(){

        if (isset($_REQUEST['tab'])) {
           $clicked_tab = $_REQUEST['tab'];
        }
        else{
            $clicked_tab = 'alipay';
        }
        ?><ul class="eh-advanced-settings-nav local-gateways">
                <li id="eh-li-local"><a  <?php ($clicked_tab == 'alipay') ? print('style="color:#9c9696"') : '' ?> id="eh-alipay-link" class="nav-link" href="?page=wt_stripe_menu&tab=alipay"><?php _e('Alipay', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                    
                <li id="eh-li-local"><a <?php ($clicked_tab == 'sepa') ? print('style="color:#9c9696"') : '' ?>   id="eh-sepa-link" class="nav-link"  href="?page=wt_stripe_menu&tab=sepa"><?php _e('SEPA', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li> 

                <li id="eh-li-local"><a <?php ($clicked_tab == 'klarna') ? print('style="color:#9c9696"') : '' ?>   id="eh-klarna-link" class="nav-link"  href="?page=wt_stripe_menu&tab=klarna"><?php _e('Klarna', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>

                <li id="eh-li-local"><a <?php ($clicked_tab == 'afterpay') ? print('style="color:#9c9696"') : '' ?>   id="eh-ach-link" class="nav-link"  href="?page=wt_stripe_menu&tab=afterpay"><?php _e('Afterpay', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'wechat') ? print('style="color:#9c9696"') : '' ?>   id="eh-ach-link" class="nav-link"  href="?page=wt_stripe_menu&tab=wechat"><?php _e('WeChat', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'ideal') ? print('style="color:#9c9696"') : '' ?>   id="eh-ideal-link" class="nav-link"  href="?page=wt_stripe_menu&tab=ideal"><?php _e('iDEAL', 'payment_gateway_stripe_and_woocommerce_integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'bancontact') ? print('style="color:#9c9696"') : '' ?>   id="eh-bancontact-link" class="nav-link"  href="?page=wt_stripe_menu&tab=bancontact"><?php _e('Bancontact', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'eps') ? print('style="color:#9c9696"') : '' ?>   id="eh-eps-link" class="nav-link"  href="?page=wt_stripe_menu&tab=eps"><?php _e('EPS', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'p24') ? print('style="color:#9c9696"') : '' ?>   id="eh-p24-link" class="nav-link"  href="?page=wt_stripe_menu&tab=p24"><?php _e('Przelewy24', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                
                <li id="eh-li-local"><a <?php ($clicked_tab == 'bacs') ? print('style="color:#9c9696"') : '' ?>   id="eh-bacs-link" class="nav-link"  href="?page=wt_stripe_menu&tab=bacs"><?php _e('Bacs', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'becs') ? print('style="color:#9c9696"') : '' ?>   id="eh-becs-link" class="nav-link"  href="?page=wt_stripe_menu&tab=becs"><?php _e('BECS', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'fpx') ? print('style="color:#9c9696"') : '' ?>   id="eh-fpx-link" class="nav-link"  href="?page=wt_stripe_menu&tab=fpx"><?php _e('FPX', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'boleto') ? print('style="color:#9c9696"') : '' ?>   id="eh-boleto-link" class="nav-link"  href="?page=wt_stripe_menu&tab=boleto"><?php _e('Boleto', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'oxxo') ? print('style="color:#9c9696"') : '' ?>   id="eh-oxxo-link" class="nav-link"  href="?page=wt_stripe_menu&tab=oxxo"><?php _e('OXXO', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <li id="eh-li-local"><a <?php ($clicked_tab == 'grabpay') ? print('style="color:#9c9696"') : '' ?>   id="eh-grabpay-link" class="nav-link"  href="?page=wt_stripe_menu&tab=grabpay"><?php _e('GrabPay', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>
                <!-- <li id="eh-li-local"><a <?php ($clicked_tab == 'multibanco') ? print('style="color:#9c9696"') : '' ?>   id="eh-multibanco-link" class="nav-link"  href="?page=wt_stripe_menu&tab=multibanco"><?php //_e('Multibanco', 'payment-gateway-stripe-and-woocommerce-integration') ?></a>|</li>   -->      
                <li id="eh-li-local"><a <?php ('affirm' == $clicked_tab) ? print('style="color:#9c9696"') : '' ?>   id="eh-affirm-link" class="nav-link"  href="?page=wt_stripe_menu&tab=affirm "><?php _e('Affirm', 'payment-gateway-stripe-and-woocommerce-integration') ?></a></li>        
            </ul><?php

    } 

    /**
     * To check whether the plugin is oauth compatible or not     
     */
    static function wtst_oauth_compatible($mode = ''){
        if(empty($mode)){
            $settings = get_option("woocommerce_eh_stripe_pay_settings");
            $mode = isset($settings['eh_stripe_mode']) ? $settings['eh_stripe_mode'] : 'live';            
        }

        $option = ('test' === $mode) ? 'wt_stripe_oauth_connected_test' : 'wt_stripe_oauth_connected_live';
       
        if("yes" === EH_Stripe_Token_Handler::wtst_get_site_option('get', array('name' => $option))){
            return true;
        }
        else{ 
            return false;
        }
    }

    static function wt_get_install_link($mode)
    {
        //Stripe oAuth customer site URL
        $site_url = add_query_arg( array( 'wc-api'=> 'wt_stripe_oauth_update', 'mode' => $mode, 'name' => EH_STRIPE_PLUGIN_NAME), trailingslashit( get_home_url() ));
        $site_url_encoded = base64_encode($site_url);
        //sandbox
        $client_id_test = 'ca_Pl5sdRX9ZIbMhFni2PDjsnkMEERxD3Ye';

        //live
        $client_id_live = 'ca_Pl5sCXjmB1vQPLI6ewCrUibnq1DojGbA';        

        if('test' === $mode){
            return add_query_arg( array('client_id' => $client_id_test, "redirect_uri" => EH_STRIPE_OAUTH_WT_URL ."oauth", "state" => $site_url_encoded ), "https://marketplace.stripe.com/oauth/v2/authorize" );

        }
        else{
            return add_query_arg( array('client_id' => $client_id_live, "redirect_uri" => EH_STRIPE_OAUTH_WT_URL ."oauth", "state" => $site_url_encoded ), "https://marketplace.stripe.com/oauth/v2/authorize" );

        }
    } 

    //Update the info to detect whether the user click connect later button
    public function wtst_oauth_connect_later()
    {
       if(wp_verify_nonce($_REQUEST['_wpnonce'], "eh_stripe_oauth_connect")){
            if(isset($_REQUEST['oauth_connect_later']) && "yes" === sanitize_text_field($_REQUEST['oauth_connect_later'])){
                set_transient("wtst_oauth_connect_later", "yes");
                print 'success';
                exit;
            }
       }
    }  

    //Check whether the plugin is already installed and used in customer site
    static function wtst_new_installation()
     {
        $stripe_settings = get_option("woocommerce_eh_stripe_pay_settings");
        //if no settings data is saved in the db, it might be a new installation
        if(empty($stripe_settings)){ 
            return true;
        }

        $flag_test_exist = true;
        $flag_live_exist = true;
        //If any sandbox keys saved, it is an existing user
        if (!isset($stripe_settings['eh_stripe_test_publishable_key']) || !isset($stripe_settings['eh_stripe_test_secret_key']) || ! $stripe_settings['eh_stripe_test_publishable_key'] || ! $stripe_settings['eh_stripe_test_secret_key']) {
            $flag_test_exist = false;
        }
        //If any sandbox keys saved, it is an existing user and not a new installation
        if (!isset($stripe_settings['eh_stripe_live_secret_key']) || !isset($stripe_settings['eh_stripe_live_publishable_key']) || !$stripe_settings['eh_stripe_live_secret_key'] || !$stripe_settings['eh_stripe_live_publishable_key']) {
            $flag_live_exist = false; 
        }
        //if both sandbox and live keys are not exist, it is a new installation
        if(false === $flag_test_exist && false === $flag_live_exist){
            return true;       
           
        }
        else{
            return false;
        }
     } 


    public function wtst_oauth_disconnect()
    {
       if(wp_verify_nonce($_REQUEST['_wpnonce'], "eh_stripe_oauth_connect")){
            $stripe_settings = get_option("woocommerce_eh_stripe_pay_settings");            
            $mode = (isset($stripe_settings['eh_stripe_mode']) ? $stripe_settings['eh_stripe_mode'] : 'live');

            //If mode is passed override 
            $mode = (isset($_REQUEST['mode']) ? sanitize_text_field($_REQUEST['mode']) : $mode);

            if('test' === $mode){
                if(isset($_REQUEST['expire']) && 'access_token' === sanitize_text_field($_REQUEST['expire']) ){ 
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_oauth_expriy_test')); 
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));                   

                }
                else{                  
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_account_id_test'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_access_token_test')); 
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_refresh_token_test'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_oauth_connected_test'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_oauth_expriy_test'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling')); 
                }

            }
            else{
                if(isset($_REQUEST['expire']) && 'access_token' === sanitize_text_field($_REQUEST['expire']) ){
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_oauth_expriy_live'));                   
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));
                }
                else{                  
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_account_id_live'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_access_token_live'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_refresh_token_live')); 
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', array('name' => 'wt_stripe_oauth_connected_live'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_oauth_expriy_live'));
                    EH_Stripe_Token_Handler::wtst_get_site_option('delete', null, array('name' => 'wtst_refresh_token_calling'));
                }
            }

            exit;
       }
    }

    /**
     * Function to set transient when oAuth notice is dismissed and to identify the no of times user dismissed the notice 
     * 
     * */    
    public function wtst_dismiss_oauth_notice()
    {
        if(isset($_REQUEST['action']) && 'wtst_dismiss_oauth_notice' === sanitize_text_field($_REQUEST['action'])){
            set_transient("wtst_dismiss_oauth_notice", "yes", 2 * DAY_IN_SECONDS);
            $dismissible_count = get_transient("wtst_oauth_notice_dismissable_count");
            $dismissible_count = (int) (false !== $dismissible_count) ? ($dismissible_count + 1) : 1;
            set_transient("wtst_oauth_notice_dismissable_count", $dismissible_count);
        }
    }

    /**
     * Function to set transient when Sofort notice is dismissed
     * 
     * 
     * */
    public function wtst_dismiss_sofort_notice()
    {
        if(isset($_REQUEST['action']) && 'wtst_dismiss_sofort_notice' === sanitize_text_field($_REQUEST['action'])){
            set_transient("wtst_dismiss_sofort_notice", "yes");

        }
    }


    /**
     * To Check if the current date is on or between the start and end date of black friday and cyber monday banner for 2024.
     * @since 4.0.1
     */
    public static function is_bfcm_season() {
        $start_date = new DateTime( '25-NOV-2024, 12:00 AM', new DateTimeZone( 'Asia/Kolkata' ) ); // Start date.
        $current_date = new DateTime( 'now', new DateTimeZone( 'Asia/Kolkata' ) ); // Current date.
        $end_date = new DateTime( '02-DEC-2024, 11:59 PM', new DateTimeZone( 'Asia/Kolkata' ) ); // End date.

        /**
         * check if the date is on or between the start and end date of black friday and cyber monday banner for 2024.
         */
        if ( $current_date < $start_date  || $current_date >= $end_date) {
            return false;
        }
        return true;
    }

    /**
     *  @since 4.0.4
     *  Changelog in plugins page
     */
    public function wt_stripe_upgrade_notice( $data, $response )
    { 
     
    
        if ( isset( $data['upgrade_notice'] ) )
        {

            
            add_action('admin_print_footer_scripts', 'wt_st_plugin_screen_update_notice_js');
           $msg = str_replace(array( '<p>', '</p>' ), array( '<div>', '</div>' ), $data['upgrade_notice']);
            echo '<style type="text/css">
            #payment-gateway-stripe-and-woocommerce-integration-update .update-message p:last-child{ display:none;}     
            #payment-gateway-stripe-and-woocommerce-integration-update . ul{ list-style:disc; margin-left:30px;}
            .wt_st_update_message{ padding-left:30px;}
            </style>
            <div class="update-message wt_st_update_message">' . wp_kses_post( wpautop( $msg ) ) . '</div>';
        }
    }

    /**
     *  @since 4.0.4
     *  Javascript code for changelog in plugins page
     */
    public function wt_st_plugin_screen_update_notice_js() 
    {   
        global $pagenow;
        if('plugins.php' != $pagenow)
        {
            return;
        }
        ?>
        <script>
            ( function( $ ){
                var update_dv=$('#payment-gateway-stripe-and-woocommerce-integration-update');
                update_dv.find('.wt_st_update_message').next('p').remove();
                update_dv.find('a.update-link:eq(0)').on('click', function(){
                    $('.wt_st_update_message').remove();
                });
            })( jQuery );
        </script>
        <?php
    }     
    
}