<?php

/**
 * Handles activating a new user by user email confirmation.
 *
 * @param string $key Hash to validate sending confirmation email
 * @param string $login User's username for logging in
 * @param bool $newpass Whether or not to assign a new password
 * @return bool|WP_Error
 */
function wdbj_tml_user_mod_activate_new_user($key, $login, $newpass = false) {
    global $wpdb;

    $key = preg_replace('/[^a-z0-9]/i', '', $key);

    if ( empty($key) || !is_string($key) )
        return new WP_Error('invalid_key', __('Invalid key', 'theme-my-login'));

    if ( empty($login) || !is_string($login) )
        return new WP_Error('invalid_key', __('Invalid key', 'theme-my-login'));

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));
    if ( empty( $user ) )
        return new WP_Error('invalid_key', __('Invalid key', 'theme-my-login'));
		
	do_action('user_activation_post', $user->user_login, $user->user_email);
	
	// Allow plugins to short-circuit process and send errors
	$errors = new WP_Error();
    $errors = apply_filters( 'user_activation_errors', $errors, $user->user_login, $user->user_email );

    if ( $errors->get_error_code() )
        return $errors;

    $wpdb->update($wpdb->users, array('user_activation_key' => ''), array('user_login' => $login) );

    $user_object = new WP_User($user->ID);
    $user_object->set_role(get_option('default_role'));
    unset($user_object);

    $pass = __('Same as when you signed up.', 'theme-my-login');
    if ( $newpass ) {
        $pass = wp_generate_password();
        wp_set_password($pass, $user->ID);
    }

    wp_new_user_notification($user->ID, $pass);

    return true;
}

/**
 * Handles activating a new user by admin approval.
 *
 * @param string $id User's ID
 * @param bool $newpass Whether or not to assign a new password
 * @return bool Returns false if not a valid user
 */
function wdbj_tml_user_mod_approve_new_user($id, $newpass = false) {
    global $wpdb;

    $id = (int) $id;

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID = %d", $id));
    if ( empty( $user ) )
        return false;
		
	do_action('approve_user', $user->ID);

    $wpdb->update($wpdb->users, array('user_activation_key' => ''), array('ID' => $id) );

    $user_object = new WP_User($user->ID);
    $user_object->set_role(get_option('default_role'));
    unset($user_object);

    $user_pass = __('Same as when you signed up.', 'theme-my-login');
    if ( $newpass ) {
        $user_pass = wp_generate_password();
        wp_set_password($user_pass, $user->ID);
    }

    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	
    $message  = sprintf(__('You have been approved access to %s', 'theme-my-login'), $blogname) . "\r\n\r\n";
    $message .= sprintf(__('Username: %s', 'theme-my-login'), $user->user_login) . "\r\n";
    $message .= sprintf(__('Password: %s', 'theme-my-login'), $user_pass) . "\r\n\r\n";
    $message .= site_url('wp-login.php', 'login') . "\r\n";	

    $title = sprintf(__('[%s] Registration Approved', 'theme-my-login'), $blogname);

    $title = apply_filters('user_approval_title', $title);
    $message = apply_filters('user_approval_message', $message, $user_pass, $user->ID);

    if ( $message && !wp_mail($user->user_email, $title, $message) )
          die('<p>' . __('The e-mail could not be sent.', 'theme-my-login') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...', 'theme-my-login') . '</p>');

    return true;
}

function wdbj_tml_user_mod_new_user_activation_notification($user_id, $key = '') {
	global $wpdb;
	
	if ( apply_filters('new_user_activation_notification', true) ) {	
	
		$user = new WP_User($user_id);

		$user_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);
	
		if ( empty($key) ) {
			$key = $wpdb->get_var( $wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login) );
			if ( empty($key) ) {
				$key = wp_generate_password(20, false);
				$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
			}
		}
		
		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);			
		
		$activation_url = add_query_arg(array('action' => 'activate', 'key' => $key, 'login' => rawurlencode($user_login)), wp_login_url());
		
		$title = sprintf(__('[%s] Activate Your Account', 'theme-my-login'), $blogname);
		$message  = sprintf(__('Thanks for registering at %s! To complete the activation of your account please click the following link: ', 'theme-my-login'), $blogname) . "\r\n\r\n";
		$message .=  $activation_url . "\r\n";
		
		$title = apply_filters('user_activation_title', $title, $user_id);
		$message = apply_filters('user_activation_message', $message, $user_id, $activation_url);

		wp_mail($user_email, $title, $message);
	}
}

function wdbj_tml_user_mod_new_user_approval_admin_notification($user_id) {
	if ( apply_filters('new_user_approval_admin_notification', true) ) {	
	
		$user = new WP_User($user_id);

		$user_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);
		
		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		$message  = sprintf(__('New user requires approval on your blog %s:', 'theme-my-login'), $blogname) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s', 'theme-my-login'), $user_login) . "\r\n";
		$message .= sprintf(__('E-mail: %s', 'theme-my-login'), $user_email) . "\r\n\r\n";
		$message .= __('To approve or deny this user:', 'theme-my-login') . "\r\n";
		$message .= admin_url('users.php');

		@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Awaiting Approval', 'theme-my-login'), $blogname), $message);
	}
}

?>
