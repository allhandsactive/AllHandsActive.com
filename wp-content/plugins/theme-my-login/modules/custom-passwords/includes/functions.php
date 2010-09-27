<?php

function wdbj_tml_custom_pass_validate_reset_key($key, $login) {
    global $wpdb;

    $key = preg_replace('/[^a-z0-9]/i', '', $key);

    if ( empty( $key ) || !is_string( $key ) )
        return new WP_Error('invalid_key', __('Invalid key', 'theme-my-login'));

    if ( empty($login) || !is_string($login) )
        return new WP_Error('invalid_key', __('Invalid key', 'theme-my-login'));

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));
    if ( empty( $user ) )
        return new WP_Error('invalid_key', __('Invalid key', 'theme-my-login'));
		
	return $user;
}

function wdbj_tml_custom_pass_reset_pass() {
	
	$user = wdbj_tml_custom_pass_validate_reset_key($_REQUEST['key'], $_REQUEST['login']);
	if ( is_wp_error($user) )
		return $user;
	
	$errors = wdbj_tml_custom_pass_errors(new WP_Error());
	if ( $errors->get_error_code() )
		return $errors;
	
	$new_pass = $_POST['pass1'];

    do_action('password_reset', $user->user_login, $new_pass);

    wp_set_password($new_pass, $user->ID);
	update_usermeta($user->ID, 'default_password_nag', false);
    $message  = sprintf(__('Username: %s', 'theme-my-login'), $user->user_login) . "\r\n";
    $message .= sprintf(__('Password: %s', 'theme-my-login'), $new_pass) . "\r\n";
    $message .= site_url('wp-login.php', 'login') . "\r\n";

    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    $title = sprintf(__('[%s] Your new password', 'theme-my-login'), $blogname);

    $title = apply_filters('password_reset_title', $title, $user->ID);
    $message = apply_filters('password_reset_message', $message, $new_pass, $user->ID);

    if ( $message && !wp_mail($user->user_email, $title, $message) )
		die('<p>' . __('The e-mail could not be sent.', 'theme-my-login') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...', 'theme-my-login') . '</p>');

    wp_password_change_notification($user);

    return true;
}

?>
