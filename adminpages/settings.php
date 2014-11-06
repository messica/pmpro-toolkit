<?php

/*
 * Display Settings Sections
 */
function pmprodev_email_settings() {
?>
<style>
	h2, table.form-table {border-bottom: 1px solid #ddd; margin-bottom: 2em;}
</style>
<?php
}

function pmprodev_gateway_settings() {
    ?>
    <p>Enable debugging for PayPal IPNs, Authorize.net Silent Posts, Stripe Webhook, etc.<br>Enter the email address you would like the logs to be emailed to, or leave blank to disable.</p>
    <?php
}

function pmprodev_cron_settings() {
    ?>
    <p>Disable scheduled scripts that run daily via WP CRON.</p>
    <?php
}

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

//redirect emails
function pmprodev_settings_redirect_email() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[redirect_email]" value="<?php echo $pmprodev_options['redirect_email']; ?>">
    <p class="description">Redirect all Paid Memberships Pro emails to a specific address.</p>
<?php
}

//cron debugging
function pmprodev_settings_cron_expire_memberships() {
    global $pmprodev_options;
    ?>
    <input id="expire_memberships" type="checkbox"  name="pmprodev_options[expire_memberships]" value="1" <?php if(!empty($pmprodev_options['expire_memberships'])) echo 'checked="true"'; ?>>
	<p class="description">Check to disable.</p>
    <?php
}
function pmprodev_settings_cron_expiration_warnings() {
    global $pmprodev_options;
    ?>
    <input id="expiration_warnings" type="checkbox"  name="pmprodev_options[expiration_warnings]" value="1" <?php if(!empty($pmprodev_options['expiration_warnings'])) echo 'checked="true"'; ?>>
	<p class="description">Check to disable.</p>
    <?php
}
function pmprodev_settings_cron_credit_card_expiring() {
    global $pmprodev_options;
    ?>
    <input id="credit_card_expiring" type="checkbox"  name="pmprodev_options[credit_card_expiring]" value="1" <?php if(!empty($pmprodev_options['credit_card_expiring'])) echo 'checked="true"'; ?>>
	<p class="description">Check to disable.</p>
    <?php
}

//gateway debugging
function pmprodev_settings_ipn_debug() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[ipn_debug]" value="<?php echo $pmprodev_options['ipn_debug']; ?>">
    <?php
}

function pmprodev_settings_checkout_debug_email() {
    global $pmprodev_options;
    ?>
    <input type="text"  name="pmprodev_options[checkout_debug_email]" value="<?php echo $pmprodev_options['checkout_debug_email']; ?>">
    <p class="description">Send an email every time the Checkout page is hit.<br>This email will contain data about the request, user, membership level, order, and other information.</p>
<?php
}

function pmprodev_settings_view_as_enabled() {
    global $pmprodev_options;
    ?>
    <input id="view_as_enabled" type="checkbox"  name="pmprodev_options[view_as_enabled]" value="1" <?php if(!empty($pmprodev_options['view_as_enabled'])) echo 'checked="true"'; ?>>
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