<?php
/*
Plugin Name: ABA PayWay Payment Gateway for WooCommerce
Plugin URI: https://wordpress.org/plugins/aba-payway-woocommerce-payment-gateway/#description
Description: PayWay is Cambodia’s leading online payment gateway provided by Advanced Bank of Asia Ltd. (ABA Bank).
Author: ABA Bank
Author URI: https://www.ababank.com/
Version: 1.0.9
*/

if (!defined('ABSPATH'))
    die("Can't load this file directly");

if (in_array(
    'woocommerce/woocommerce.php',
    apply_filters('active_plugins', get_option('active_plugins'))
));
define('ABA_PAYMENT_PLUGIN_PATH', WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/');

add_action('plugins_loaded', 'aba_payway_aim_init', 0);
function aba_payway_aim_init()
{
    //if condition use to do nothin while WooCommerce is not installed
    if (!class_exists('WC_Payment_Gateway')) return;
    include_once('PayWayApiCheckout.php');

    // class add it too WooCommerce
    add_filter('woocommerce_payment_gateways', 'add_aba_payway_gateway');
    function add_aba_payway_gateway($methods)
    {
        $methods[] = 'aba_payway_aim';
        return $methods;
    }
}

// Add custom action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'aba_payway_aim_action_links');
function aba_payway_aim_action_links($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'aba-payway-aim') . '</a>',
    );
    return array_merge($plugin_links, $links);
}

function aba_render_checkout_frm($woocommerce, $transactionId, $amount, $items, $shipping_fee)
{
    $first_name = '';
    $last_name = '';
    $email = '';
    $phone = '';
    if (is_array($woocommerce->customer->billing)) {
        $first_name = $woocommerce->customer->billing['first_name'];
        $last_name = $woocommerce->customer->billing['last_name'];
        $email = $woocommerce->customer->billing['email'];
        $phone = $woocommerce->customer->billing['phone'];
    }
    $cart_url = get_permalink(wc_get_page_id('cart'));

    echo '<div id="aba_main_modal" class="aba-modal">';
    echo '<div class="aba-modal-content">';
    echo '<form method="POST" target="aba_webservice" action="' . aba_PAYWAY_AIM::getEndpointApiUrl() . '" id="aba_merchant_request" target="aba_webservice" name="aba_merchant_request">';
    echo '<input type="hidden" name="req_time" value="' . date("YYYYmdHis") . '" id="req_time">';
    echo '<input type="hidden" name="merchant_id" value="' . aba_PAYWAY_AIM::getMerchantId() . '" id="merchant_id">';
    echo '<input type="hidden" name="api_version" value="v1">';
    echo '<input type="hidden" name="hash" id="hash" value="' . aba_PAYWAY_AIM::getHash($transactionId, $amount, $items, $shipping_fee) . '"/>';
    echo '<input type="hidden" name="tran_id" id="tran_id" value="' . $transactionId . '"/>';
    echo '<input type="hidden" name="amount" value="' . $amount . '" />';
    echo '<input type="hidden" name="firstname" value="' . $first_name . '" />';
    echo '<input type="hidden" name="lastname" value="' . $last_name . '" />';
    echo '<input type="hidden" name="phone" value="' . $phone . '"  />';
    echo '<input type="hidden" name="email" value="' . $email . '" />';
    if ($shipping_fee > 0) {
        echo '<input type="hidden" name="shipping" value="' . $shipping_fee . '" />';
    }
    echo '<input type="hidden" name="cancel_url" value="' . base64_encode($cart_url) . '" id="cancel_url">';
    echo '<input type="hidden" name="return_url" value="' . base64_encode(aba_PAYWAY_AIM::getReturnURL()) . '" id="return_url">';
    echo '<input type="hidden" name="return_params" value="json" id="return_params">';
    echo '<input type="hidden" name="items" value="' . $items . '" id="items"/>';
    echo '<input type="hidden" name="continue_success_url" id="continue_success_url" value="' . aba_PAYWAY_AIM::getContinueSuccessUrl() . '">';
    echo '<input type="hidden" name="payment_option" value="cards"/>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="aba_api_key" id="aba_api_key" value="' . (aba_PAYWAY_AIM::getApiKey() == "" ? '' : '1') . '">';
    echo '<input type="hidden" name="aba_api_url" id="aba_api_url" value="' . aba_PAYWAY_AIM::getApiUrl() . '">';
    echo '<input type="hidden" id="aba_enable_payment" value="' . aba_PAYWAY_AIM::getPaymentOption() . '">';
    echo '<input type="hidden" id="aba_is_enable" value="' . aba_PAYWAY_AIM::isEnabled() . '">';

    echo '<input type="hidden" id="aba_logo_aba" value="' . aba_PAYWAY_AIM::getImageUrl('aba') . '">';
    echo '<input type="hidden" id="aba_logo_cards" value="' . aba_PAYWAY_AIM::getImageUrl('cards') . '">';
    echo '<input type="hidden" id="aba_logo_all_cards" value="' . aba_PAYWAY_AIM::getImageUrl('credit_debit') . '">';
    echo '<input type="hidden" id="aba_bg_color" value="' . aba_PAYWAY_AIM::getBackgroundColor() . '">';

    echo '<input type="hidden" id="aba_choosen_method" value="' . (isset($_SESSION['aba_choosen_method']) ? $_SESSION['aba_choosen_method'] : '') . '">';
}

//Add Payway form in checkout page
add_action('woocommerce_after_checkout_form', 'aba_woocommerce_after_checkout_form', 10, 1);

function aba_woocommerce_after_checkout_form()
{
    global $woocommerce;
    $transactionId = rand();
    $_SESSION['aba_transaction_id'] = $transactionId;
    $shipping_fee = WC()->cart->get_shipping_total();
    $shipping_fee = sprintf("%.2f", $shipping_fee);
    $amount = sprintf("%.2f", $woocommerce->cart->get_cart_contents_total() + $woocommerce->cart->get_total_tax());
    aba_render_checkout_frm($woocommerce, $transactionId, $amount, null, $shipping_fee);
}

add_action('after_woocommerce_pay', 'aba_woocommerce_after_after_woocommerce_pay', 10, 1);

function aba_woocommerce_after_after_woocommerce_pay()
{
    global $woocommerce;

    $order_id = $woocommerce->session->order_awaiting_payment;
    $_SESSION['aba_transaction_id'] = $order_id;

    $order = new WC_Order($order_id);

    $shipping_fee = $order->get_shipping_total();

    $amount = sprintf("%.2f", $order->get_total() - $shipping_fee);
    $shipping_fee = sprintf("%.2f", $shipping_fee);

    $item = aba_PAYWAY_AIM::itemDetail($order);

    $items = base64_encode(json_encode($item));
    aba_render_checkout_frm($woocommerce, $order_id, $amount, $items, $shipping_fee);
}

add_action('wp_enqueue_scripts', 'aba_payway_scripts_basic');
function aba_payway_scripts_basic()
{
    $pay_css = aba_PAYWAY_AIM::getCsssend();
    $pay_js = aba_PAYWAY_AIM::getJssend();
    $hide_close = aba_PAYWAY_AIM::getHideclose();
    wp_deregister_script('wc-checkout');
    wp_enqueue_script('wc-checkout', ABA_PAYMENT_PLUGIN_PATH . 'js/checkout.js', array('jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n'), time(), true);
    wp_enqueue_script('aba-payway-custom-script', ABA_PAYMENT_PLUGIN_PATH . 'js/custom.js', array('jquery'), time(), true);
    $query_url = parse_url($pay_js, PHP_URL_QUERY);
    if ($query_url) {
        $pay_js .= '&' . $hide_close;
    } else {
        $pay_js .= '?' . $hide_close;
    }
    wp_enqueue_script('checkout-popup', $pay_js, false);

    wp_localize_script(
        'aba-payway-custom-script',
        'frontend_ajax_object',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        )
    );

    wp_enqueue_style('aba-payway-checkout-style-icon', plugins_url('css/payway.css?v=1.1', __FILE__));
    wp_enqueue_style('aba-payway-checkout-style', $pay_css);
}

add_action('init', 'aba_payway_start_session', 1);
function aba_payway_start_session()
{
    if (!session_id()) {
        session_start();
    }
}

add_action("wp_ajax_aba_check_payment_cancel_order", "aba_check_payment_cancel_order");
add_action("wp_ajax_nopriv_aba_check_payment_cancel_order", "aba_check_payment_cancel_order");
function aba_check_payment_cancel_order()
{
    if (!empty($_SESSION['aba_order_id'])) {
        $order = new WC_Order($_SESSION['aba_order_id']);
        if ($order->status == 'pending') {
            $order->update_status('cancelled');
        }
    }
    exit;
}

// add a wp query variable to redirect to
add_action('query_vars', 'set_aba_query_var');
function set_aba_query_var($vars)
{
    array_push($vars, 'aba_payway_pushback'); // ref url redirected to in add rewrite rule
    return $vars;
}

// Create a redirect
add_action('init', 'custom_add_rewrite_rule');
function custom_add_rewrite_rule()
{
    add_rewrite_rule('^abapayway/pushback$', 'index.php?aba_payway_pushback=1', 'top');
    //flush the rewrite rules, should be in a plugin activation hook, i.e only run once...
    flush_rewrite_rules();
}

// return the file we want...
add_filter('template_include', 'plugin_include_template');
function plugin_include_template($template)
{
    if (get_query_var('aba_payway_pushback')) {
        global $wpdb, $woocommerce;
        if (isset($_REQUEST['response'])) {
            $response = json_decode(stripslashes($_REQUEST['response']), TRUE);
            if ($response['status'] == 0 || $response['status'] == "0") {
                if (aba_PAYWAY_AIM::checkTransaction($response['tran_id'])) {
                    $order_id = $response['tran_id'];
                    $order = new WC_Order($order_id);
                    $order->update_status('processing');
                    //$order->payment_complete();
                    $woocommerce->cart->empty_cart();
                    echo 'Code: #0000';
                    exit;
                } else {
                    echo 'Code: #0001';
                    exit;
                }
            } else {
                echo 'Code: #0002';
                exit;
            }
        } else {
            echo 'Code: #0003';
            exit;
        }
        exit;
    }
    return $template;
}
