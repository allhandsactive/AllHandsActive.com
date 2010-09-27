<?php

function wdbj_tml_user_mod_user_moderation($user_id) {
    global $wpdb;
    
	require_once (TML_MODULE_DIR . '/user-moderation/includes/functions.php');
	
	// Disable original notification
	add_filter('new_user_admin_notification', create_function('', "return false;"), 100);
	add_filter('new_user_notification', create_function('', "return false;"), 100);
	
    $user = new WP_User($user_id);
    $user->set_role('pending');
    if ( 'email' == wdbj_tml_get_option('moderation', 'type') ) {
        $key = wp_generate_password(20, false);
        $wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user->user_login));
		wdbj_tml_user_mod_new_user_activation_notification($user_id, $key);
    } elseif ( 'admin' == wdbj_tml_get_option('moderation', 'type') ) {
		wdbj_tml_user_mod_new_user_approval_admin_notification($user_id);
	}
}

function wdbj_tml_user_mod_authenticate($user, $username, $password) {
    global $wpdb;

    if ( is_a($user, 'WP_User') ) {
        $user_role = reset($user->roles);
        if ( 'pending' == $user_role ) {
            if ( 'email' == wdbj_tml_get_option('moderation', 'type') )
                return new WP_Error('pending', __('<strong>ERROR</strong>: You have not yet confirmed your e-mail address.', 'theme-my-login'));
            else
                return new WP_Error('pending', __('<strong>ERROR</strong>: Your registration has not yet been approved.', 'theme-my-login'));
        }
    }
    return $user;
}

function wdbj_tml_user_mod_user_activation() {
    require_once( TML_MODULE_DIR . '/user-moderation/includes/functions.php' );
	
    $newpass = ( wdbj_tml_is_module_active('custom-passwords/custom-passwords.php') ) ? 0 : 1;
    $errors = wdbj_tml_user_mod_activate_new_user($_GET['key'], $_GET['login'], $newpass);

    if ( !is_wp_error($errors) ) {
        $redirect_to = site_url('wp-login.php?activation=complete');
        if ( 'tml-page' != wdbj_tml_get_var('request_instance') )
            $redirect_to = wdbj_tml_get_current_url('activation=complete&instance=' . wdbj_tml_get_var('request_instance'));
        wp_redirect($redirect_to);
        exit();
    }

    $redirect_to = site_url('wp-login.php?activation=invalidkey');
    if ( 'tml-page' != wdbj_tml_get_var('request_instance') )
        $redirect_to = wdbj_tml_get_current_url('activation=invalidkey&instance=' . wdbj_tml_get_var('request_instance'));
    wp_redirect($redirect_to);
    exit();
}

function wdbj_tml_user_mod_allow_password_reset($allow, $user_id) {
    $user = new WP_User($user_id);
    $user_role = reset($user->roles);
    if ( 'pending' == $user_role )
        $allow = false;
    return $allow;
}

function wdbj_tml_user_mod_register_redirect($redirect_to) {
	$redirect_to = site_url('wp-login.php');
	if ( 'tml-page' != wdbj_tml_get_var('request_instance') )
		$redirect_to = wdbj_tml_get_current_url('instance=' . wdbj_tml_get_var('request_instance'));

	if ( 'email' == wdbj_tml_get_option('moderation', 'type') )
		$redirect_to = add_query_arg('pending', 'activation', $redirect_to);
	elseif ( 'admin' == wdbj_tml_get_option('moderation', 'type') )
		$redirect_to = add_query_arg('pending', 'approval', $redirect_to);
	return $redirect_to;
}

?>
