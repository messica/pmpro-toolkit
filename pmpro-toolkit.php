<?php

/**
 * Plugin Name: PMPro Developer's Toolkit
 * Author: Stranger Studios
 * Description: Various tools to test and debug Paid Memberships Pro enabled websites.
 * Version: .1.1
 */

/*
 * Globals
 */
global $pmprodev_options, $gateway;

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

*/

/*
 * Gateway Debug Constants
 */
function pmprodev_gateway_debug_setup() {

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
add_action('init', 'pmprodev_gateway_debug_setup');

/*
 * Redirect PMPro Emails
 */
function pmprodev_redirect_emails($recipient, $email) {

    global $pmprodev_options;

    if(!empty($pmprodev_options['redirect_email']))
        $recipient = $pmprodev_options['redirect_email'];

    return $recipient;
}
add_filter('pmpro_email_recipient', 'pmprodev_redirect_emails', 10, 2);

/*
 * Send debug email every time checkout page is hit.
 */
function pmprodev_checkout_debug_email($level) {

    global $pmprodev_options, $current_user, $wpdb;

    if(empty($pmprodev_options['checkout_debug_email']))
        return $level;

    $email = new PMProEmail();

    if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
        $http = 'https://';
    else
        $http = 'http://';

    $email->subject = sprintf('%s Checkout Page Debug Log', get_bloginfo('name'));
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
add_filter('pmpro_checkout_level', 'pmprodev_checkout_debug_email');

/*
 * View as specific Membership Level
 */

//create cookie based on query string parameters
function pmprodev_view_as_init() {

    global $current_user, $pmprodev_options;

    $view_as_level_ids = $_REQUEST['pmprodev_view_as'];
    $membership_level_capability = apply_filters('pmpro_edit_member_capability', 'manage_options');

    if(!empty($view_as_level_ids) && !empty($pmprodev_options['view_as_enabled']) && current_user_can($membership_level_capability)) {

        //are we resetting the filter?
        if($view_as_level_ids == 'r')
            setcookie('pmprodev_view_as', '', 0);
        else
            setcookie('pmprodev_view_as', $view_as_level_ids, null);
    }
}
add_action('init', 'pmprodev_view_as_init');

//override pmpro_has_membership_access
function pmprodev_view_as_access_filter($hasaccess, $post, $user, $levels) {

    global $pmprodev_options;

    $view_as_level_ids = $_COOKIE['pmprodev_view_as'];
    $membership_level_capability = apply_filters('pmpro_edit_member_capability', 'manage_options');

    if(isset($view_as_level_ids) && current_user_can($membership_level_capability)) {

        //default to false to override any real membership levels
        $hasaccess = false;

        //get level ids for this post
        $post_level_ids = array();
        foreach($levels as $key=>$level)
            $post_level_ids[] = $level->id;

        //get view as level ids from cookie
        $view_as_level_ids = explode('-', $view_as_level_ids);

        foreach($view_as_level_ids as $id) {
            //return true when we find a match
            if(in_array($id, $post_level_ids))
                $hasaccess = true;
        }
    }

    return $hasaccess;
}
add_filter('pmpro_has_membership_access_filter', 'pmprodev_view_as_access_filter', 10, 4);

//override pmpro_hasMembershipLevel() function
//TODO: figure out why this is running before cookie is set...
function pmprodev_view_as_has_membership_level($return, $user_id, $levels) {

    global $pmprodev_options;

    $view_as_level_ids = $_COOKIE['pmprodev_view_as'];
    $membership_level_capability = apply_filters('pmpro_edit_member_capability', 'manage_options');

    if(isset($view_as_level_ids) && current_user_can($membership_level_capability)) {

        //if we're checking for "0"
        if($levels == '0' && $view_as_level_ids == 'n')
            return true;

        //make levels array if it's not already
        if(!is_array($levels))
            $levels = array($levels);

        //get view as level ids from cookie
        $view_as_level_ids = explode('-', $view_as_level_ids);

        foreach($view_as_level_ids as $id) {
            if(in_array($id, $levels))
                return true;
        }

        //default to false to overrdide real levels
        return false;
    }
}
add_filter('pmpro_has_membership_level', 'pmprodev_view_as_has_membership_level', 10, 3);