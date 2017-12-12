<div class="wrap">
<h2>PMPro Toolkit Database Scripts</h2>
<?php 	
    global $wpdb, $pmprodev_member_tables, $pmprodev_other_tables;
	
	$pmprodev_member_tables = array(
		$wpdb->pmpro_memberships_users,
		$wpdb->pmpro_membership_orders,
		$wpdb->pmpro_discount_codes_uses		
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

	if(!empty($_POST['give_level']))
		$give_level = true;
	else
		$give_level = false;
		
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

		?><hr /><p><strong>
		<?php
		echo __( 'Member tables have been truncated.', 'pmpro-toolkit' );
		?>
		</strong></p>
		<?php
	}

	// clean level and discount code tables
	if ( $clean_level_data ) {
		foreach ( $pmprodev_other_tables as $table ) {
			$wpdb->query( "TRUNCATE $table" );
		}
		?>
		<hr /><p><strong>
		<?php
		echo __( 'Level and discount code tables have been truncated.', 'pmpro-toolkit' );
		?>
		</strong></p>
		<?php
	}

	// scrub member data
	if ( $scrub_member_data ) {
		$user_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->users" );

		?>
		<hr /><p><strong>
		<?php
		echo __( 'Scrubbing user data...', 'pmpro-toolkit' );
		?>
		</strong></p>
		<?php
	
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
		?>
		<hr /><p><strong>
		<?php
		echo __( 'Deleting non-admins...', 'pmpro-toolkit' );
		?>
		</strong></p>
		<?php
	
		foreach($user_ids as $user_id)
		{		
			if(!user_can($user_id, "manage_options"))
			{
				//emails				
				$wpdb->query("DELETE FROM $wpdb->users WHERE ID = " . $user_id . " LIMIT 1");
				$wpdb->query("DELETE FROM $wpdb->usermeta WHERE user_id = " . $user_id);
			}
			
			echo ". ";
		}
		
		echo "</p>";
	}
	
	//delete options
	if($clean_pmpro_options)
	{
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'pmpro_%' AND option_name <> 'pmpro_db_version' AND option_name NOT LIKE 'pmpro_%page_id'");
		?>
		<hr /><p><strong>
		<?php
		echo __( 'Options deleted.', 'pmpro-toolkit' );
		?>
		</strong></p>
		<?php
	}

	//moving level
	if($move_level)
	{
		$from_level_id = intval($_REQUEST['move_level_a']);
		$to_level_id = intval($_REQUEST['move_level_b']);

		//make sure both levels are > 0
		if($from_level_id < 1 || $to_level_id < 1)
		?>
		<hr /><p><strong>
		<?php
		echo __( 'Please enter a level ID > 1 for each options.', 'pmpro-toolkit' );
		?>
		</strong></p>
		<?php
		} else {
			//get user ids to run hook later
			$user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $from_level_id . "' AND status = 'active' ");

			if(empty($user_ids))
			{
		?>
		<hr /><p><strong>
		<?php
		echo sprintf( __( 'Couldn\'t find users with level ID ', 'pmpro-toolkit' ), $from_level_id );
		?>
		</strong></p>
		<?php
			}
			else
			{
				//update users in DB
				$wpdb->query("UPDATE $wpdb->pmpro_memberships_users SET membership_id = " . $to_level_id . " WHERE membership_id = " . $from_level_id . " AND STATUS =  'active';");

			?>
			<hr /><p><strong>
			<?php
				echo __( 'Users updated. Running pmpro_after_change_membership_level filter for all users...', 'pmpro-toolkit' );
			?>
			</strong></p>
			<?php

				foreach($user_ids as $user_id)
				{
					do_action('pmpro_after_change_membership_level', $to_level_id, $user_id);
					echo ". ";
				}

				echo "</p>";
			}
		}
	}

	if($give_level) {
		$give_level_id = intval($_REQUEST['give_level_id']);
		$give_level_startdate = preg_replace('/^0-9\-/', '', $_REQUEST['give_level_startdate']);
		$give_level_enddate = preg_replace('/^0-9\-/', '', $_REQUEST['give_level_enddate']);
				
		if(empty($give_level_id) || empty($give_level_startdate) || empty($give_level_enddate)) {
		?>
		<hr /><p><strong>
		<?php
		echo __( 'Please enter a level ID > 1 for each options.', 'pmpro-toolkit' );
		?>
		</strong></p>
		<?php
		} else {
			$sqlQuery = "INSERT INTO {$wpdb->pmpro_memberships_users} (user_id, membership_id, status, startdate, enddate) 
						SELECT 
							u.ID,  			#ID from wp_users table
							" . $give_level_id . ", 			#id of the level to give users
							'active', 		#status to give users
							'" . $give_level_startdate . "', 		#start date in YYYY-MM-DD format
							'" . $give_level_enddate . "' 		#end date in YYYY-MM-DD format, use '' for auto-recurring/no end date
						FROM {$wpdb->users} u 
							LEFT JOIN {$wpdb->pmpro_memberships_users} mu
								ON u.ID = mu.user_id 
								AND status = 'active' 
						WHERE mu.id IS NULL
			";
			$wpdb->query($sqlQuery);
	
			//assume it worked
			echo '<hr><p><strong>';
			echo sprintf( __( '%s users were give level %s ', 'pmpro-toolkit' ), $wpdb->rows_affected, $give_level_id );
			echo '.</strong></p>';
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
		?>
		<hr /><p><strong>
		<?php
		echo esc_html_e( "Couldn't find users with level ID $cancel_level_id.", 'pmpro-toolkit' );
		?>
		</strong>
		<?php
		} else {
		?>
		<hr /><p><strong>
		<?php
		echo esc_html_e( 'Cancelling users...', 'pmpro-toolkit' );
		?>
		</strong>
		<?php
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
	
		<p><?php echo esc_html_e( 'This feature allows you to either clear data from PMPro-related database tables and options or to scrub member email and transaction id data to prevent real members from receiving updates or having their subscriptions changed.', 'pmpro-toolkit' ); ?></p>

		<p><?php echo esc_html_e( 'Check the options that you would like to apply. The cleanup scripts will be run upon saving these settings.', 'pmpro-toolkit' ); ?></p>
		
		<div class="error">
			<p><?php echo sprintf( __( '%s Checking these options WILL delete data from your database. Please backup first and make sure that you intend to delete this data.', 'pmpro-toolkit' ), '<strong>IMPORTANT NOTE:</strong>' ); ?></p>
		</div>
		
		<hr />
		<p>
			<input type="checkbox" id="clean_member_tables" name="clean_member_tables" value="1" /> 
			<label for="clean_member_tables"><?php echo esc_html_e( 'Delete all member data.', 'pmpro-toolkit' ); ?> (<?php echo implode( ', ', $pmprodev_member_tables ); ?>)</label>
		</p>
		
		<hr />
		<p>
			<input type="checkbox" id="clean_level_data" name="clean_level_data" value="1" /> 
			<label for="clean_level_data"><?php echo esc_html_e( 'Delete all level and discount code data.', 'pmpro-toolkit' ); ?> (<?php echo implode( ', ', $pmprodev_other_tables ); ?>)</label>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="scrub_member_data" name="scrub_member_data" value="1" /> 
			<label for="scrub_member_data"><?php echo sprintf( __( 'Scrub member emails and transaction ids. Updates non-admins in %s and %s tables.', 'pmpro-toolkit' ), $wpdb->users, $wpdb->pmpro_membership_orders ); ?></label>
			<br/ ><small><?php echo esc_html_e( 'This may time out on slow servers or sites with large numbers of users.', 'pmpro-toolkit' ); ?></small>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="delete_users" name="delete_users" value="1" /> 
			<label for="delete_users"><?php echo sprintf( __( "Delete non-admin users. (Deletes from %s and %s tables directly.)", 'pmpro-toolkit' ), $wpdb->users, $wpdb->usermeta ); ?></label>
			<br/ ><small><?php echo esc_html_e( 'This may time out on slow servers or sites with large numbers of users.', 'pmpro-toolkit' ); ?></small>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="clean_pmpro_options" name="clean_pmpro_options" value="1" /> 
			<label for="clean_pmpro_options"><?php echo esc_html_e( 'Delete all PMPro options. (Any option prefixed with pmpro_ but not the DB version or PMPro page_id options.)', 'pmpro-toolkit' ); ?></label>
		</p>

		<hr />		
		<p>
			<input type="checkbox" id="move_level" name="move_level" value="1" /> 
			<label for="move_level">
			<?php
			echo esc_html_e( 'Change all members with level ID', 'pmpro-toolkit' );
?>
 <input type="text" name="move_level_a" value="" size="4" /> to level ID <input type="text" name="move_level_b" value="" size="4" />. <?php echo esc_html_e( 'Will NOT cancel any recurring subscriptions.', 'pmpro-toolkit' ); ?>
			</label>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="give_level" name="give_level" value="1" /> 
			<label for="give_level">
				<?php echo esc_html_e( 'Give all non-members level ID ', 'pmpro-toolkit' ); ?><input type="text" name="give_level_id" value="" size="4" />. <?php echo esc_html_e( 'Set the start date to <input type="text" name="give_level_startdate" value="" size="10" /> (YYYY-MM-DD) and set the end date to ', 'pmpro-toolkit' ); ?><input type="text" name="give_level_enddate" value="" size="10" /> (optional, YYYY-MM-DD). 
			</label>
			<br/ ><small><?php echo esc_html_e( 'This only gives users\' the level via the database and does NOT fire any pmpro_change_membership_level hooks.', 'pmpro-toolkit' ); ?></small>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="cancel_level" name="cancel_level" value="1" /> 
			<label for="cancel_level">
			<?php
			echo esc_html_e( 'Cancel all members with level ID', 'pmpro-toolkit' );
?>
 <input type="text" name="move_levels_a" value="" size="4" />. <?php echo esc_html_e( 'WILL also cancel any recurring subscriptions.', 'pmpro-toolkit' ); ?>
			</label>
		</p>

		<hr />
		<p>
			<input type="checkbox" id="copy_memberships_pages" name="copy_memberships_pages" value="1" /> 
			<label for="copy_memberships_pages">
				<?php echo esc_html_e( 'Make all pages that require level ID', 'pmpro-toolkit' ); ?> <input type="text" name="copy_memberships_pages_a" value="" size="4" /> <?php echo esc_html_e( 'also require level ID', 'pmpro-toolkit' ); ?> <input type="text" name="copy_memberships_pages_b" value="" size="4" />.
			</label>
		</p>

		<hr />
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_html_e( 'Save Changes', 'pmpro-toolkit' ); ?>"></p>
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
