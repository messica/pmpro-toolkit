<?php

/**
 * Plugin Name: PMPro Developer's Toolkit
 * Author: Stranger Studios
 * Description: Various tools to test and debug Paid Memberships Pro enabled websites.
 * Version: .1
 */

/*
 * Globals
 */
global $pmprodev_options, $gateway;

/*
 * Default Options
 */
$pmprodev_options = get_option('pmprodev_options');
//if(empty($pmprodev_options)) {
//    $pmprodev_options = array(
//        'ipn_debug' => 0,
//        'authnet_silent_post_debug' => 0,
//        'stripe_webhook_debug' => 0,
//        'ins_debug' => 0,
//        'debug_email' => 0,
//        'redirect_email' => 0,
//        'checkout_debug_email' => 0,
//    );
//    update_option('pmprodev_options', $pmprodev_options);
//}
if(empty($pmprodev_options)) {
    $pmprodev_options = array(
        'ipn_debug' => 'paidmembershipsprotest+ipn_debug@gmail.com',
        'authnet_silent_post_debug' => 'paidmembershipsprotest+authnet_silent_post_debug@gmail.com',
        'stripe_webhook_debug' => 'paidmembershipsprotest+stripe_webhook_debug@gmail.com',
        'ins_debug' => 'paidmembershipsprotest+ins_debug@gmail.com',
        'redirect_email' => 'paidmembershipsprotest+redirecct_email@gmail.com',
        'checkout_debug_email' => 'paidmembershipsprotest+checkout_debug_email@gmail.com',
    );
    update_option('pmprodev_options', $pmprodev_options);
}

/*
 * Debug Constants
 */
function pmprodev_init() {

    global $pmprodev_options;

    if(!empty($pmprodev_options['ipn_debug']) && !defined('PMPRO_IPN_DEBUG'))
        define('PMPRO_IPN_DEBUG', $pmprodev_options['ipn_debug']);

    if(!empty($pmprodev_options['authnet_silent_post_debug']) && !defined('PMPRO_AUTHNET_SILENT_POST_DEBUG'))
        define('PMPRO_AUTHNET_SILENT_POST_DEBUG', $pmprodev_options['authnet_silent_post_debug']);

    if(!empty($pmprodev_options['stripe_webhook_debug']) && !defined('PMPRO_STRIPE_WEBHOOK_DEBUG'))
        define('PMPRO_STRIPE_WEBHOOK_DEBUG', $pmprodev_options['stripe_webhook_debug']);

    if(!empty($pmprodev_options['ins_debug']) && !defined('PMPRO_INS_DEBUG'))
        define('PMPRO_INS_DEBUG', $pmprodev_options['ins_debug']);

}

/*
 * Redirect PMPro Emails
 */
function pmprodev_pmpro_email_recipient($recipient, $email) {

    global $pmprodev_options;

    if(!empty($pmprodev_options['redirect_email']))
        $recipient = $pmprodev_options['redirect_email'];

    return $recipient;
}

/*
 * Send debug email every time checkout page is hit.
 */
function pmprodev_pmpro_checkout_level($level) {

    global $pmprodev_options, $current_user, $wpdb;

    if(empty($pmprodev_options['checkout_debug_email']))
        return $level;

    $email = new PMProEmail();

    if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
        $http = 'https://';
    else
        $http = 'http://';

    $email->subject = sprintf('%s Checkout Page Debug Information', get_bloginfo('name'));
    $email->recipient = $pmprodev_options['checkout_debug_email'];
    $email->template = 'checkout_debug';
    $email->body = file_get_contents(plugin_dir_path(__FILE__) . '/email/checkout_debug.html');
    $email->data = array(
        'sitename' => get_bloginfo('sitename'),
        'checkout_url' => $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'submit' => print_r($_REQUEST['submit-checkout'], true),
        'level' => print_r($level, true),
        'user' => print_r($current_user->data, true),
        'request' => print_r($_REQUEST, true)
    );

    $order = new MemberOrder();
    $order->getLastMemberOrder($current_user->user_id);

    if(!empty($order))
        $email->data['order'] = print_r($order, true);

    return $level;
}

/*
 * Hooks
 */
add_action('init', 'pmprodev_init');

/*
 * Filters
 */
add_filter('pmpro_email_recipient', 'pmprodev_pmpro_email_recipient', 10, 2);
add_filter('pmpro_checkout_level', 'pmprodev_pmpro_checkout_level');
