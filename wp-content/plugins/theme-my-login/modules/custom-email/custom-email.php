<?php
/*
Plugin Name: Custom E-mail
Description: Enabling this module will initialize custom e-mails. You will then have to configure the settings via the "E-mail" tab.
*/

add_action('tml_init', 'wdbj_tml_custom_email_init');
function wdbj_tml_custom_email_init() {
	require_once( TML_MODULE_DIR . '/custom-email/includes/hook-functions.php' );
	add_action('retrieve_password', 'wdbj_tml_custom_email_retrieve_pass_filters');
	add_action('password_reset', 'wdbj_tml_custom_email_reset_pass_filters', 10, 2);
	add_action('register_post', 'wdbj_tml_custom_email_new_user_filters', 10, 2);
}

add_action('tml_admin_init', 'wdbj_tml_custom_email_admin_init');
function wdbj_tml_custom_email_admin_init() {
    require_once( TML_MODULE_DIR . '/custom-email/admin/admin.php' );
	add_action('tml_admin_menu', 'wdbj_tml_custom_email_admin_menu');
	add_filter('tml_save_settings', 'wdbj_tml_custom_email_save_settings');
}

add_action('activate_custom-email/custom-email.php', 'wdbj_tml_custom_email_activate');
function wdbj_tml_custom_email_activate() {
	$current = wdbj_tml_get_option('email');
	$default = wdbj_tml_custom_email_default_settings();
	
	if ( is_array($current) )
		wdbj_tml_update_option(array_merge($default, $current), 'email');
	else
		wdbj_tml_update_option($default, 'email');
		
	unset($current, $default);
}

function wdbj_tml_custom_email_default_settings() {
	$options = array(
		'mail_from' => '',
		'mail_from_name' => '',
		'mail_content_type' => '',
		'new_user' => array(
			'title' => '',
			'message' => '',
			'admin_disable' => 0
			),
		'retrieve_pass' => array(
			'title' => '',
			'message' => ''
			),
		'reset_pass' => array(
			'title' => '',
			'message' => '',
			'admin_disable' => 0
			)
		);
	return $options;
}

?>
