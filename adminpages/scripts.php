<div class="wrap">
<h2>PMPro Toolkit Database Scripts</h2>
<?php 	
    global $wpdb, $pmprodev_member_tables, $pmprodev_other_tables;
	
	$pmprodev_member_tables = array(
		$wpdb->pmpro_memberships_users,
		$wpdb->pmpro_membership_orders,
		$wpdb->pmpro_discount_codes_uses,
		$wpdb->pmpro_memberships_users
	);

	$pmprodev_other_tables = array(
		$wpdb->pmpro_discount_codes,
		$wpdb->pmpro_discount_codes_levels,
		$wpdb->pmpro_membership_levels,
		$wpdb->pmpro_memberships_categories,
		$wpdb->pmpro_memberships_pages
	);
		
	//check if we should run scripts
	if(!empty($_POST['clean_member_tables']))
		$clean_member_tables = true;
	else
		$clean_member_tables = false;
		
	if(!empty($_POST['clean_level_data']))
		$clean_level_data = true;
	else
		$clean_level_data = false;
		
	if(!empty($_POST['scrub_member_data']))
		$scrub_member_data = true;
	else
		$scrub_member_data = false;
		
	if(!empty($_POST['delete_users']))
		$delete_users = true;
	else
		$delete_users = false;
		
	if(!empty($_POST['clean_pmpro_options']))
		$clean_pmpro_options = true;
	else
		$clean_pmpro_options = false;
	
	if(!empty($_POST['move_level']))
		$move_level = true;
	else
		$move_level = false;

	if(!empty($_POST['cancel_level']))
		$cancel_level = true;
	else
		$cancel_level = false;

	if(!empty($_POST['copy_memberships_pages']))
		$copy_memberships_pages = true;
	else
		$copy_memberships_pages = false;

	//clean member tables
	if($clean_member_tables)
	{
		foreach($pmprodev_member_tables as $table)
			$wpdb->query("TRUNCATE $table");
			
		echo "<hr /><p><strong>Member tables have been truncated.</strong></p>";
	}
	
	//clean level and discount code tables
	if($clean_level_data)
	{
		foreach($pmprodev_other_tables as $table)
			$wpdb->query("TRUNCATE $table");
			
			
		echo "<hr /><p><strong>Level and discount code tables have been truncated.</strong></p>";
	}
	
	//scrub member data
	if($scrub_member_data)
	{
		$user_ids = $wpdb->get_col("SELECT ID FROM $wpdb->users");
		
		echo "<hr /><p><strong>Scrubbing user data...</strong> ";
		
		$count = 0;
		$admin_email = get_option("admin_email");
		
		foreach($user_ids as $user_id)
		{
			$count++;
			
			if(!user_can($user_id, "manage_options"))
			{
				//emails
				$new_email = str_replace("@", "+scrub" . $count . "@", $admin_email);
				$wpdb->query("UPDATE $wpdb->users SET user_email = '" . $new_email . "' WHERE ID = " . $user_id . " LIMIT 1");
			}

			//stil update transaction ids/etc for admin users
			
			//orders
			$new_transaction_id = "SCRUBBED-" . $count;
			$wpdb->query("UPDATE $wpdb->pmpro_membership_orders SET payment_transaction_id = '" . $new_transaction_id . "' WHERE user_id = '" . $user_id . "' AND payment_transaction_id <> '' ");
			$wpdb->query("UPDATE $wpdb->pmpro_membership_orders SET subscription_transaction_id = '" . $new_transaction_id . "' WHERE user_id = '" . $user_id . "' AND subscription_transaction_id <> '' ");
			
			//braintree customer ids
			update_user_meta($user_id, "pmpro_braintree_customerid", $new_transaction_id);
			update_user_meta($user_id, "pmpro_stripe_customerid", $new_transaction_id);
			
			echo ". ";
		}
		
		echo "</p>";
	}
	
	//scrub non admins
	if($delete_users)
	{
		$user_ids = $wpdb->get_col("SELECT ID FROM $wpdb->users");
		
		echo "<hr /><p><strong>Deleting non-admins...</strong> ";
		
		foreach($user_ids as $user_id)
		{		
			if(!user_can($user_id, "manage_options"))
			{
				//emails				
				$wpdb->query("DELETE FROM $wpdb->users WHERE ID = " . $user_id . " LIMIT 1");
				$wpdb->query("DELETE FROM $wpdb->usermeta WHERE user_id = " . $user_id . " LIMIT 1");
			}			
			
			echo ". ";
		}
		
		echo "</p>";
	}
	
	//delete options
	if($clean_pmpro_options)
	{
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'pmpro_%' AND option_name <> 'pmpro_db_version' AND option_name NOT LIKE 'pmpro_%page_id'");
		
		echo "<hr /><p><strong>Options deleted.</strong></p>";
	}
	
	//moving level
	if($move_level)
	{
		$from_level_id = intval($_REQUEST['move_level_a']);
		$to_level_id = intval($_REQUEST['move_level_b']);

		//make sure both levels are > 0
		if($from_level_id < 1 || $to_level_id < 1)
			echo "<hr /><p><strong>Please enter a level ID > 1 for each options.</strong></p>";
		else
		{
			//get user ids to run hook later
			$user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $from_level_id . "' AND status = 'active' ");

			if(empty($user_ids))
			{
				echo "<hr /><p><strong>Couldn't find users with level ID " . $from_level_id . ".</strong></p>";
			}
			else
			{
				//update users in DB
				$wpdb->query("UPDATE $wpdb->pmpro_memberships_users SET membership_id = " . $to_level_id . " WHERE membership_id = " . $from_level_id . " AND STATUS =  'active';");

				echo "<hr /><p><strong>Users updated. Running pmpro_after_change_membership_level filter for all users...</strong> ";

				foreach($user_ids as $user_id)
				{
					do_action('pmpro_after_change_membership_level', $to_level_id, $user_id);
					echo ". ";
				}

				echo "</p>";
			}
		}
	}

	//cancelling a lvel
	if($cancel_level)
	{
		$cancel_level_id = intval($_POST['cancel_level']);

		//get user ids to cancel
		$user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $cancel_level_id . "' AND status = 'active' ");

		if(empty($user_ids))
		{
			echo "<hr /><p><strong>Couldn't find users with level ID " . $cancel_level_id . ".</strong></p>";
		}
		else
		{
			echo "<hr /><p><strong>Cancelling users...</strong> ";

			foreach($user_ids as $user_id)
			{
				pmpro_changeMembershipLevel(0, $user_id);
				echo ". ";
			}

			echo "</p>";
		}
	}

	//moving level
	if($copy_memberships_pages)
	{
		$from_level_id = intval($_REQUEST['copy_memberships_pages_a']);
		$to_level_id = intval($_REQUEST['copy_memberships_pages_b']);

		$wpdb->query("INSERT IGNORE INTO $wpdb->pmpro_memberships_pages (membership_id, page_id) SELECT $to_level_id, page_id FROM $wpdb->pmpro_memberships_pages WHERE membership_id = $from_level_id");
		
		echo "<hr /><p><strong>Require Membership options copied.</strong></p>";
	}

	?>	
    <hr />

	<form id="form-scripts" action="" method="post">
		<input type="hidden" name="page" value="pmprodev-database-scripts" />	
	
		<p>This feature allows you to either clear data from PMPro-related database tables and options or to scrub member email and transaction id data to prevent real members from receiving updates or having their subscriptions changed.</p>
		
		<p>Check the options that you would like to apply. The cleanup scripts will be run upon saving these settings.</p>
		
		<div class="error">
			<p><strong>IMPORTANT NOTE:</strong> Checking these options WILL delete data from your database. Please backup first and make sure that you intend to delete this data.</p>
		</div>
		
		<hr />
		<p>
			<input type="checkbox" id="clean_member_tables" name="clean_member_tables" value="1" /> 
			<label for="clean_member_tables">Delete all member data. (<?php echo implode(", ", $pmprodev_member_tables);?>)</label>
		</p>
		
		<hr />
		<p>
			<input type="checkbox" id="clean_level_data" name="clean_level_data" value="1" /> 
			<label for="clean_level_data">Delete all level and discount code data. (<?php echo implode(", ", $pmprodev_other_tables);?>)</label>
		</p>

		<hr />		
		<p>
			<input type="checkbox" id="scrub_member_data" name="scrub_member_data" value="1" /> 
			<label for="scrub_member_data">Scrub member emails and transaction ids. (Updates non-admins in <?php echo $wpdb->users;?> and <?php echo $wpdb->pmpro_membership_orders;?> tables.)</label>
			<br/ ><small>This may time out on slow servers or sites with large numbers of users.</small>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="delete_users" name="delete_users" value="1" /> 
			<label for="delete_users">Delete non-admin users. (Deletes from <?php echo $wpdb->users;?> and <?php echo $wpdb->usermeta;?> tables directly.)</label>
			<br/ ><small>This may time out on slow servers or sites with large numbers of users.</small>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="clean_pmpro_options" name="clean_pmpro_options" value="1" /> 
			<label for="clean_pmpro_options">Delete all PMPro options. (Any option prefixed with pmpro_ but not the DB version or PMPro page_id options.)</label>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="move_level" name="move_level" value="1" /> 
			<label for="move_level">
				Change all members with level ID <input type="text" name="move_level_a" value="" size="4" /> to level ID <input type="text" name="move_level_b" value="" size="4" />. Will NOT cancel any recurring subscriptions.
			</label>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="cancel_level" name="cancel_level" value="1" /> 
			<label for="cancel_level">
				Cancel all members with level ID <input type="text" name="move_levels_a" value="" size="4" />. WILL also cancel any recurring subscriptions.
			</label>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="copy_memberships_pages" name="copy_memberships_pages" value="1" /> 
			<label for="copy_memberships_pages">
				Make all pages that require level ID <input type="text" name="copy_memberships_pages_a" value="" size="4" /> also require level ID <input type="text" name="copy_memberships_pages_b" value="" size="4" />.
			</label>
		</p>

		<hr />
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
	</form>
	
	<script>
		jQuery(document).ready(function() {
			jQuery('div.wrap form').submit(function() {
				//check if any of the clean options are set
				var o1 = jQuery('#clean_member_tables').is(":checked");
				var o2 = jQuery('#clean_level_data').is(":checked");
				var o3 = jQuery('#scrub_member_data').is(":checked");
				var o4 = jQuery('#clean_pmpro_options').is(":checked");
				var o5 = jQuery('#move_level').is(":checked");
				var o6 = jQuery('#cancel_level').is(":checked");

				if(o1 || o2 || o3 || o4 ||  o5 || o6)
				{
					return confirm ('You have checked one of the database script options. Saving these settings WILL DELETE DATA FROM YOUR DATABASE. Are you sure you want to continue?');
				}
				
				return true;
			});
		});
	</script>
</div>