<?php

/**
 * Plugin Name: PMPro Developer's Toolkit
 * Author: Stranger Studios
 * Description: Adds various tools and settings to aid in the development of Paid Memberships Pro enabled websites.
 * Version: .1
 */

/*
 * Default Options
 * Copy to your own functions.php or customizations plugin, uncomment, and modify as necessary

global $pmprodev_options
$pmprodev_options = array(
    'ipn_debug'                 => 'example@domain.com',
    'authnet_silent_post_debug' => 'example@domain.com',
    'stripe_webhook_debug'      => 'example@domain.com',
    'ins_debug'                 => 'example@domain.com',
    'redirect_email'            => 'example@domain.com',
    'checkout_debug_email'      => 'example@domain.com',
);

* /

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
add_action('init', 'pmprodev_init');


/*
 * Redirect PMPro Emails
 */
function pmprodev_pmpro_email_recipient($recipient, $email) {

    global $pmprodev_options;

    if(!empty($pmprodev_options['redirect_email']))
        $recipient = $pmprodev_options['redirect_email'];

    return $recipient;
}
add_filter('pmpro_email_recipient', 'pmprodev_pmpro_email_recipient', 10, 2);


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
add_filter('pmpro_checkout_level', 'pmprodev_pmpro_checkout_level');