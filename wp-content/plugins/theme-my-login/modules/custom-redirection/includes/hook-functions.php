<?php

function wdbj_tml_custom_redirect_login_form($instance_id) {
	$jump_back_to = 'tml-page' == $instance_id ? 'previous' : 'current';
	wp_original_referer_field(true, $jump_back_to);
	echo "\n";
}

function wdbj_tml_custom_redirect_login($redirect_to, $request, $user) {
	global $pagenow;

	if ( 'wp-login.php' == $pagenow )
		return $redirect_to;
	
	// Bailout if this isn't a login
	if ( 'POST' != $_SERVER['REQUEST_METHOD'] )
		return $redirect_to;
		
	$_redirect_to = '';
		
	// User is logged in
	if ( is_object($user) && !is_wp_error($user) ) {
		$user_role = reset($user->roles);
		$redirection = wdbj_tml_get_option('redirection', $user_role);
		if ( 'default' == $redirection['login_type'] ) {
			// Do nothing
		} elseif ( 'referer' == $redirection['login_type'] ) {
			// Determine the correct referer
			if ( !$http_referer = wp_get_original_referer() )
				$http_referer = wp_get_referer();
			$_redirect_to = $http_referer;
		} else {
			$_redirect_to = $redirection['login_url'];
			// Allow a few user specific variables
			$replace = array('%user_id%' => $user->ID, '%user_login%' => $user->user_login);
			$_redirect_to = str_replace(array_keys($replace), array_values($replace), $_redirect_to);
		}
		// Let requested URL take precedence
		if ( !empty($request) && admin_url() != $request )
			$_redirect_to = $request;
	}
	
	// Make sure it's not empty!
	if ( !empty($_redirect_to) )
		$redirect_to = $_redirect_to;
	
	return $redirect_to;
}

function wdbj_tml_custom_redirect_logout($redirect_to, $request, $user) {

	$_redirect_to = '';

	if ( is_object($user) && !is_wp_error($user) ) {
		$user_role = reset($user->roles);
		$redirection = wdbj_tml_get_option('redirection', $user_role);
		if ( 'default' == $redirection['logout_type'] ) {
			// Do nothing
		} elseif ( 'referer' == $redirection['logout_type'] ) {
			// Determine the correct referer
			if ( !$http_referer = wp_get_original_referer() )
				$http_referer = wp_get_referer();
			// Clean some args
			$http_referer = remove_query_arg(array('instance', 'action', 'checkemail', 'error', 'loggedout', 'registered', 'redirect_to', 'updated', 'key', '_wpnonce'), $http_referer);
			$_redirect_to = $http_referer;
		} else {
			$_redirect_to = $redirection['logout_url'];
			// Allow a few user specific variables
			$replace = array('%user_id%' => $user->ID, '%user_login%' => $user->user_login);
			$_redirect_to = str_replace(array_keys($replace), array_values($replace), $_redirect_to);
		}
	}
	
	// Make sure it's not empty!
	if ( !empty($_redirect_to) )
		$redirect_to = $_redirect_to;
		
	// Make sure it's not an admin URL
	if ( strpos($redirect_to, 'wp-admin') !== false )
		$redirect_to = add_query_arg('loggedout', 'true', get_permalink(wdbj_tml_get_option('page_id')));

	return $redirect_to;
}

?>
