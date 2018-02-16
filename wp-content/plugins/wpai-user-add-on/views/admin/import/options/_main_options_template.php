<div class="wpallimport-collapsed">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">			
			<h3><?php _e('User\'s Data','wp_all_import_user_add_on');?></h3>	
		</div>		
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner wpallimport-user-data">
				<table style="width:100%;">
					<tr>
						<td colspan="3">
							<input type="hidden" name="pmui[import_users]" value="1"/>				
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>First Name</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('The user\'s first name. ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[first_name]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['first_name']) ?>"/>				
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Last Name</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('The user\'s last name.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[last_name]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['last_name']) ?>"/>				
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Role</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string with role slug used to set the user\'s role. Default role is subscriber.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[role]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['role']) ?>"/>				
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Nickname</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('The user\'s nickname, defaults to the user\'s username. ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[nickname]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['nickname']) ?>"/>				
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Description</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string containing content about the user.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<textarea name="pmui[description]" class="widefat" style="width:100%;margin-bottom:5px;"><?php if (!empty($post['pmui']['description'])) echo esc_html($post['pmui']['description']); ?></textarea>
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>* Login</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string that contains the user\'s username for logging in.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[login]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['login']) ?>"/>				
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Password</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string that contains the plain text password for the user.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[pass]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['pass']); ?>"/>				
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Nicename</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string that contains a URL-friendly name for the user. The default is the user\'s username.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[nicename]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['nicename']); ?>"/>
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>* Email</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string containing the user\'s email address.', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[email]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['email']); ?>"/>
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Registered</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('The date the user registered. Format is Y-m-d H:i:s', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[registered]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['registered']); ?>"/>
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>Display Name</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string that will be shown on the site. Defaults to user\'s username. It is likely that you will want to change this, for both appearance and security through obscurity (that is if you dont use and delete the default admin user). ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[display_name]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['display_name']); ?>"/>
							</div>
							<div class="input">
								<p style="margin-bottom:5px;"><?php _e('<b>URL</b>', 'wp_all_import_user_add_on');?><a class="wpallimport-help" href="#help" original-title="<?php _e('A string containing the user\'s URL for the user\'s web site. ', 'wp_all_import_user_add_on'); ?>">?</a></p>
								<input name="pmui[url]" type="text" class="widefat" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['url']); ?>"/>
							</div>					
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>