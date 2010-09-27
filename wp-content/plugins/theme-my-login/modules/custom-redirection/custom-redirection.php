<?php
/*
Plugin Name: Custom Redirection
Description: Enabling this module will initialize custom redirection. You will then have to configure the settings via the "Redirection" tab.
*/

add_action('tml_init', 'wdbj_tml_custom_redirect_init');
function wdbj_tml_custom_redirect_init() {
	include( TML_MODULE_DIR . '/custom-redirection/includes/hook-functions.php' );
	add_filter('login_redirect', 'wdbj_tml_custom_redirect_login', 10, 3);
	add_filter('logout_redirect', 'wdbj_tml_custom_redirect_logout', 10, 3);
	add_action('login_form', 'wdbj_tml_custom_redirect_login_form');
}

add_action('tml_admin_init', 'wdbj_tml_custom_redirect_admin_init');
function wdbj_tml_custom_redirect_admin_init() {
    require_once (TML_MODULE_DIR . '/custom-redirection/admin/admin.php');
	add_action('tml_admin_menu', 'wdbj_tml_custom_redirect_admin_menu');
}

add_action('activate_custom-redirection/custom-redirection.php', 'wdbj_tml_custom_redirection_activate');
function wdbj_tml_custom_redirection_activate() {
	$current = wdbj_tml_get_option('redirection');
	$default = wdbj_tml_custom_redirect_default_settings();	
	
	if ( is_array($current) )
		wdbj_tml_update_option(array_merge($default, $current), 'redirection');
	else
		wdbj_tml_update_option($default, 'redirection');
	
	unset($current, $default);
}

function wdbj_tml_custom_redirect_default_settings() {
	global $wp_roles;	
	foreach ( $wp_roles->get_names() as $role => $label ) {
		$options[$role] = array('login_type' => 'default', 'login_url' => '', 'logout_type' => 'default', 'logout_url' => '');
	}
    return $options;
}
        
?>
