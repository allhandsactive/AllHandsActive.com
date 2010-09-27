<?php
/*
Plugin Name: Custom Passwords
Description: Enabling this module will initialize and enable custom passwords. There are no other settings for this module.
*/

add_action('tml_init', 'wdbj_tml_custom_pass_init');
function wdbj_tml_custom_pass_init() {
	include_once( TML_MODULE_DIR . '/custom-passwords/includes/hook-functions.php' );
	require_once( TML_MODULE_DIR . '/custom-passwords/includes/functions.php' );
	// Password registration
	add_action('register_form', 'wdbj_tml_custom_pass_form');
	add_filter('registration_errors', 'wdbj_tml_custom_pass_errors');
	add_filter('user_registration_pass', 'wdbj_tml_custom_pass_set_pass');
	// Password reset
	add_action('login_form_resetpass', 'wdbj_tml_custom_pass_reset_form');
	add_action('login_form_rp', 'wdbj_tml_custom_pass_reset_form');
	add_action('login_action_resetpass', 'wdbj_tml_custom_pass_reset_action');
	add_action('login_action_rp', 'wdbj_tml_custom_pass_reset_action');
	// Template messages
	add_filter('login_message', 'wdbj_tml_custom_pass_login_message');
	add_filter('lostpassword_message', 'wdbj_tml_custom_pass_lostpassword_message');
	add_action('template_redirect', 'wdbj_tml_custom_pass_messages', 100);
	// Redirection
	add_filter('register_redirect', 'wdbj_tml_custom_pass_register_redirect');
	add_filter('resetpass_redirect', 'wdbj_tml_custom_pass_resetpass_redirect');
}

?>
