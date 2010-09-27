<?php
/*
Plugin Name: Theme My Login
Plugin URI: http://www.jfarthing.com/wordpress-plugins/theme-my-login
Description: Themes the WordPress login, registration and forgot password pages according to your theme.
Version: 5.1.6
Author: Jeff Farthing
Author URI: http://www.jfarthing.com
Text Domain: theme-my-login
*/

// Set the default module directory
if ( !defined('TML_MODULE_DIR') )
    define('TML_MODULE_DIR', WP_PLUGIN_DIR . '/theme-my-login/modules');

// Require global configuration class file
require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/class.php' );

// Declare $theme_my_login as global for use within functions
global $theme_my_login;

// Initialize global configuration class
$theme_my_login = new Theme_My_Login();

// Require general plugin functions file
require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/functions.php' );

// Load the plugin textdomain
load_plugin_textdomain('theme-my-login', '', 'theme-my-login/language');

// Load active modules
wdbj_tml_load_active_modules();

// Include admin-functions.php for install/uninstall process
if ( is_admin() ) {
    require_once( WP_PLUGIN_DIR . '/theme-my-login/admin/includes/admin.php' );
    require_once( WP_PLUGIN_DIR . '/theme-my-login/admin/includes/module.php' );
	
    register_activation_hook(__FILE__, 'wdbj_tml_install');
    register_uninstall_hook(__FILE__, 'wdbj_tml_uninstall');
	
	add_action('admin_init', 'wdbj_tml_admin_init');
    add_action('admin_menu', 'wdbj_tml_admin_menu');
	
	// Display warning notices for function overrides
	if ( function_exists('wp_new_user_notification') )
		add_action('tml_settings_page', 'wdbj_tml_add_new_user_notification_override_notice');
	if ( function_exists('wp_password_change_notification') )
		add_action('tml_settings_page', 'wdbj_tml_add_password_change_notification_override_notice');
}

// Load pluggable functions after modules (in case a module needs to override a function)
require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/pluggable-functions.php' );

add_action('plugins_loaded', 'wdbj_tml_load');
function wdbj_tml_load() {
	global $pagenow;
	
	// Bailout if we're at the default login
	if ( 'wp-login.php' == $pagenow )
		return;
	
	require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/hook-functions.php' );
	
    do_action('tml_load');

    add_action('template_redirect', 'wdbj_tml_template_redirect');
    
    add_filter('the_title', 'wdbj_tml_the_title', 10, 2);
    add_filter('single_post_title', 'wdbj_tml_single_post_title');
	
	if ( wdbj_tml_get_option('rewrite_links') )
		add_filter('site_url', 'wdbj_tml_site_url', 10, 3);
		
	add_filter('wp_list_pages_excludes', 'wdbj_tml_list_pages_excludes');
	add_filter('wp_list_pages', 'wdbj_tml_list_pages');
	add_filter('page_link', 'wdbj_tml_page_link', 10, 2);
	
	add_filter('wp_setup_nav_menu_item', 'wdbj_tml_setup_nav_menu_item');
    
	add_shortcode('theme-my-login-page', 'wdbj_tml_page_shortcode');
	add_shortcode('theme-my-login', 'wdbj_tml_shortcode');
    
    if ( wdbj_tml_get_option('enable_widget') ) {
        require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/widget.php' );
		add_action('widgets_init', 'wdbj_tml_register_widget');
		function wdbj_tml_register_widget() {
			return register_widget("Theme_My_Login_Widget");
		}
    }
}

function wdbj_tml_template_redirect() {
    if ( is_page(wdbj_tml_get_option('page_id')) || wdbj_tml_get_option('enable_template_tag') || is_active_widget(false, null, 'theme-my-login') ) {
	
		wdbj_tml_set_error();
	
		do_action('tml_init');

        if ( wdbj_tml_get_option('enable_css') )
            wdbj_tml_get_css();
            
        require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/login-actions.php' );
    }
}

// Template tag
function theme_my_login($args = '') {
	if ( ! wdbj_tml_get_option('enable_template_tag') )
		return false;		
	$args = wp_parse_args($args);
	echo wdbj_tml_shortcode($args);
}

?>