<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Eh_Stripe_Order_Datatables extends WP_List_Table {

    public $order_data;

    function __construct() {
        parent::__construct(array(
            'singular' => 'Order',
            'plural' => 'Orders',
            'ajax' => true
        ));
    }
    
    //Get data to be displayed in order details list table
    public function input() {
        $order_id = eh_stripe_overview_get_order_ids();
        $order_temp = array();
        for ($i = 0; $i < count($order_id); $i++) {
            $order = wc_get_order($order_id[$i]);
            $data = EH_Helper_Class::wt_stripe_order_db_operations($order_id[$i], $order, 'get', '_eh_stripe_payment_charge');
            $order_temp[$i]['order_id'] = $order_id[$i];
            $order_temp[$i]['order_number'] = $order->get_order_number();
            $order_temp[$i]['order_status'] = $order->get_status();
            $order_temp[$i]['user_id'] = ($order->get_user_id()) ? $order->get_user_id() : 'guest';
            if ($order_temp[$i]['user_id'] === 'guest') {
                $order_temp[$i]['user_name'] = __('Guest', 'payment-gateway-stripe-and-woocommerce-integration');
            } else {
                $order_temp[$i]['user_name'] = get_user_meta($order->get_user_id(), 'first_name', true) . ' ' . get_user_meta($order->get_user_id(), 'last_name', true);
            }
            $order_temp[$i]['user_email'] = (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_email : $order->get_billing_email();
            $order_temp[$i]['ship'] = $order->get_address('shipping');
            $order_temp[$i]['order_total'] = $order->get_total();
            $order_temp[$i]['order_mode'] = (isset($data['mode']) ? $data['mode'] : '');
            $order_temp[$i]['refund_rem'] = $order->get_remaining_refund_amount();
            $order_temp[$i]['price'] = $order->get_formatted_order_total();
            //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            $order_temp[$i]['date'] = date('Y-m-d ', (version_compare(WC()->version, '2.7.0', '<')) ? strtotime($order->order_date) : strtotime($order->get_date_created()));
        }
        $this->order_data = $order_temp;
    }
    
    //Checkbox column for selection
    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                esc_attr( $this->_args['plural'] ),
                absint( $item['order_id'] )
        );
    }
    
    //Column to display order status
    function column_order_status($item) {
        return sprintf(
            '<mark class="%1$s tips"><span>%2$s</span></mark>',
            esc_attr( $item['order_status'] ),
            esc_html( ucfirst( $item['order_status'] ) )
        );
    }
    
    //Column to display order details
    function column_order($item) {
        $order_url = add_query_arg(
            array(
                'post'   => absint( $item['order_id'] ),
                'action' => 'edit',
            ),
            admin_url( 'post.php' )
        );

        $user_name = esc_html( $item['user_name'] );
        if ( 'guest' !== $item['user_id'] ) {
            $user_name = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url( add_query_arg( 'user_id', absint( $item['user_id'] ), admin_url( 'user-edit.php' ) ) ),
                esc_html( $item['user_name'] )
            );
        }

        return sprintf(
            '<span><a href="%1$s"><strong>#%2$s</strong></a> by %3$s<br>%4$s</span>',
            esc_url( $order_url ),
            esc_html( $item['order_number'] ),
            wp_kses_post( $user_name ),
            esc_html( $item['user_email'] )
        );
    }
    
    //Column to display order shipping details
    function column_ship($item) {
        
       
        $shiptoaddr = ( ! empty( $item['ship']['first_name'] ) || ! empty( $item['ship']['last_name'] ) ) ? esc_html( $item['ship']['first_name'] . ' ' . $item['ship']['last_name'] ) . ',' : '';
        $shiptoaddr.= ( ! empty( $item['ship']['company'] ) ) ? esc_html( $item['ship']['company'] ) . ',' : '';
        $shiptoaddr.= ( ! empty( $item['ship']['address_1'] ) ) ? esc_html( $item['ship']['address_1'] ) . ',' : '';
        $shiptoaddr.= ( ! empty( $item['ship']['address_2'] ) ) ? esc_html( $item['ship']['address_2'] ) . ',' : '';
        $shiptoaddr.= ( ! empty( $item['ship']['city'] ) ) ? esc_html( $item['ship']['city'] ) . ',' : '';
        $shiptoaddr.= ( ! empty( $item['ship']['state'] ) ) ? esc_html( $item['ship']['state'] ) . '-' : '';
        $shiptoaddr.= ( ! empty( $item['ship']['postcode'] ) ) ? esc_html( $item['ship']['postcode'] ) . ',' : '';
        $shiptoaddr.= ( ! empty( $item['ship']['country'] ) ) ? esc_html( $item['ship']['country'] ) . ',' : '';
        return '<span>' . $shiptoaddr . '</span>';
    }
    
    //Column to display order price
    function column_price($item) {
        return '<span>' . wp_kses_post( $item['price'] ) . '</span>';
    }
    
    //Column to display refund button for full and partial refunds
    function column_p_actions($item) {
        $actions = '';
        if (in_array($item['order_status'], array('pending', 'on-hold', 'processing', 'completed', 'cancelled'), true)) {
            $id = absint( $item['order_id'] );
            $data = EH_Helper_Class::wt_stripe_order_db_operations($id, null, 'get', '_eh_stripe_payment_charge');
            if ($data !== '') {
                if ('succeeded' === $data['status']) {
                    switch ($data['captured']) {
                        case 'Captured':
                            $refund_amount = wc_format_decimal( $item['refund_rem'] );
                            $actions = '<span style="text-align: center;" class="button payment_refund_button payment_act" id="' . esc_attr( $id ) . '">' . esc_html__( 'Refund ', 'payment-gateway-stripe-and-woocommerce-integration' ) . esc_html( get_woocommerce_currency_symbol() ) . '<span class="amount_refund_main_' . esc_attr( $id ) . '">' . esc_html( $refund_amount ) . '</span><span class="amount_refund_place_' . esc_attr( $id ) . '" hidden> ' . esc_html( $refund_amount ) . '</span><span id="' . esc_attr( $id ) . '_loader"></span></span><input type="number" id="' . esc_attr( $id ) . '" class="payment_refund_text_' . esc_attr( $id ) . '" style="float:left; width:89%; margin-top:3px; margin-left:3px" placeholder="' . esc_attr__( 'Amount', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" hidden>'
                                    . '<input type="checkbox" style="margin-left:3px;" checked class="' . esc_attr( $id ) . '" id="payment_refund_check" value="refund">' . esc_html__( 'Full', 'payment-gateway-stripe-and-woocommerce-integration' );
                            break;
                        case 'Uncaptured':
                            $actions = '<center><span style="width:100%;text-align: center;" class="button payment_capture_button payment_act" id="' . esc_attr( $id ) . '">' . esc_html__( 'Capture ', 'payment-gateway-stripe-and-woocommerce-integration' ) . esc_html( get_woocommerce_currency_symbol() ) . '<span class="amount_capture_main_' . esc_attr( $id ) . '">' . esc_html( number_format( (float) $item['order_total'], 2, '.', '' ) ) . ' </span></span></center>';
                            break;
                    }
                }
            }
        }
        return $actions;
    }
    
    //Column to display order actions
    function column_order_actions($item) {
        $actions = '';
        $order_id = absint( $item['order_id'] );
        switch ($item['order_status']) {
            case 'pending':
            case 'on-hold':
                $actions = '<p><span style="width:98%; margin-bottom: 2px;" class="button processing order_act processing_button" id="' . esc_attr( $order_id ) . '" title="' . esc_attr__( 'Mark as processed', 'payment-gateway-stripe-and-woocommerce-integration' ) . '">' . esc_html__( 'Processing', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span><span style="width:98%" class="button complete order_act complete_button" id="' . esc_attr( $order_id ) . '" title="' . esc_attr__( 'Mark as completed', 'payment-gateway-stripe-and-woocommerce-integration' ) . '">' . esc_html__( 'Completed', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span></p>';
                break;
            case 'processing':
                $actions = '<span style="width:98%" class="button complete_button complete order_act" id="' . esc_attr( $order_id ) . '" title="' . esc_attr__( 'Mark as completed', 'payment-gateway-stripe-and-woocommerce-integration' ) . '">' . esc_html__( 'Completed', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>';
                break;
            default :
                $actions = '<span></span>';
        }
        return $actions;
    }
    
    //Column to display order date with order mode
    function column_date($item) {
        $actions = '';
        if ($item['order_mode'] === 'Test') {
            $actions = '<br><strong style="color:orangered">' . esc_html__( 'TEST MODE', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</strong>';
        }
        return '<span>' . esc_html( $item['date'] ) . '</span>' . $actions;
    }
    
    //Gets columns for the list table
    function get_columns() {

        return $columns = array(
            'cb' => '<input type="checkbox" />',
            'order_status' => '<span class="status_head tips">' . esc_html__( 'Status', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>',
            'order' => esc_html__( 'Order', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'ship' => esc_html__( 'Ship to', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'price' => esc_html__( 'Price', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'p_actions' => esc_html__( 'Payment Action', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'order_actions' => esc_html__( 'Actions', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'date' => esc_html__( 'Date', 'payment-gateway-stripe-and-woocommerce-integration' )
        );
    }
    
    //Returns columns to be made sortable
    function get_sortable_columns() {
        $sortable_columns = array();
        return $sortable_columns;
    }
    
    //Gets functions in bulk action drop down
    function get_bulk_actions() {
        $actions = array(
            'processing' => esc_html__( 'Mark Processing', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'on-hold' => esc_html__( 'Mark On-Hold', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'completed' => esc_html__( 'Mark Completed', 'payment-gateway-stripe-and-woocommerce-integration' )
        );
        return $actions;
    }
    
    //Preparing table with table elements
    function prepare_items($page_num = '', $prepare = '') {
        $per_page = (get_option('eh_order_table_row')) ? get_option('eh_order_table_row') : 20;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array(
            $columns,
            $hidden,
            $sortable
        );
        $data = $this->order_data;
        $current_page = ($page_num == '') ? $this->get_pagenum() : $page_num;
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    //renders the complete list table to the page
    function display() {
        parent::display();
    }
    
    //handles incoming Ajax requests
    function ajax_response($page_num = '') {

        $this->prepare_items($page_num);

        extract($this->_args);
        extract($this->_pagination_args, EXTR_SKIP);

        ob_start();
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
        if (!empty($_REQUEST['no_placeholder'])) {
            $this->display_rows();
        } else {
            $this->display_rows_or_placeholder();
        }
        $rows = ob_get_clean();

        ob_start();
        $this->print_column_headers();
        $headers = ob_get_clean();

        ob_start();
        $this->pagination('top');
        $pagination_top = ob_get_clean();

        ob_start();
        $this->pagination('bottom');
        $pagination_bottom = ob_get_clean();

        $response = array(
            'rows' => $rows
        );
        $response['pagination']['top'] = $pagination_top;
        $response['pagination']['bottom'] = $pagination_bottom;
        $response['column_headers'] = $headers;

        if (isset($total_items)) {
            /* translators: %s: Number of items */
            $response['total_items_i18n'] = sprintf(_n('%s item', '%s items', $total_items, 'payment-gateway-stripe-and-woocommerce-integration'), number_format_i18n($total_items));
        }

        if (isset($total_pages)) {
            $response['total_pages'] = $total_pages;
            $response['total_pages_i18n'] = number_format_i18n($total_pages);
        }

        die(wp_json_encode($response));
    }

}

class Eh_Stripe_Datatables extends WP_List_Table {

    public $stripe_data;

    function __construct() {
        parent::__construct(array(
            'singular' => 'Stripe',
            'plural' => 'Stripe',
            'ajax' => true
        ));
    }
    
    //Gets value to be displayed in transaction details list table
    public function input() {
        $order_id = eh_stripe_overview_get_order_ids();
        $stripe_temp = array();
        for ($i = 0, $j = 0; $i < count($order_id); $i++) {
              $charge_count = count(EH_Helper_Class::wt_stripe_order_db_operations($order_id[$i], null, 'get', '_eh_stripe_payment_charge',null, false));
            $refund_count = count(EH_Helper_Class::wt_stripe_order_db_operations($order_id[$i], null, 'get', '_eh_stripe_payment_refund',null, false));
            $balance_count = count(EH_Helper_Class::wt_stripe_order_db_operations($order_id[$i], null, 'get', '_eh_stripe_payment_balance',null, false));
            $data =  EH_Helper_Class::wt_stripe_order_db_operations($order_id[$i], null, 'get', '_eh_stripe_payment_charge',null, false);
            for ($k = 0; $k < $charge_count; $k++) {
                if (isset($data[$k]->value) && is_object($data[$k])) {
                    $data[$k] = $data[$k]->value;
                } 
                elseif (!empty($data)) 
                {
                    $keys = array_keys($data);
                    $k = end($keys);
                }        
                       
                $order = wc_get_order($order_id[$i]);
                $stripe_temp[$j]['order_id'] = $order_id[$i];
                $stripe_temp[$j]['order_number'] = $order->get_order_number();
                $stripe_temp[$j]['stripe_way'] = __('Charge', 'payment-gateway-stripe-and-woocommerce-integration');
                $stripe_temp[$j]['order_status'] = $order->get_status();
                $stripe_temp[$j]['user_id'] = ($order->get_user_id()) ? $order->get_user_id() : 'guest';
                if ($stripe_temp[$j]['user_id'] === 'guest') {
                    $stripe_temp[$j]['user_name'] = __('Guest', 'payment-gateway-stripe-and-woocommerce-integration');
                } else {
                    $stripe_temp[$j]['user_name'] = get_user_meta($order->get_user_id(), 'first_name', true) . ' ' . get_user_meta($order->get_user_id(), 'last_name', true);
                }
                $stripe_temp[$j]['user_email'] = (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_email : $order->get_billing_email();
                $stripe_temp[$j]['type'] = (isset($data[$k]['captured']) ? $data[$k]['captured'] : '');
                $stripe_temp[$j]['source'] = (isset($data[$k]['source_type']) ? $data[$k]['source_type'] : '');
                $stripe_temp[$j]['transaction_id'] = (isset($data[$k]['transaction_id']) ? $data[$k]['transaction_id'] : '');
                $stripe_temp[$j]['status'] = (isset($data[$k]['status']) ? $data[$k]['status'] : '');
                $stripe_temp[$j]['amount'] = (isset($data[$k]['amount']) ? $data[$k]['amount'] : '');
                $stripe_temp[$j]['amount_refunded'] = (isset($data[$k]['amount_refunded']) ? $data[$k]['amount_refunded'] : '');
                $stripe_temp[$j]['currency'] = (isset($data[$k]['currency']) ? $data[$k]['currency'] : '');
                $stripe_temp[$j]['created'] = (isset($data[$k]['origin_time']) ? $data[$k]['origin_time'] : '');
                $j++;
            }
            for ($k = 0; $k < $refund_count; $k++) {
                $data = EH_Helper_Class::wt_stripe_order_db_operations($order_id[$i], null, 'get', '_eh_stripe_payment_refund',null, false); 

                if (!empty($data) && !isset($data[$k])) 
                {
                    $keys = array_keys($data);
                    $k = end($keys);
                } 

                $order = wc_get_order($order_id[$i]);
                $stripe_temp[$j]['order_id'] = $order_id[$i];
                $stripe_temp[$j]['order_number'] = $order->get_order_number();
                $stripe_temp[$j]['stripe_way'] = __('Refund', 'payment-gateway-stripe-and-woocommerce-integration');
                $stripe_temp[$j]['order_status'] = $order->get_status();
                $stripe_temp[$j]['order_total'] = $order->get_total();
                $stripe_temp[$j]['user_id'] = ($order->get_user_id()) ? $order->get_user_id() : 'guest';
                if ($stripe_temp[$j]['user_id'] === 'guest') {
                    $stripe_temp[$j]['user_name'] = __('Guest', 'payment-gateway-stripe-and-woocommerce-integration');
                } else {
                    $stripe_temp[$j]['user_name'] = get_user_meta($order->get_user_id(), 'first_name', true) . ' ' . get_user_meta($order->get_user_id(), 'last_name', true);
                }
                $stripe_temp[$j]['user_email'] = (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_email : $order->get_billing_email();
                $stripe_temp[$j]['transaction_id'] = (isset($data[$k]['transaction_id']) ? $data[$k]['transaction_id'] : '');
                $stripe_temp[$j]['status'] = (isset($data[$k]['status']) ? $data[$k]['status'] : '');
                $stripe_temp[$j]['amount'] = (isset($data[$k]['amount']) ? $data[$k]['amount'] : '');
                $stripe_temp[$j]['currency'] = (isset($data[$k]['currency']) ? $data[$k]['currency'] : '');
                $stripe_temp[$j]['created'] = (isset($data[$k]['origin_time']) ? $data[$k]['origin_time'] : '');
                $j++;
            }
            for ($k = 0; $k < $balance_count; $k++) {
                $data = EH_Helper_Class::wt_stripe_order_db_operations($order_id[$i], null, 'get', '_eh_stripe_payment_balance',null, false);

                if (!empty($data) && !isset($data[$k])) 
                {
                    $keys = array_keys($data);
                    $k = end($keys);
                } 

                $order = wc_get_order($order_id[$i]);
                $stripe_temp[$j]['order_id'] = $order_id[$i];
                $stripe_temp[$j]['order_number'] = $order->get_order_number();
                $stripe_temp[$j]['stripe_way'] = __('Balance', 'payment-gateway-stripe-and-woocommerce-integration');
                $stripe_temp[$j]['order_status'] = $order->get_status();
                $stripe_temp[$j]['order_total'] = $order->get_total();
                $stripe_temp[$j]['user_id'] = ($order->get_user_id()) ? $order->get_user_id() : 'guest';
                if ($stripe_temp[$j]['user_id'] === 'guest') {
                    $stripe_temp[$j]['user_name'] = __('Guest', 'payment-gateway-stripe-and-woocommerce-integration');
                } else {
                    $stripe_temp[$j]['user_name'] = get_user_meta($order->get_user_id(), 'first_name', true) . ' ' . get_user_meta($order->get_user_id(), 'last_name', true);
                }
                $stripe_temp[$j]['user_email'] = (version_compare(WC()->version, '2.7.0', '<')) ? $order->billing_email : $order->get_billing_email();
                $stripe_temp[$j]['transaction_id'] = " ";
                $stripe_temp[$j]['status'] = 'succeeded';
                $stripe_temp[$j]['amount'] = (isset($data[$k]['balance_amount']) ? $data[$k]['balance_amount'] : '');
                $stripe_temp[$j]['currency'] = (isset($data[$k]['currency']) ? $data[$k]['currency'] : '');
                $stripe_temp[$j]['created'] = (isset($data[$k]['origin_time']) ? $data[$k]['origin_time'] : '');
                $j++;
            }
        }
        $this->stripe_data = $stripe_temp;
    }
    
    //Column to display order status
    function column_order_status($item) {
        return sprintf(
            '<mark class="%1$s tips"><span>%2$s</span></mark>',
            esc_attr( $item['order_status'] ),
            esc_html( ucfirst( $item['order_status'] ) )
        );
    }
    
    //Column to display order details
    function column_order($item) {
        $order_url = add_query_arg(
            array(
                'post'   => absint( $item['order_id'] ),
                'action' => 'edit',
            ),
            admin_url( 'post.php' )
        );

        $user_name = esc_html( $item['user_name'] );
        if ( 'guest' !== $item['user_id'] ) {
            $user_name = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url( add_query_arg( 'user_id', absint( $item['user_id'] ), admin_url( 'user-edit.php' ) ) ),
                esc_html( $item['user_name'] )
            );
        }

        return sprintf(
            '<span><a href="%1$s"><strong>#%2$s</strong></a> by %3$s<br>%4$s</span>',
            esc_url( $order_url ),
            esc_html( $item['order_number'] ),
            wp_kses_post( $user_name ),
            esc_html( $item['user_email'] )
        );
    }
    
    //Column to display transaction id
    function column_id($item) {
        $action = '';
        if ($item['stripe_way'] === 'Balance' && floatval($item['amount']) != 0) {
            $action = '<span style="width:80%;text-align: center;" class="button stripe_refund_button" id="' . esc_attr( absint( $item['order_id'] ) ) . '">' . esc_html__( 'Refund ', 'payment-gateway-stripe-and-woocommerce-integration' ) . esc_html( get_woocommerce_currency_symbol( strtoupper( $item['currency'] ) ) ) . esc_html( $item['amount'] ) . ' ' . '</span>';
        }
        return '<span>' . ( is_null( $item['transaction_id'] ) ? '-' : esc_html( $item['transaction_id'] ) ) . '</span>' . $action;
    }
    
    // Column to display transaction status
    function column_status($item) {
        $actions = '';
        switch ($item['stripe_way']) {
            case 'Charge':
                if ($item['type'] === 'Captured') {
                    $actions = '<span class="table-type-text" style="color:#7ad03a !important">' . esc_html__( 'Payment Complete', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>';
                } else {
                    $actions = '<span class="table-type-text" style="color:#39beef !important">' . esc_html__( 'Capture Pending', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>';
                }
                break;
            case 'Refund':
                
                $order_id = absint( $item['order_id'] );
                $ord = new WC_Order($order_id);
                $total_refund = $ord->get_total_refunded();
                
                
                if ($item['amount'] === floatval($item['order_total']) || $total_refund == floatval($item['order_total'])) {
                    $actions = '<span class="table-type-text" style="color:#39beef !important">' . esc_html__( 'Fully Refunded', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>';
                } else {
                    $actions = '<span class="table-type-text">' . esc_html__( 'Partially Refunded', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>';
                }
                break;
            case 'Balance':
                $actions = '<span class="table-type-text" style="color:#7ad03a !important">' . esc_html__( 'Transaction Successful', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>';
                break;
        }
        return $actions;
    }
    
    //Column to display order amount
    function column_amount($item) {
        $actions = '';
        switch ($item['stripe_way']) {
            case 'Charge':
                $actions = '<span class="table-type-text">' . esc_html__( 'Amount ', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span><br> ' . esc_html( get_woocommerce_currency_symbol( strtoupper( $item['currency'] ) ) ) . esc_html( $item['amount'] ) . ( ( $item['amount_refunded'] != 0 ) ? '<br><span class="table-type-text">' . esc_html__( 'Refunded : ', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span> ' . esc_html( get_woocommerce_currency_symbol( strtoupper( $item['currency'] ) ) ) . esc_html( $item['amount_refunded'] ) : '' );
                break;
            case 'Refund':
                $actions = '<span class="table-type-text">' . esc_html__( 'Refund ', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span><br>' . esc_html( get_woocommerce_currency_symbol( strtoupper( $item['currency'] ) ) ) . esc_html( $item['amount'] );
                break;
            case 'Balance':
                $actions = '<span class="table-type-text">' . esc_html__( 'Balance ', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span><br>' . esc_html( get_woocommerce_currency_symbol( strtoupper( $item['currency'] ) ) ) . esc_html( $item['amount'] );
                break;
        }
        return $actions;
    }
    
    //Column to display order date
    function column_date($item) {
        return '<span>' . esc_html( $item['created'] ) . '</span>';
    }
    
    //Column to display image of payment method
    function column_thumb($item) {
        $ext = version_compare(WC()->version, '2.6', '>=') ? '.svg' : '.png';
        $style = version_compare(WC()->version, '2.6', '>=') ? 'style="margin-left: 0.3em"' : '';
        $icon = '';
        if ($item['stripe_way'] === 'Charge') {
            if ((strpos($item['source'], 'Visa') !== false) || (strpos($item['source'], 'visa') !== false) ){
                $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/visa' . $ext) ) . '" alt="' . esc_attr__( 'Visa', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" width="32" title="' . esc_attr__( 'VISA', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" ' . $style . ' />';
            }
            if ((strpos($item['source'], 'MasterCard') !== false) || (strpos($item['source'], 'mastercard') !== false) ){
                $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard' . $ext) ) . '" alt="' . esc_attr__( 'Mastercard', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" width="32" title="' . esc_attr__( 'Master Card', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" ' . $style . ' />';
            }
            if ((strpos($item['source'], 'American Express') !== false) || (strpos($item['source'], 'amex') !== false) ){
                $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/amex' . $ext) ) . '" alt="' . esc_attr__( 'Amex', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" width="32" title="' . esc_attr__( 'American Express', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" ' . $style . ' />';
            }
            if ((strpos($item['source'], 'Discover') !== false) || (strpos($item['source'], 'discover') !== false) ){    
                $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/discover' . $ext) ) . '" alt="' . esc_attr__( 'Discover', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" width="32" title="' . esc_attr__( 'Discover', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" ' . $style . ' />';
            }
            if ((strpos($item['source'], 'JCB') !== false) || (strpos($item['source'], 'jcb') !== false) ){ 
                $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/jcb' . $ext) ) . '" alt="' . esc_attr__( 'JCB', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" width="32" title="' . esc_attr__( 'JCB', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" ' . $style . ' />';
            }
            if ((strpos($item['source'], 'Diners Club') !== false) || (strpos($item['source'], 'diners') !== false) ){   
                $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/diners' . $ext) ) . '" alt="' . esc_attr__( 'Diners', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" width="32" title="' . esc_attr__( 'Diners Club', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" ' . $style . ' />';
            }

            if (strpos($item['source'], 'Alipay') !== false) {
                $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url(EH_STRIPE_MAIN_URL_PATH . 'assets/img/alipay.png') ) . '" alt="' . esc_attr__( 'Alipay', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" width="52" title="' . esc_attr__( 'Alipay', 'payment-gateway-stripe-and-woocommerce-integration' ) . '" ' . $style . ' />';
            }
        }
        return $icon;
    }
    
    //Gets columns for the list table
    function get_columns() {

        return $columns = array(
            'thumb' => '<span class="wc-image">' . esc_html__( 'Image', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>',
            'order_status' => '<span class="status_head tips">' . esc_html__( 'Status', 'payment-gateway-stripe-and-woocommerce-integration' ) . '</span>',
            'order' => esc_html__( 'Order', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'id' => esc_html__( 'Transaction ID', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'status' => esc_html__( 'Status', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'amount' => esc_html__( 'Amount', 'payment-gateway-stripe-and-woocommerce-integration' ),
            'date' => esc_html__( 'Date', 'payment-gateway-stripe-and-woocommerce-integration' )
        );
    }
    
    //Returns columns to be made sortable
    function get_sortable_columns() {
        $sortable_columns = array(
        );
        return $sortable_columns;
    }
    
    //compares two dates
    function date_compare($a, $b) {
        $t1 = strtotime($a['created']);
        $t2 = strtotime($b['created']);
        return $t1 < $t2 ? 1 : -1;
    }
    
    //Preparing table with table elements
    function prepare_items($page_num = '', $prepare = '', $page_count = '') {
        $per_page = (get_option('eh_stripe_table_row')) ? get_option('eh_stripe_table_row') : 20;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array(
            $columns,
            $hidden,
            $sortable
        );
        $data = $this->stripe_data;
        usort($data, array($this, 'date_compare'));
        $current_page = ($page_num == '') ? $this->get_pagenum() : $page_num;
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    //Renders the complete list table to the page
    function display() {
        parent::display();
    }
    
    //Handles incoming Ajax requests
    function ajax_response() {

        $this->prepare_items();

        extract($this->_args);
        extract($this->_pagination_args, EXTR_SKIP);

        ob_start();
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
        if (!empty($_REQUEST['no_placeholder'])) {
            $this->display_rows();
        } else {
            $this->display_rows_or_placeholder();
        }
        $rows = ob_get_clean();

        ob_start();
        $this->print_column_headers();
        $headers = ob_get_clean();

        ob_start();
        $this->pagination('top');
        $pagination_top = ob_get_clean();

        ob_start();
        $this->pagination('bottom');
        $pagination_bottom = ob_get_clean();

        $response = array(
            'rows' => $rows
        );
        $response['pagination']['top'] = $pagination_top;
        $response['pagination']['bottom'] = $pagination_bottom;
        $response['column_headers'] = $headers;

        if (isset($total_items)) {
            /* translators: %s: Number of items */
            $response['total_items_i18n'] = sprintf(_n('%s item', '%s items', $total_items, 'payment-gateway-stripe-and-woocommerce-integration'), number_format_i18n($total_items));
        }

        if (isset($total_pages)) {
            $response['total_pages'] = $total_pages;
            $response['total_pages_i18n'] = number_format_i18n($total_pages);
        }

        die(wp_json_encode($response));
    }

}

function eh_spg_order_ajax_data_callback() {

    if(!EH_Helper_Class::check_write_access(EH_STRIPE_PLUGIN_NAME, 'ajax-eh-spg-nonce'))
    {
       die(esc_html__('You are not allowed to view this page.', 'payment-gateway-stripe-and-woocommerce-integration'));
    }
    $obj = new Eh_Stripe_Order_Datatables();
    $obj->input();
    $obj->ajax_response();
}

add_action('wp_ajax_eh_spg_order_ajax_table_data', 'eh_spg_order_ajax_data_callback');

function eh_spg_stripe_ajax_data_callback() {

    if(!EH_Helper_Class::check_write_access(EH_STRIPE_PLUGIN_NAME, 'ajax-eh-spg-nonce'))
    {
       die(esc_html__('You are not allowed to view this page.', 'payment-gateway-stripe-and-woocommerce-integration'));
    }
    $obj = new Eh_Stripe_Datatables();
    $obj->input();
    $obj->ajax_response();
}

add_action('wp_ajax_eh_spg_stripe_ajax_table_data', 'eh_spg_stripe_ajax_data_callback');

/**
 * This function adds the jQuery script to the plugin's page footer
 */
function eh_spg_admin_header() {
    //phpcs:ignore WordPress.Security.NonceVerification.Recommended     
    $page = (isset($_GET['page'])) ? sanitize_text_field(wp_unslash($_GET['page'])) : false;
    if ('eh-stripe-overview' != $page){
        return;
    }
    //phpcs:ignore WordPress.Security.NonceVerification.Recommended 
    $tab = (!empty($_GET['tab'])) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'orders';
    if ($tab === 'orders') {
        echo '<style type="text/css">';
        echo '.wp-list-table { text-align:center ;}';
        echo 'table th{ text-align:center !important;}';
        echo '.wp-list-table .column-date { width: 10%;}';
        echo '.wp-list-table .column-order_actions { width: 10%; vertical-align:middle;}';
        echo '.wp-list-table .column-price { width: 10%;}';
        echo '.wp-list-table .column-p_actions { vertical-align:middle;}';
        echo '</style>';
    } else {
        echo '<style type="text/css">';
        echo '.wp-list-table { text-align:center ;}';
        echo 'table th{ text-align:center !important;}';
        echo '.wp-list-table .column-date { width: 10%;}';
        echo '.wp-list-table .column-status { width: 15%;}';
        echo '.wp-list-table .column-amount { width: 15%;}';
        echo '</style>';
    }
}

function eh_spg_ajax_table_script() {
    $screen = get_current_screen();
    if ('webtoffee-stripe_page_eh-stripe-overview' != $screen->id)
        return false;
        ?>
        <script type="text/javascript">
            (function (jQuery) {

                list = {
                    init: function (section, action) {

                        // This will have its utility when dealing with the page number input
                        var timer;
                        var delay = 500;

                        // Pagination links, sortable link
                        jQuery('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function (e) {
                            // We don't want to actually follow these links
                            e.preventDefault();
                            // Simple way: use the URL to extract our needed variables
                            var query = this.search.substring(1);

                            var data = {
                                paged: list.__query(query, 'paged') || '1',
                            };
                            list.update(data,section, action);
                        });

                        // Page number input
                        jQuery('input[name=paged]').on('keyup', function (e) {
                            if (13 == e.which)
                                e.preventDefault();

                            // This time we fetch the variables in inputs
                            var data = {
                                paged: parseInt(jQuery('input[name=paged]').val()) || '1',
                            };
                            window.clearTimeout(timer);
                            timer = window.setTimeout(function () {
                                list.update(data, section, action);
                            }, delay);
                        });
                    },
                    update: function (data,section,action) {
                        jQuery("#"+ section +"  .loader").css("display", "block");
                        jQuery.ajax({

                            // /wp-admin/admin-ajax.php
                            url: ajaxurl,
                            // Add action and nonce to our collected data
                            data: jQuery.extend({
                                _wpnonce: jQuery('#_ajax_eh_spg_nonce').val(),
                                action: action,
                            },
                                    data
                                    ),
                            // Handle the successful result
                            success: function (response) {
                                jQuery("#"+ section +" .loader").css("display", "none");
                                // WP_List_Table::ajax_response() returns json
                                var response = jQuery.parseJSON(response);
                                // Add the requested rows
                                if (response.rows.length)
                                    jQuery('#the-list').html(response.rows);
                                // Update column headers for sorting
                                if (response.column_headers.length)
                                    jQuery('thead tr, tfoot tr').html(response.column_headers);
                                // Update pagination for navigation
                                if (response.pagination.bottom.length)
                                    jQuery('.tablenav.top .tablenav-pages').html(jQuery(response.pagination.top).html());
                                if (response.pagination.top.length)
                                    jQuery('.tablenav.bottom .tablenav-pages').html(jQuery(response.pagination.bottom).html());

                                // Init back our event handlers
                                list.init(section,action);
                            }
                        });
                    },
                    __query: function (query, variable) {

                        var vars = query.split("&");
                        for (var i = 0; i < vars.length; i++) {
                            var pair = vars[i].split("=");
                            if (pair[0] == variable)
                                return pair[1];
                        }
                        return false;
                    },
                }

            })(jQuery);
        </script>
        <?php
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        $tab = (!empty($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'orders'); 
        if ($tab === 'orders') { 

        ?>
        <script type="text/javascript">
            (function (jQuery) {
                
                // Show time!
                list.init('order_section' , 'eh_spg_order_ajax_table_data');

            })(jQuery);
        </script>
        <?php

    } else { //else tab 'stripe'
        ?>
        <script type="text/javascript">
            (function (jQuery) {

                // Show time!
                list.init('stripe_section' , 'eh_spg_stripe_ajax_table_data');

            })(jQuery);
        </script>
        <?php

    }
}


?>
<?php

add_action('admin_head', 'eh_spg_admin_header');
add_action('admin_footer', 'eh_spg_ajax_table_script');
