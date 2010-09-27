<?php

include_once( TML_MODULE_DIR . '/custom-email/includes/hook-functions.php' );

function wdbj_tml_user_mod_custom_email_user_activation_filters() {
	wdbj_tml_custom_email_headers();
	add_filter('user_activation_title', 'wdbj_tml_user_mod_user_activation_title');
	add_filter('user_activation_message', 'wdbj_tml_user_mod_user_activation_message', 10, 3);
}

function wdbj_tml_user_mod_custom_email_user_approval_filters() {
	wdbj_tml_custom_email_headers();
	add_filter('user_approval_title', 'wdbj_tml_user_mod_user_approval_title');
	add_filter('user_approval_message', 'wdbj_tml_user_mod_user_approval_message', 10, 3);
}

function wdbj_tml_user_mod_custom_email_user_denial_filters() {
	wdbj_tml_custom_email_headers();
	add_filter('user_denial_title', 'wdbj_tml_user_mod_user_denial_title');
	add_filter('user_denial_message', 'wdbj_tml_user_mod_user_denial_message', 10, 2);
}

function wdbj_tml_user_mod_user_activation_title($title) {
	$_title = wdbj_tml_get_option('email', 'user_activation', 'title');
	return empty($_title) ? $title : wdbj_tml_custom_email_replace_vars($_title, $user_id);
}

function wdbj_tml_user_mod_user_activation_message($message, $user_id, $activation_url) {
	$_message = wdbj_tml_get_option('email', 'user_activation', 'message');
	$replacements = array(
		'%activateurl%' => $activation_url
		);	
	return empty($_message) ? $message : wdbj_tml_custom_email_replace_vars($_message, $user_id, $replacements);
}

function wdbj_tml_user_mod_user_approval_title($title) {
	$_title = wdbj_tml_get_option('email', 'user_approval', 'title');
	return empty($_title) ? $title : wdbj_tml_custom_email_replace_vars($_title, $user_id);
}

function wdbj_tml_user_mod_user_approval_message($message, $new_pass, $user_id) {
	$_message = wdbj_tml_get_option('email', 'user_approval', 'message');
	$replacements = array(
		'%loginurl%' => site_url('wp-login.php', 'login'),
		'%user_pass%' => $new_pass
		);	
	return empty($_message) ? $message : wdbj_tml_custom_email_replace_vars($_message, $user_id, $replacements);
}

function wdbj_tml_user_mod_user_denial_title($title) {
	$_title = wdbj_tml_get_option('email', 'user_denial', 'title');
	return empty($_title) ? $title : wdbj_tml_custom_email_replace_vars($_title, $user_id);
}

function wdbj_tml_user_mod_user_denial_message($message, $user_id) {
	$_message = wdbj_tml_get_option('email', 'user_denial', 'message');
	$replacements = array(
		'%loginurl%' => site_url('wp-login.php', 'login'),
		'%user_pass%' => $new_pass
		);	
	return empty($_message) ? $message : wdbj_tml_custom_email_replace_vars($_message, $user_id, $replacements);
}

?>
