<style>
.eh_gopro_block {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    padding: 20px;
    margin-bottom: 30px;
    font-family: Arial, sans-serif;
}

.eh_section {
    margin-bottom: 25px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #E6ECF1;
}

.eh_section_header {
    display: flex;
    align-items: center;
    padding: 12px;
    font-weight: 590;
    font-size: 15px;
    color: #003366;
    justify-content: flex-start;
}

.eh_section_header img {
    width: 28px;
    height: 28px;
    margin-right: 10px;
}

.eh_section_content {
    padding: 15px 20px 5px;
}

.eh_section_content ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

.eh_section_content li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 12px;
    font-size: 14px;
    color: #333;
}

.eh_section_content li:before {
    content: '';
    position: absolute;
    left: 0;
    top: 2px;
    width: 14px;
    height: 14px;
    background-image: url(<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/green-tick.svg'); ?>);
    background-repeat: no-repeat;
    background-size: contain;
}

.eh_section_button {
    margin: 15px 0 10px;
    text-align: center;
}

.eh_section_button a {
    display: inline-block;
    background-color: #2D4DC1;
    color: #fff;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 590;
    border-radius: 6px;
    text-decoration: none;
}

.eh_badge_section {
    background: #F5EDF9;
    padding: 20px;
    border-radius: 10px;
    font-size: 14px;
    color: #333;
}

.eh_badge_item {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}

.eh_badge_item img {
    width: 40px;
    height: 40px;
    margin-right: 10px;
}

.eh_badge_divider {
    border-top: 1px solid #ddd;
    margin: 10px 0;
}

.eh_like_plugin {
    background: #F8F9FD;
    padding: 20px;
    border-radius: 12px;
    font-family: Arial, sans-serif;
    font-size: 14px;
    color: #333;
    margin-top: 20px;
    text-align: center;
}
.eh_like_plugin a {
    color: #0073aa;
    text-decoration: none;
}
.eh_like_plugin a:hover {
    text-decoration: underline;
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
</style>

<!-- Premium Features -->
<div class="eh_gopro_block">

  <!-- Stripe Premium -->
  <div class="eh_section" style="background-color: #EAF2FF;">
    <div class="eh_section_header" style="background-color: #DDEEFF;">
      <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/stripe-cta.svg'); ?>" alt="Stripe Icon">
      <span><?php _e('Advanced Stripe for WooCommerce', 'payment-gateway-stripe-and-woocommerce-integration'); ?></span>
    </div>
    <div class="eh_section_content">
      <ul>
        <li><?php _e('Accept recurring payments for WooCommerce Subscriptions', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('One-click payments via Link', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Offer 20+ payment options', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
      </ul>
      <div class="eh_section_button">
        <a href="https://www.webtoffee.com/product/woocommerce-stripe-payment-gateway/?utm_source=free_plugin_sidebar&utm_medium=Stripe_basic&utm_campaign=Stripe&utm_content=<?php echo EH_STRIPE_VERSION; ?>" target="_blank">
          <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/white-crown.svg'); ?>" alt="Crown" style="width:16px; vertical-align:middle; margin-right:6px;">
          <?php _e('Upgrade to Premium', 'payment-gateway-stripe-and-woocommerce-integration'); ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Subscription Plugin -->
  <div class="eh_section" style="background-color: #EBFAF0;">
    <div class="eh_section_header" style="background-color: #DDF9E4;">
      <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/subscription-cta.svg'); ?>" alt="Calendar Icon">
      <span><?php _e('WebToffee WooCommerce Subscription Plugin', 'payment-gateway-stripe-and-woocommerce-integration'); ?></span>
    </div>
    <div class="eh_section_content">
      <p style="margin-bottom: 15px;"><?php _e('Want to offer subscription-based products?', 'payment-gateway-stripe-and-woocommerce-integration'); ?></p>
      <ul>
        <li><?php _e('Create simple and variable subscriptions', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Offer free trials & charge signup fees', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Configure auto-renewals', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Offer discounts on renewals', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Synchronize subscription renewals', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Prorate subscription fee', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Manage access to content', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Send automated email reminders', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Supports Stripe and PayPal', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
        <li><?php _e('Supports 10+ languages', 'payment-gateway-stripe-and-woocommerce-integration'); ?></li>
      </ul>
      <div class="eh_section_button">
        <a href="https://www.webtoffee.com/product/woocommerce-subscription/?utm_source=free_plugin_sidebar&utm_medium=Stripe_basic&utm_campaign=Stripe&utm_content=<?php echo EH_STRIPE_VERSION; ?>" target="_blank">
          <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/white-crown.svg'); ?>" alt="Crown" style="width:16px; vertical-align:middle; margin-right:6px;">
          <?php _e('Get woo Subscription', 'payment-gateway-stripe-and-woocommerce-integration'); ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Guarantee & Satisfaction -->
  <div class="eh_badge_section">
    <div class="eh_badge_item">
      <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/money-back.svg'); ?>" alt="30-Day Guarantee">
      <div><strong><?php _e('30-Day', 'payment-gateway-stripe-and-woocommerce-integration'); ?></strong><br><?php _e('Money Back Guarantee', 'payment-gateway-stripe-and-woocommerce-integration'); ?></div>
    </div>
    <div class="eh_badge_divider"></div>
    <div class="eh_badge_item">
      <img src="<?php echo esc_url(EH_STRIPE_MAIN_URL_PATH.'assets/img/customer-satisfaction.svg'); ?>" alt="99% Satisfaction">
      <div><?php _e('99% Customer', 'payment-gateway-stripe-and-woocommerce-integration'); ?><br><strong><?php _e('Satisfaction Score', 'payment-gateway-stripe-and-woocommerce-integration'); ?></strong></div>
    </div>
  </div>
</div>

<!-- Like This Plugin -->
<div class="eh_like_plugin">
  <h3><strong><?php _e('Like this plugin?', 'payment-gateway-stripe-and-woocommerce-integration'); ?></strong></h3>
  <p>
    <?php _e('If you find this plugin useful please show your support and rate it', 'payment-gateway-stripe-and-woocommerce-integration'); ?>
    <a href="http://wordpress.org/support/view/plugin-reviews/payment-gateway-stripe-and-woocommerce-integration" target="_blank" style="color: #ffc600; text-decoration: none;">★★★★★</a>
    <?php _e('on', 'payment-gateway-stripe-and-woocommerce-integration'); ?>
    <a href="http://wordpress.org/support/view/plugin-reviews/payment-gateway-stripe-and-woocommerce-integration" target="_blank">WordPress.org</a>
    – <?php _e('much appreciated!', 'payment-gateway-stripe-and-woocommerce-integration'); ?> :)
  </p>
</div>
