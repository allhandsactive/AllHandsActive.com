<table class="form-table">
    <tr>
		<td>
			<p class="description">
				<?php _e('This e-mail will be sent to a new user upon registration.', 'theme-my-login'); ?>
				<?php _e('Please be sure to include the variable %user_pass% if using default passwords or else the user will not know their password!', 'theme-my-login'); ?>
				<?php _e('If either field is left empty, the default will be used instead.', 'theme-my-login'); ?>
			</p>
			<p class="description"><?php _e('Available Variables', 'theme-my-login'); ?>: %blogname%, %siteurl%, %user_login%, %user_email%, %user_pass%, %user_ip%</p>
			<label for="theme_my_login_new_user_title"><?php _e('Subject', 'theme-my-login'); ?></label><br />
			<input name="theme_my_login[email][new_user][title]" type="text" id="theme_my_login_new_user_title" value="<?php echo $theme_my_login->options['email']['new_user']['title']; ?>" class="full-text" /><br />
			<label for="theme_my_login_new_user_message"><?php _e('Message', 'theme-my-login'); ?></label><br />
			<textarea name="theme_my_login[email][new_user][message]" id="theme_my_login_new_user_message" class="large-text" rows="10"><?php echo $theme_my_login->options['email']['new_user']['message']; ?></textarea><br />
			<label for="theme_my_login_new_user_admin_disable"><input name="theme_my_login[email][new_user][admin_disable]" type="checkbox" id="theme_my_login_new_user_admin_disable" value="1"<?php checked(1, $theme_my_login->options['email']['new_user']['admin_disable']); ?> /> Disable Admin Notification</label>
			<p class="description"><?php _e('Check this option if you do not wish to receive notification everytime someone registers for your blog.', 'theme-my-login'); ?></p>
		</td>
	</tr>
</table>