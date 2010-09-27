<table class="form-table">
    <tr>
		<td>
			<p class="description">
				<?php _e('This e-mail will be sent to a user who is deleted/denied when "Admin Approval" is checked for "User Moderation" and the user\'s role is "Pending".', 'theme-my-login'); ?>
				<?php _e('If either field is left empty, the default will be used instead.', 'theme-my-login'); ?>
			</p>
			<p class="description"><?php _e('Available Variables', 'theme-my-login'); ?>: %blogname%, %siteurl%, %user_login%, %user_email%</p>
			<label for="theme_my_login_user_denial_title"><?php _e('Subject', 'theme-my-login'); ?></label><br />
			<input name="theme_my_login[email][user_denial][title]" type="text" id="theme_my_login_user_denial_title" value="<?php if ( isset($theme_my_login->options['email']['user_denial']['title']) ) echo $theme_my_login->options['email']['user_denial']['title']; ?>" class="full-text" /><br />
			<label for="theme_my_login_user_denial_message"><?php _e('Message', 'theme-my-login'); ?></label><br />
			<textarea name="theme_my_login[email][user_denial][message]" id="theme_my_login_user_denial_message" class="large-text" rows="10"><?php if ( isset($theme_my_login->options['email']['user_denial']['message']) ) echo $theme_my_login->options['email']['user_denial']['message']; ?></textarea><br />
		</td>
	</tr>
</table>