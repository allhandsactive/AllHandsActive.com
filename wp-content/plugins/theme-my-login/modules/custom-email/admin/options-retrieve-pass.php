<table class="form-table">
	<tr>
		<td>
			<p class="description">
				<?php _e('This e-mail will be sent to a user when they attempt to recover their password.', 'theme-my-login'); ?>
				<?php _e('Please be sure to include the variable %reseturl% or else the user will not be able to recover their password!', 'theme-my-login'); ?>
				<?php _e('If either field is left empty, the default will be used instead.', 'theme-my-login'); ?>
			</p>
			<p><em><?php _e('Available Variables', 'theme-my-login'); ?>: %blogname%, %siteurl%, %reseturl%, %user_login%, %user_email%, %user_ip%</em></p>
			<label for="theme_my_login_reset_pass_title"><?php _e('Subject', 'theme-my-login'); ?></label><br />
			<input name="theme_my_login[email][retrieve_pass][title]" type="text" id="theme_my_login_retrieve_pass_title" value="<?php echo $theme_my_login->options['email']['retrieve_pass']['title']; ?>" class="full-text" /><br />
			<label for="theme_my_login_reset_pass_message"><?php _e('Message', 'theme-my-login'); ?></label><br />
			<textarea name="theme_my_login[email][retrieve_pass][message]" id="theme_my_login_retrieve_pass_message" class="large-text" rows="10"><?php echo $theme_my_login->options['email']['retrieve_pass']['message']; ?></textarea><br />
		</td>
	</tr>
</table>