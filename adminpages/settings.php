<?php

/*
 * Display Settings Sections
 */
function pmprodev_gateway_settings() {
    ?>
    <p>Enable debugging for PayPal IPNs, Authorize.net Silent Posts, Stripe Webhook, etc.<br>Enter the email address you would like the logs to be emailed to, or leave blank to disable.</p>
    <?php
}
function pmprodev_email_settings() {}
function pmprodev_view_as_settings() {
    global $wpdb;
    //get example level info
    $level = $wpdb->get_row('SELECT * FROM ' . $wpdb->pmpro_membership_levels . ' LIMIT 1');
    $level_name = $level->name;
    $level_id = $level->id;
    $example_link = '<a href="' . add_query_arg('pmprodev_view_as', $level_id, home_url()) . '">' . add_query_arg('pmprodev_view_as', $level_id, home_url()) . '</a>';
    ?>
    <p>
        Enabling "View as..." will allow admins to view any page as if they had any membership level(s) for a brief period of time.<br>
        To use it, add the query string parameter <code>pmprodev_view_as</code> to your URL, passing a series of level IDs separated by hyphens.
    </p>
    <p>
        For example, view your homepage as <?php echo $level_name; ?> with the link <?php echo $example_link; ?>
    </p>
    <p>
        Use "r" to reset the "View as" filter, and any nonexistent level ID (for example, "n" will never be a level ID) to emulate having no membership level.
    </p>
<?php
}

/*
 * Display Settings Fields
 */

//gateway debugging
function pmprodev_settings_ipn_debug() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[ipn_debug]" value="<?php echo $pmprodev_options['ipn_debug']; ?>">
    <?php
}
function pmprodev_settings_authnet_silent_post_debug() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[authnet_silent_post_debug]" value="<?php echo $pmprodev_options['authnet_silent_post_debug']; ?>">
    <?php
}
function pmprodev_settings_stripe_webhook_debug() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[stripe_webhook_debug]" value="<?php echo $pmprodev_options['stripe_webhook_debug']; ?>">
    <?php
}
function pmprodev_settings_ins_debug() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[ins_debug]" value="<?php echo $pmprodev_options['ins_debug']; ?>">
    <?php
}
function pmprodev_settings_checkout_debug_email() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[checkout_debug_email]" value="<?php echo $pmprodev_options['checkout_debug_email']; ?>">
    <p class="description">Send an email every time the Checkout page is hit.<br>This email will contain data about the request, user, membership level, order, and other information.</p>
<?php
}

//redirect emails
function pmprodev_settings_redirect_email() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[redirect_email]" value="<?php echo $pmprodev_options['redirect_email']; ?>">
    <p class="description">Redirect all Paid Memberships Pro emails to a specific address.</p>
<?php
}
function pmprodev_settings_view_as_enabled() {
    global $pmprodev_options;
    ?>
    <input id="view_as_enabled" type="checkbox"  name="pmprodev_options[view_as_enabled]" value="1"
        <?php if(!empty($pmprodev_options['view_as_enabled'])) echo 'checked="true"'; ?>>
    <?php
}

/*
 * Display Page
 */
?>

<div class="wrap">
    <h2>PMPro Toolkit Options</h2>
    <form action="options.php" method="POST">
        <?php
        settings_fields('pmprodev_options');
        do_settings_sections('pmprodev');
        submit_button();
        ?>
    </form>
</div>