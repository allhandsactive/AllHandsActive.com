<?php

function wdbj_tml_themed_profiles_display() {
	global $current_user, $action, $redirect, $profile, $user_id, $wp_http_referer;
		
	$profileuser = get_user_to_edit($current_user->ID);
	
	$wp_error = wdbj_tml_get_var('errors');
	if ( empty($wp_error) )
		$wp_error = new WP_Error();
		
	if ( isset($_GET['updated']) && $_GET['updated'] )
		$wp_error->add('profile_updated', __('Profile updated.', 'theme-my-login'), 'message');
		
	ob_start();
	?>
<div class="login" id="profile">
<?php
	if ( is_wp_error($wp_error) && $wp_error->get_error_code() ) {
		$errors = '';
		$messages = '';
		foreach ( $wp_error->get_error_codes() as $code ) {
			$severity = $wp_error->get_error_data($code);
			foreach ( $wp_error->get_error_messages($code) as $error ) {
				if ( 'message' == $severity )
					$messages .= '    ' . $error . "<br />\n";
				else
					$errors .= '    ' . $error . "<br />\n";
			}
		}
		if ( !empty($errors) )
			echo '<p class="error">' . apply_filters('login_errors', $errors) . "</p>\n";
		if ( !empty($messages) )
			echo '<p class="message">' . apply_filters('login_messages', $messages) . "</p>\n";
	}
	?>
<form id="your-profile" action="" method="post">
<?php wp_nonce_field('update-user_' . $current_user->ID) ?>
<?php if ( $wp_http_referer ) : ?>
	<input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>" />
<?php endif; ?>
<p>
<input type="hidden" name="from" value="profile" />
<input type="hidden" name="checkuser_id" value="<?php echo $current_user->ID; ?>" />
</p>

<?php if ( has_filter('personal_options') || has_filter('profile_personal_options') ) : ?>
<h3><?php _e('Personal Options', 'theme-my-login'); ?></h3>

<table class="form-table">
<?php do_action('personal_options', $profileuser); ?>
</table>
<?php do_action('profile_personal_options', $profileuser); ?>
<?php endif; ?>

<h3><?php _e('Name', 'theme-my-login') ?></h3>

<table class="form-table">
<tr>
	<th><label for="user_login"><?php _e('Username', 'theme-my-login'); ?></label></th>
	<td><input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($profileuser->user_login); ?>" disabled="disabled" class="regular-text" /> <span class="description"><?php _e('Your username cannot be changed.', 'theme-my-login'); ?></span></td>
</tr>

<tr>
	<th><label for="first_name"><?php _e('First name', 'theme-my-login') ?></label></th>
	<td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($profileuser->first_name) ?>" class="regular-text" /></td>
</tr>

<tr>
	<th><label for="last_name"><?php _e('Last name', 'theme-my-login') ?></label></th>
	<td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($profileuser->last_name) ?>" class="regular-text" /></td>
</tr>

<tr>
	<th><label for="nickname"><?php _e('Nickname', 'theme-my-login'); ?> <span class="description"><?php _e('(required)', 'theme-my-login'); ?></span></label></th>
	<td><input type="text" name="nickname" id="nickname" value="<?php echo esc_attr($profileuser->nickname) ?>" class="regular-text" /></td>
</tr>

<tr>
	<th><label for="display_name"><?php _e('Display name publicly as', 'theme-my-login') ?></label></th>
	<td>
		<select name="display_name" id="display_name">
		<?php
			$public_display = array();
			$public_display['display_nickname']  = $profileuser->nickname;
			$public_display['display_username']  = $profileuser->user_login;
			if ( !empty($profileuser->first_name) )
				$public_display['display_firstname'] = $profileuser->first_name;
			if ( !empty($profileuser->last_name) )
				$public_display['display_lastname'] = $profileuser->last_name;
			if ( !empty($profileuser->first_name) && !empty($profileuser->last_name) ) {
				$public_display['display_firstlast'] = $profileuser->first_name . ' ' . $profileuser->last_name;
				$public_display['display_lastfirst'] = $profileuser->last_name . ' ' . $profileuser->first_name;
			}
			if ( !in_array( $profileuser->display_name, $public_display ) )// Only add this if it isn't duplicated elsewhere
				$public_display = array( 'display_displayname' => $profileuser->display_name ) + $public_display;
			$public_display = array_map( 'trim', $public_display );
			foreach ( $public_display as $id => $item ) {
		?>
				<option id="<?php echo $id; ?>" value="<?php echo esc_attr($item); ?>"<?php selected( $profileuser->display_name, $item ); ?>><?php echo $item; ?></option>
		<?php
			}
		?>
		</select>
	</td>
</tr>
</table>

<h3><?php _e('Contact Info', 'theme-my-login') ?></h3>

<table class="form-table">
<tr>
	<th><label for="email"><?php _e('E-mail', 'theme-my-login'); ?> <span class="description"><?php _e('(required)', 'theme-my-login'); ?></span></label></th>
	<td><input type="text" name="email" id="email" value="<?php echo esc_attr($profileuser->user_email) ?>" class="regular-text" /></td>
</tr>

<tr>
	<th><label for="url"><?php _e('Website', 'theme-my-login') ?></label></th>
	<td><input type="text" name="url" id="url" value="<?php echo esc_attr($profileuser->user_url) ?>" class="regular-text code" /></td>
</tr>

<?php if ( function_exists('_wp_get_user_contactmethods') ) :
	foreach (_wp_get_user_contactmethods() as $name => $desc) {
?>
<tr>
	<th><label for="<?php echo $name; ?>"><?php echo apply_filters('user_'.$name.'_label', $desc); ?></label></th>
	<td><input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr($profileuser->$name) ?>" class="regular-text" /></td>
</tr>
<?php
	}
	endif;
?>
</table>

<h3><?php _e('About Yourself', 'theme-my-login'); ?></h3>

<table class="form-table">
<tr>
	<th><label for="description"><?php _e('Biographical Info', 'theme-my-login'); ?></label></th>
	<td><textarea name="description" id="description" rows="5" cols="30"><?php echo esc_html($profileuser->description); ?></textarea><br />
	<span class="description"><?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.', 'theme-my-login'); ?></span></td>
</tr>

<?php
$show_password_fields = apply_filters('show_password_fields', true, $profileuser);
if ( $show_password_fields ) :
?>
<tr id="password">
	<th><label for="pass1"><?php _e('New Password', 'theme-my-login'); ?></label></th>
	<td><input type="password" name="pass1" id="pass1" size="16" value="" autocomplete="off" /> <span class="description"><?php _e("If you would like to change the password type a new one. Otherwise leave this blank.", 'theme-my-login'); ?></span><br />
		<input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" /> <span class="description"><?php _e("Type your new password again.", 'theme-my-login'); ?></span><br />
		<div id="pass-strength-result"><?php _e('Strength indicator', 'theme-my-login'); ?></div>
		<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'theme-my-login'); ?></p>
	</td>
</tr>
<?php endif; ?>
</table>

<?php
	do_action('show_user_profile', $profileuser);
?>

<?php if ( count($profileuser->caps) > count($profileuser->roles) && apply_filters('additional_capabilities_display', true, $profileuser) ) { ?>
<br class="clear" />
	<table width="99%" style="border: none;" cellspacing="2" cellpadding="3" class="editform">
		<tr>
			<th scope="row"><?php _e('Additional Capabilities', 'theme-my-login') ?></th>
			<td><?php
			$output = '';
			global $wp_roles;
			foreach ( $profileuser->caps as $cap => $value ) {
				if ( !$wp_roles->is_role($cap) ) {
					if ( $output != '' )
						$output .= ', ';
					$output .= $value ? $cap : "Denied: {$cap}";
				}
			}
			echo $output;
			?></td>
		</tr>
	</table>
<?php } ?>

<p class="submit">
	<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($current_user->ID); ?>" />
	<input type="submit" class="button-primary" value="<?php esc_attr_e('Update Profile'); ?>" name="submit" />
</p>
</form>
</div>
<?php
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}

?>