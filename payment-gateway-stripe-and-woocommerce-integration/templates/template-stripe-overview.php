<?php
if (!defined('ABSPATH')) {
    exit;
}?>
<div class='wrap' id='wrap_table' style="padding:10px;position:relative">
<?php
    eh_spg_list_stripe_table();
?>
</div>
<?php
function eh_spg_list_stripe_table()
{

    $obj= new Eh_Stripe_Datatables();
    $obj->input();
    $obj->prepare_items();
    $obj->search_box('search', 'search_id');
    ?>
    <label>Table Row</label>
    <input id='display_count_stripe' style="width:132px" type='number' value="<?php $count=get_option('eh_stripe_table_row');if($count){echo esc_attr($count);}?>" placeholder="<?php esc_attr_e( 'Number of Rows','payment-gateway-stripe-and-woocommerce-integration' ); ?>">
    <button id='save_dislay_count_stripe'class='button button-primary'><?php esc_html_e('Save', 'payment-gateway-stripe-and-woocommerce-integration'); ?></button>
    <form id="orders-filter" method="get">
        <input type="hidden" name="action" value="all" />
        <?php //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash ?>
        <input type="hidden" name="page" value="<?php echo (isset($_REQUEST['page']) ? esc_attr(sanitize_text_field(wp_unslash($_REQUEST['page']))) : ''); ?>" />
        <?php $obj->display(); ?>
    </form>
    <?php
}