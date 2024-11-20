<style>
    
.eh-button-go-pro {
    box-shadow: none;
    border: 0;
    text-shadow: none;
    padding: 10px 20px 10px 30px;
    height: auto;
    font-size: 15px;
    border-radius: 4px;
    font-weight: 500;
    background: #1DA5F8;
    margin: 20px 2px 20px 2px;
    text-decoration: none;
}

.eh-button {
    margin-bottom: 20px;
    color: #fff;
}
.eh-button:hover, .eh-button:visited {
    color: #fff;
}
.eh_gopro_block{ background: #fff; float: left; height:auto; padding: 10px; box-shadow: 0px 2px 2px #ccc; margin-bottom: 32px; width: 100%; border-top:solid 1px #cccccc; }
.eh_gopro_block h3{ text-align: center; }

.eh_premium_upgrade_head {
    font-weight: 500;
    font-size: 16px;
    line-height: 25px;
    color: #000000;
    margin-bottom: 5px;
    display: flex;
    border:  1px solid #F3FBFF;
    background: #F3FBFF;
}
.eh_premium_upgrade_head > span{
    padding: 10px;
}
.eh_pro_features li{ 
    padding-left:15%;  
    float: left;
    padding-bottom: 16px;
}

.eh_pro_features li.money-back:before{
  content: '';
  position: absolute;
  margin-right: 10px;
  margin-left: -40px;
  height:40px ;
  width: 50px;
  background-image: url(<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/money-back.svg'); ?>);
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
}
.eh_pro_features li.customer-satisfaction:before{
  content: '';
  position: absolute;
  margin-right: 10px;
  margin-left: -40px;
  height:40px ;
  width: 50px;
  background-image: url(<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/customer-satisfaction.svg'); ?>);
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
}


.eh_pro_green_btn:before{
  content: '';
  position: absolute;
  height: 15px;
  width: 18px;
  background-image: url(<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/white-crown.svg'); ?>);
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  margin: 2px 7px;
}
.eh_premium_features{
    padding: 10px 0px 10px 5px; 
    font-size: 14px; 
    font-weight: 400;
    /*background:#F3FAFF; */
    border-radius: 10px;
    border-color: #c6e7ef;
    /*border-width: 0.5px;
    border-style: solid;*/
}
.eh_premium_features li{ padding:10px 5px 10px 35px;  }
.eh_premium_features li::before {
    background-image: url(<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/green-tick.svg'); ?>);
    font-weight: 400;
    font-style: normal;
    vertical-align: top;
    text-align: center;
    content: "";
    margin-right: 10px;
    margin-left: -25px;
    font-size: 16px;
    color: #3085bb;
    height: 18px;
    width: 18px;
    position: absolute;
}
.eh-button-documentation{
    border: 0;
    background: #d8d8dc;
    box-shadow: none;
    padding: 10px 30px;
    font-size: 12px;
    font-weight: 550;
    height: auto;
    margin-left: 10px;
    margin-right: 10px;
    margin-top: 10px;
    border-radius: 3px;
    text-decoration: none;
}
.wfte_branding{
    text-align:end; 
    width: 20%;
    float: right;
    padding: 5px;
}
.wfte_branding_label{
    font-size: 11px;
    font-weight: 600;
    width: fit-content;
}
.wtst-bfcm-banner-2024-offer {

    padding: 0px 6px;

}
</style>

<div class="eh_gopro_block">

        <div class="eh_premium_upgrade_head">
            <span>
                <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/crown.svg') ?>">
            </span>
            <span><center><?php _e( "What's in premium", 'payment-gateway-stripe-and-woocommerce-integration' ); ?></center></span><?php
                /**
                 * @since 4.0.1
                 * Black Friday Cyber Monday banner offer
                 */ 
                if( true === Eh_Stripe_Admin_Handler::is_bfcm_season() ){
                    
                   ?><div class="wtst-bfcm-banner-2024-offer">
                        <svg width="31" height="40" viewBox="0 0 31 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 0H30.6977V40L15.3488 33.5849L0 40V0Z" fill="#FC4762"/>
                        <path d="M11.1814 27.8613C10.7556 27.8613 10.363 27.752 10.0037 27.5332C9.64819 27.3145 9.36108 27.0234 9.14233 26.6602C8.92748 26.293 8.82006 25.8906 8.82006 25.4531L8.83178 21.6504C8.83178 21.2051 8.9392 20.8047 9.15405 20.4492C9.36498 20.0898 9.65014 19.8027 10.0095 19.5879C10.3689 19.3691 10.7595 19.2598 11.1814 19.2598C11.6189 19.2598 12.0115 19.3672 12.3591 19.582C12.7107 19.7969 12.9919 20.0859 13.2029 20.4492C13.4177 20.8086 13.5251 21.209 13.5251 21.6504L13.5369 25.4531C13.5369 25.8906 13.4314 26.291 13.2205 26.6543C13.0056 27.0215 12.7205 27.3145 12.365 27.5332C12.0095 27.752 11.615 27.8613 11.1814 27.8613ZM11.1814 26.4141C11.4236 26.4141 11.6345 26.3164 11.8142 26.1211C11.9939 25.9219 12.0837 25.6992 12.0837 25.4531L12.072 21.6504C12.072 21.3848 11.988 21.1602 11.8201 20.9766C11.6521 20.793 11.4392 20.7012 11.1814 20.7012C10.9353 20.7012 10.7244 20.791 10.5486 20.9707C10.3728 21.1504 10.2849 21.377 10.2849 21.6504V25.4531C10.2849 25.7148 10.3728 25.9414 10.5486 26.1328C10.7244 26.3203 10.9353 26.4141 11.1814 26.4141ZM15.8513 27.7441H14.5037C14.4646 27.7441 14.4451 27.7246 14.4451 27.6855L14.4685 19.4414C14.4685 19.4102 14.4841 19.3945 14.5154 19.3945H18.3591C18.3982 19.3945 18.4177 19.4102 18.4177 19.4414V20.7949C18.4177 20.8262 18.4021 20.8418 18.3708 20.8418H15.9099V22.7051H18.3708C18.4021 22.7051 18.4177 22.7246 18.4177 22.7637L18.4294 24.123C18.4294 24.1543 18.4099 24.1699 18.3708 24.1699H15.9099V27.6855C15.9099 27.7246 15.8904 27.7441 15.8513 27.7441ZM20.9021 27.7441H19.5544C19.5154 27.7441 19.4958 27.7246 19.4958 27.6855L19.5193 19.4414C19.5193 19.4102 19.5349 19.3945 19.5662 19.3945H23.4099C23.449 19.3945 23.4685 19.4102 23.4685 19.4414V20.7949C23.4685 20.8262 23.4529 20.8418 23.4216 20.8418H20.9607V22.7051H23.4216C23.4529 22.7051 23.4685 22.7246 23.4685 22.7637L23.4802 24.123C23.4802 24.1543 23.4607 24.1699 23.4216 24.1699H20.9607V27.6855C20.9607 27.7246 20.9412 27.7441 20.9021 27.7441Z" fill="white"/>
                        <path d="M8.65968 15.7161C8.24171 15.7161 7.85304 15.6047 7.49367 15.3821C7.1382 15.1633 6.85304 14.8704 6.6382 14.5032C6.42726 14.1321 6.32179 13.7278 6.32179 13.2903V12.7512C6.32179 12.7083 6.33742 12.6868 6.36867 12.6868H7.71632C7.75539 12.6868 7.77492 12.7083 7.77492 12.7512V13.2903C7.77492 13.552 7.8589 13.7786 8.02687 13.97C8.19484 14.1575 8.40578 14.2512 8.65968 14.2512C8.94093 14.2512 9.16164 14.1536 9.32179 13.9583C9.48195 13.759 9.56203 13.5364 9.56203 13.2903V12.968C9.56203 12.718 9.4839 12.4934 9.32765 12.2942C9.1714 12.095 8.97414 11.9954 8.73585 11.9954H8.2671C8.22804 11.9954 8.20851 11.9739 8.20851 11.9309L8.22023 10.5891C8.22023 10.5579 8.23585 10.5422 8.2671 10.5422H8.73585C8.9507 10.5422 9.1421 10.4583 9.31007 10.2903C9.47804 10.1223 9.56203 9.90161 9.56203 9.62817V9.46411C9.56203 9.2063 9.47609 8.98755 9.30421 8.80786C9.13234 8.62817 8.91749 8.53833 8.65968 8.53833C8.41359 8.53833 8.2046 8.62817 8.03273 8.80786C7.86085 8.98755 7.77492 9.2063 7.77492 9.46411V10.0383C7.77492 10.0774 7.75929 10.0969 7.72804 10.0969H6.38039C6.34914 10.0969 6.33351 10.0774 6.33351 10.0383V9.39966C6.33351 8.96216 6.44484 8.57153 6.66749 8.22778C6.88624 7.88403 7.17335 7.6106 7.52882 7.40747C7.88429 7.20044 8.26124 7.09692 8.65968 7.09692C9.09328 7.09692 9.48781 7.20239 9.84328 7.41333C10.1987 7.62817 10.48 7.91138 10.687 8.26294C10.898 8.6145 11.0034 9.00122 11.0034 9.4231V9.61646C11.0034 9.96802 10.9448 10.2727 10.8277 10.5305C10.7105 10.7844 10.5484 11.0286 10.3413 11.2629C10.5484 11.5012 10.7124 11.7629 10.8335 12.0481C10.9546 12.3333 11.0152 12.6262 11.0152 12.927V13.2903C11.0152 13.7356 10.9097 14.1438 10.6987 14.5149C10.4839 14.8821 10.1987 15.175 9.84328 15.3938C9.48781 15.6086 9.09328 15.7161 8.65968 15.7161ZM14.3433 15.6985C13.8355 15.6985 13.3843 15.5891 12.9898 15.3704C12.5991 15.1516 12.2925 14.8606 12.0698 14.4973C11.8511 14.1301 11.7417 13.7278 11.7417 13.2903L11.7534 9.48755C11.7534 9.04224 11.8648 8.64185 12.0874 8.28638C12.3062 7.927 12.6109 7.63989 13.0015 7.42505C13.3921 7.2063 13.8394 7.09692 14.3433 7.09692C14.8589 7.09692 15.3081 7.20435 15.6909 7.41919C16.0737 7.63403 16.3745 7.9231 16.5933 8.28638C16.8159 8.64575 16.9273 9.04614 16.9273 9.48755L16.939 13.2903C16.939 13.7239 16.8296 14.1243 16.6109 14.4915C16.3882 14.8586 16.0816 15.1516 15.6909 15.3704C15.3042 15.5891 14.855 15.6985 14.3433 15.6985ZM15.4859 13.2903L15.4741 9.48755C15.4741 9.2063 15.3687 8.97778 15.1577 8.802C14.9507 8.62622 14.6792 8.53833 14.3433 8.53833C13.9995 8.53833 13.7241 8.62622 13.5171 8.802C13.3101 8.97778 13.2066 9.2063 13.2066 9.48755V13.2903C13.2066 13.5872 13.3101 13.8215 13.5171 13.9934C13.7241 14.1653 13.9995 14.2512 14.3433 14.2512C14.6792 14.2512 14.9527 14.1653 15.1636 13.9934C15.3784 13.8215 15.4859 13.5872 15.4859 13.2903ZM19.1069 11.1047C18.8257 11.1047 18.5698 11.0325 18.3394 10.8879C18.1011 10.7434 17.9116 10.552 17.771 10.3137C17.6304 10.0715 17.5601 9.81177 17.5601 9.53442V8.67896C17.5601 8.38989 17.6304 8.12622 17.771 7.88794C17.9116 7.64575 18.0991 7.4563 18.3335 7.31958C18.5718 7.17896 18.8296 7.10864 19.1069 7.10864C19.396 7.10864 19.6577 7.17896 19.8921 7.31958C20.1226 7.46021 20.3042 7.65161 20.437 7.8938C20.5737 8.13208 20.6421 8.3938 20.6421 8.67896V9.53442C20.6421 9.81958 20.5737 10.0813 20.437 10.3196C20.2964 10.554 20.1089 10.7434 19.8745 10.8879C19.6441 11.0325 19.3882 11.1047 19.1069 11.1047ZM19.646 15.5813H18.2456C18.2066 15.5813 18.1948 15.5618 18.2105 15.5227L22.9976 7.27856C23.021 7.24731 23.0444 7.23169 23.0679 7.23169H24.4624C24.5054 7.23169 24.5171 7.24731 24.4976 7.27856L19.7222 15.5227C19.7066 15.5618 19.6812 15.5813 19.646 15.5813ZM19.0835 10.1321C19.2437 10.1321 19.3745 10.0754 19.4761 9.96216C19.5816 9.84497 19.6343 9.70239 19.6343 9.53442V8.70239C19.6343 8.54614 19.5816 8.40942 19.4761 8.29224C19.3745 8.17505 19.2437 8.11646 19.0835 8.11646C18.9234 8.11646 18.7886 8.17505 18.6792 8.29224C18.5737 8.40942 18.521 8.54614 18.521 8.70239V9.53442C18.521 9.69458 18.5757 9.83521 18.6851 9.9563C18.7984 10.0735 18.9312 10.1321 19.0835 10.1321ZM23.73 15.6985C23.4448 15.6985 23.187 15.6262 22.9566 15.4817C22.7183 15.3411 22.5288 15.1516 22.3882 14.9133C22.2476 14.6711 22.1773 14.4094 22.1773 14.1282V13.2786C22.1773 12.9856 22.2476 12.72 22.3882 12.4817C22.5288 12.2395 22.7163 12.05 22.9507 11.9133C23.189 11.7727 23.4487 11.7024 23.73 11.7024C24.0152 11.7024 24.2749 11.7727 24.5093 11.9133C24.7398 12.054 24.9234 12.2454 25.0601 12.4875C25.1968 12.7258 25.2652 12.9895 25.2652 13.2786V14.1282C25.2652 14.4172 25.1948 14.679 25.0542 14.9133C24.9136 15.1516 24.7261 15.343 24.4917 15.4875C24.2612 15.6282 24.0073 15.6985 23.73 15.6985ZM23.7183 14.7317C23.8784 14.7317 24.0093 14.6731 24.1109 14.5559C24.2163 14.4387 24.2691 14.2961 24.2691 14.1282V13.302C24.2691 13.1418 24.2163 13.0051 24.1109 12.8918C24.0093 12.7747 23.8784 12.7161 23.7183 12.7161C23.5581 12.7161 23.4234 12.7747 23.314 12.8918C23.2046 13.0051 23.1499 13.1418 23.1499 13.302V14.1282C23.1499 14.2883 23.2066 14.429 23.3198 14.55C23.4331 14.6711 23.5659 14.7317 23.7183 14.7317Z" fill="white"/>
                        </svg>
                    </div><?php
                }            
        ?></div>

        

        <ul class="eh_premium_features">
            <li><?php echo __('Supports recurring payments for WooCommerce subscriptions','payment-gateway-stripe-and-woocommerce-integration'); ?></li>         
            <li><?php echo __('Premium priority support','payment-gateway-stripe-and-woocommerce-integration'); ?></li>         
            <li><?php echo __('Timely compatibility updates & bug fixes','payment-gateway-stripe-and-woocommerce-integration'); ?></li>
            <div class="eh_pro_green_btn" style="padding-top:15px; padding-left: 25px; padding-bottom: 15px;">
               <!--<p style="text-align: left;">-->
                <a href="https://www.webtoffee.com/product/woocommerce-stripe-payment-gateway/?utm_source=free_plugin_sidebar&utm_medium=Stripe_basic&utm_campaign=Stripe&utm_content=<?php echo EH_STRIPE_VERSION;?>" target="_blank" class="eh-button eh-button-go-pro"><?php echo __('Upgrade to Premium','payment-gateway-stripe-and-woocommerce-integration'); ?></a>
            <!--</p>-->
            </div>         
            
        </ul>
    <p style="text-align: center;">
            <ul class="eh_pro_features" style="font-weight: 400; color:#666; list-style: none; padding:10px;  font-size: 13px; line-height: 16px; ">
                <li class="money-back" style=""><span style=" padding-left: 10px;float: left;"><?php echo __('You are covered by our 30-Day Money Back Guarantee ','payment-gateway-stripe-and-woocommerce-integration'); ?></span></li>
                <li class="customer-satisfaction" style=""><span style=" padding-left: 10px;float: left;"><?php echo __('Supported by a team with 99% Customer Satisfaction Score','payment-gateway-stripe-and-woocommerce-integration'); ?></span></li>

            </ul>


        <br/>
    </p>
    <!--<p style="text-align: center;">
            <a href="https://www.webtoffee.com/category/documentation/stripe-payment-gateway-for-woocommerce/" target="_blank" class="eh-button eh-button-documentation" style=" color: #555 !important;"><?php //echo __('Documentation','payment-gateway-stripe-and-woocommerce-integration'); ?></a>

    </p>-->
</div>

<div class="eh_gopro_block">
    <h3 style="text-align: center;"><?php echo __('Like this plugin?','payment-gateway-stripe-and-woocommerce-integration'); ?></h3>
    <p><?php echo __('If you find this plugin useful please show your support and rate it','payment-gateway-stripe-and-woocommerce-integration'); ?> <a href="http://wordpress.org/support/view/plugin-reviews/payment-gateway-stripe-and-woocommerce-integration" target="_blank" style="color: #ffc600; text-decoration: none;">★★★★★</a><?php echo __(' on','payment-gateway-stripe-and-woocommerce-integration'); ?> <a href="http://wordpress.org/support/view/plugin-reviews/payment-gateway-stripe-and-woocommerce-integration" target="_blank">WordPress.org</a> -<?php echo __('  much appreciated!','payment-gateway-stripe-and-woocommerce-integration'); ?> :)</p>

</div>