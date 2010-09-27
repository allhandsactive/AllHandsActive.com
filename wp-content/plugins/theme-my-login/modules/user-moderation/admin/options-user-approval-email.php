<table class="form-table">
    <tr>
		<td>
			<p class="description">
				<?php _e('This e-mail will be sent to a new user upon admin approval when "Admin Approval" is checked for "User Moderation".', 'theme-my-login'); ?>
				<?php _e('Please be sure to include the variable %user_pass% if using default passwords or else the user will not know their password!', 'theme-my-login'); ?>
				<?php _e('If either field is left empty, the default will be used instead.', 'theme-my-login'); ?>
			</p>
			<p class="description"><?php _e('Available Variables', 'theme-my-login'); ?>: %blogname%, %siteurl%, %activateurl%, %user_login%, %user_email%, %user_pass%</p>
			<label for="theme_my_login_user_approval_title"><?php _e('Subject', 'theme-my-login'); ?></label><br />
			<input name="theme_my_login[email][user_approval][title]" type="text" id="theme_my_login_user_approval_title" value="<?php if ( isset($theme_my_login->options['email']['user_approval']['title']) ) echo $theme_my_login->options['email']['user_approval']['title']; ?>" class="full-text" /><br />
			<label for="theme_my_login_user_approval_message"><?php _e('Message', 'theme-my-login'); ?></label><br />
			<textarea name="theme_my_login[email][user_approval][message]" id="theme_my_login_user_approval_message" class="large-text" rows="10"><?php if ( isset($theme_my_login->options['email']['user_approval']['message']) ) echo $theme_my_login->options['email']['user_approval']['message']; ?></textarea><br />
		</td>
	</tr>
</table>