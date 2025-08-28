<?php
if (!defined('ABSPATH')) {
    exit;
}?>
<div class='wrap' id='wrap_table' style="padding:10px;position:relative">
<?php
    eh_spg_list_order_table();
?>
</div>
<?php
function eh_spg_list_order_table()
{

    $obj= new Eh_Stripe_Order_Datatables();
    $obj->input();
    $obj->prepare_items();
    $obj->search_box('search', 'search_id');
    ?>
    <label>Table Row</label>
    <input id='display_count_order' style="width:132px" type='number' value="<?php $count=get_option('eh_order_table_row');if($count){echo esc_attr($count);}?>" placeholder="<?php esc_attr_e( 'Number of Rows','payment-gateway-stripe-and-woocommerce-integration' ); ?>">
    <button id='save_dislay_count_order'class='button button-primary'><?php esc_html_e('Save', 'payment-gateway-stripe-and-woocommerce-integration'); ?></button>
    <input type="hidden" name="action" value="all" />
    <?php //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized?>
    <input type="hidden" name="page" value="<?php echo (isset($_REQUEST['page']) ? esc_attr(sanitize_text_field(wp_unslash($_REQUEST['page']))) : ''); ?>" />
    <?php $obj->display(); ?>
    <?php
}