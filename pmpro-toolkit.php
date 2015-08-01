<?php
/**
 * Plugin Name: Paid Memberships Pro - Developer's Toolkit
 * Author: Stranger Studios
 * Description: Various tools to test and debug Paid Memberships Pro enabled websites.
 * Version: .4
 */

/*
 * Globals
 */
global $pmprodev_options, $gateway;

$pmprodev_options = get_option('pmprodev_options');
if(empty($pmprodev_options)) {
    $pmprodev_options = array(
        'ipn_debug' => '',
        'authnet_silent_post_debug' => '',
        'stripe_webhook_debug' => '',
        'ins_debug' => '',
        'redirect_email' => '',
        'checkout_debug_email' => '',
        'view_as_enabled' => false
    );
}

/*
 * Gateway Debug Constants
 */
function pmprodev_gateway_debug_setup() {

    global $pmprodev_options;

	//define IPN/webhook debug emails
    if(!empty($pmprodev_options['ipn_debug']) && !defined('PMPRO_IPN_DEBUG'))
        define('PMPRO_IPN_DEBUG', $pmprodev_options['ipn_debug']);

    if(!empty($pmprodev_options['ipn_debug']) && !defined('PMPRO_AUTHNET_SILENT_POST_DEBUG'))
        define('PMPRO_AUTHNET_SILENT_POST_DEBUG', $pmprodev_options['ipn_debug']);

    if(!empty($pmprodev_options['ipn_debug']) && !defined('PMPRO_STRIPE_WEBHOOK_DEBUG'))
        define('PMPRO_STRIPE_WEBHOOK_DEBUG', $pmprodev_options['ipn_debug']);

    if(!empty($pmprodev_options['ipn_debug']) && !defined('PMPRO_INS_DEBUG'))
        define('PMPRO_INS_DEBUG', $pmprodev_options['ipn_debug']);
		
	//unhook crons
	if(!empty($pmprodev_options['expire_memberships']))
		remove_action("pmpro_cron_expire_memberships", "pmpro_cron_expire_memberships");
	if(!empty($pmprodev_options['expiration_warnings']))
		remove_action("pmpro_cron_expiration_warnings", "pmpro_cron_expiration_warnings");
	if(!empty($pmprodev_options['credit_card_expiring']))
		remove_action("pmpro_cron_credit_card_expiring_warnings", "pmpro_cron_credit_card_expiring_warnings");	
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

    $email->sendEmail();

    return $level;
}
add_filter('pmpro_checkout_level', 'pmprodev_checkout_debug_email');

/*
 * View as specific Membership Level
 */

//create cookie based on query string parameters
function pmprodev_view_as_init() {

    global $current_user, $pmprodev_options;

    if(!empty($_REQUEST['pmprodev_view_as']))
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

	if(!empty($_COOKIE['pmprodev_view_as']))
		$view_as_level_ids = $_COOKIE['pmprodev_view_as'];
	else
		$view_as_level_ids = NULL;   
   
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

    if(!empty($_COOKIE['pmprodev_view_as']))
		$view_as_level_ids = $_COOKIE['pmprodev_view_as'];
	else
		$view_as_level_ids = NULL;
		
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
	
	return $return;
}
add_filter('pmpro_has_membership_level', 'pmprodev_view_as_has_membership_level', 10, 3);

/*
 * Add settings page
 */
function pmprodev_admin_menu() {
    add_options_page('PMPro Toolkit Settings', 'PMPro Toolkit', apply_filters('pmpro_edit_member_capability', 'manage_options'), 'pmprodev', 'pmprodev_settings_page');	
	add_management_page('PMPro Toolkit Scripts', 'PMPro Toolkit Scripts', 'manage_options', 'pmprodev-database-scripts', 'pmprodev_database_scripts_page');
}
add_action('admin_menu', 'pmprodev_admin_menu');

function pmprodev_database_scripts_page()
{
	require_once(dirname(__FILE__) . "/adminpages/scripts.php");
}

function pmprodev_admin_init() {

    //register setting
    register_setting('pmprodev_options', 'pmprodev_options');

    //add settings sections
    add_settings_section('pmprodev-email', 'Email Debugging', 'pmprodev_email_settings', 'pmprodev');
	add_settings_section('pmprodev-cron', 'Scheduled Cron Job Debugging', 'pmprodev_cron_settings', 'pmprodev');    
	add_settings_section('pmprodev-gateway', 'Gateway/Checkout Debugging', 'pmprodev_gateway_settings', 'pmprodev');    
    add_settings_section('pmprodev-view-as', '"View as..."', 'pmprodev_view_as_settings', 'pmprodev');
		
    //add settings fields
    add_settings_field('redirect_email', 'Redirect PMPro Emails', 'pmprodev_settings_redirect_email', 'pmprodev', 'pmprodev-email');    
	
	add_settings_field('cron-expire-memberships', 'Expire Memberships', 'pmprodev_settings_cron_expire_memberships', 'pmprodev', 'pmprodev-cron');   
	add_settings_field('cron-expiration-warnings', 'Expiration Warnings', 'pmprodev_settings_cron_expiration_warnings', 'pmprodev', 'pmprodev-cron');
	add_settings_field('cron-credit-card-expiring', 'Credit Card Expirations', 'pmprodev_settings_cron_credit_card_expiring', 'pmprodev', 'pmprodev-cron');	
	
	add_settings_field('ipn-debug', 'Gateway Callback Debug Email', 'pmprodev_settings_ipn_debug', 'pmprodev', 'pmprodev-gateway');   
    add_settings_field('checkout_debug_email', 'Send Checkout Debug Email', 'pmprodev_settings_checkout_debug_email', 'pmprodev', 'pmprodev-gateway');
	
    add_settings_field('view_as_enabled', 'Enable "View As" feature', 'pmprodev_settings_view_as_enabled', 'pmprodev', 'pmprodev-view-as');		
}
add_action('admin_init', 'pmprodev_admin_init');

function pmprodev_settings_page() {
    require_once(plugin_dir_path(__FILE__) . '/adminpages/settings.php');
}