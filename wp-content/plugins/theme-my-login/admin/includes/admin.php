<?php

function wdbj_tml_add_new_user_notification_override_notice() {
	add_action('admin_notices', 'wdbj_tml_new_user_notification_override_notice');
}

function wdbj_tml_add_password_change_notification_override_notice() {
	add_action('admin_notices', 'wdbj_tml_password_change_notification_override_notice');
}

function wdbj_tml_new_user_notification_override_notice() {
	$message = __('<strong>WARNING</strong>: The function <em>wp_new_user_notification</em> has already been overridden by another plugin. ', 'theme-my-login');
	$message .= __('Some features of <em>Theme My Login</em> may not function properly.', 'theme-my-login');
	echo '<div class="error"><p>' . $message . '</p></div>';
}

function wdbj_tml_password_change_notification_override_notice() {
	$message = __('<strong>WARNING</strong>: The function <em>wp_password_change_notification</em> has already been overridden by another plugin. ', 'theme-my-login');
	$message .= __('Some features of <em>Theme My Login</em> may not function properly.', 'theme-my-login');
	echo '<div class="error"><p>' . $message . '</p></div>';
}

function wdbj_tml_admin_menu() {
	// Create our settings link in the default WP "Settings" menu
    add_options_page(__('Theme My Login', 'theme-my-login'), __('Theme My Login', 'theme-my-login'), 'manage_options', 'theme-my-login/admin/options.php');
}

function wdbj_tml_admin_init() {
	// Register our settings in the global 'whitelist_settings'
    register_setting('theme_my_login', 'theme_my_login',  'wdbj_tml_save_settings');
	
	// Hook into the loading of our dedicated settings page
	add_action('load-theme-my-login/admin/options.php', 'wdbj_tml_load_settings_page');
	
	// Create a hook for modules to use
    do_action('tml_admin_init');
}

function wdbj_tml_load_settings_page() {
	global $theme_my_login, $user_ID;
	
	do_action('tml_settings_page');
	
	// Enqueue neccessary scripts and styles
    wp_enqueue_script('theme-my-login-admin', plugins_url('/theme-my-login/admin/js/theme-my-login-admin.js'));
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_style('theme-my-login-admin', plugins_url('/theme-my-login/admin/css/theme-my-login-admin.css'));

	// Set the correct admin style according to user setting (Only supports default admin schemes)
	if ( function_exists('get_user_meta') )
		$admin_color = get_user_meta($user_ID, 'admin_color');
	else
		$admin_color = get_usermeta($user_ID, 'admin_color');
		
    if ( 'classic' == $admin_color )
		wp_enqueue_style('theme-my-login-colors-classic', plugins_url('/theme-my-login/admin/css/colors-classic.css'));
    else
        wp_enqueue_style('theme-my-login-colors-fresh', plugins_url('/theme-my-login/admin/css/colors-fresh.css'));
	
	// Handle activation/deactivation of modules
	if ( isset($theme_my_login->options['activate_modules']) || isset($theme_my_login->options['deactivate_modules']) ) {
		// If we have modules to activate
		if ( isset($theme_my_login->options['activate_modules']) ) {
			// Attempt to activate them
			$result = wdbj_tml_activate_modules($theme_my_login->options['activate_modules']);
			// Check for WP_Error
			if ( is_wp_error($result) ) {
				// Loop through each module in the WP_Error object
				foreach ( $result->get_error_data('plugins_invalid') as $module => $wp_error ) {
					// Store the module and error message to a temporary array which will be passed to 'admin_notices'
					if ( is_wp_error($wp_error) )
						$theme_my_login->options['module_errors'][$module] = $wp_error->get_error_message();
				}
			}
			// Unset the 'activate_modules' array
			unset($theme_my_login->options['activate_modules']);
		}

		// If we have modules to deactivate
		if ( isset($theme_my_login->options['deactivate_modules']) ) {
			// Deactive them
			wdbj_tml_deactivate_modules($theme_my_login->options['deactivate_modules']);
			// Unset the 'deactivate_modules' array
			unset($theme_my_login->options['deactivate_modules']);
		}
		
		// Update the options in the DB
		wdbj_tml_save_options();
		
		// Redirect so that the newly activated modules can be included and newly unactivated modules can not be included
		$redirect = isset($theme_my_login->options['module_errors']) ? admin_url('options-general.php?page=theme-my-login/admin/options.php') : add_query_arg('updated', 'true');
		wp_redirect($redirect);
		exit();
	}
	
	// If we have errors to display, hook into 'admin_notices' to display them
	if ( isset($theme_my_login->options['module_errors']) && $theme_my_login->options['module_errors'] )
		add_action('admin_notices', 'wdbj_tml_module_error_notice');
}

function wdbj_tml_module_error_notice() {
	global $theme_my_login;
	
	// If we have errors to display
	if ( isset($theme_my_login->options['module_errors']) ) {
		// Display them
		echo '<div class="error">';
		foreach ( $theme_my_login->options['module_errors'] as $module => $error ) {
			echo '<p><strong>' . sprintf(__('ERROR: The module "$module" could not be activated (%s).', 'theme-my-login'), $error) . '</strong></p>';
		}
		echo '</div>';
		// Unset the error array
		unset($theme_my_login->options['module_errors']);
		// Update the options in the DB
		wdbj_tml_save_options();
	}
}

function wdbj_tml_save_settings($settings) {
	global $theme_my_login;
	
	// Assign current settings
	$current = $theme_my_login->options;
	
	// Sanitize new settings
	$settings['page_id'] = absint($settings['page_id']);
	$settings['show_page'] = ( isset($settings['show_page']) && $settings['show_page'] ) ? 1 : 0;
	$settings['rewrite_links'] = ( isset($settings['rewrite_links']) && $settings['rewrite_links'] ) ? 1 : 0;
	$settings['enable_css'] = ( isset($settings['enable_css']) && $settings['enable_css'] ) ? 1 : 0;
	$settings['enable_template_tag'] = ( isset($settings['enable_template_tag']) && $settings['enable_template_tag'] ) ? 1 : 0;
	$settings['enable_widget'] = ( isset($settings['enable_widget']) && $settings['enable_widget'] ) ? 1 : 0;
	if ( isset($_POST['tml_editing_modules']) ) {
		$settings['modules'] = isset($settings['modules']) ? (array) $settings['modules'] : array();
		
		// Set modules to be activated
		if ( $activate = array_diff($settings['modules'], (array) $current['active_modules']) )
			$settings['activate_modules'] = $activate;
			
		// Set modules to be deactivated
		if ( $deactivate = array_diff((array) $current['active_modules'], $settings['modules']) )
			$settings['deactivate_modules'] = $deactivate;
			
		// Unset 'modules' as it is only relevent here
		unset($settings['modules']);
	}

	// Merge current settings
    $settings = wp_parse_args($settings, $current);
	
	// Allow plugins/modules to add/modify settings
    $settings = apply_filters('tml_save_settings', $settings);
	
	return $settings;
}

function wdbj_tml_install() {
	global $theme_my_login;
	
    $previous_install = get_option('theme_my_login');
    if ( $previous_install ) {
        if ( version_compare($previous_install['version'], '4.4', '<') )
            remove_role('denied');
    }

	if ( $page = get_page_by_title('Login') ) {
		$page_id = $page->ID;
		if ( 'trash' == $page->post_status )
			wp_untrash_post($page_id);
		if ( strpos($page->post_content, '[theme-my-login-page]') === false ) {
			$page->post_content = preg_replace("/(\[theme-my-login .*\])/", '[theme-my-login-page]', $page->post_content);
			wp_update_post($page);
		}
	} else {
		$insert = array(
			'post_title' => 'Login',
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_content' => '[theme-my-login-page]',
			'comment_status' => 'closed',
			'ping_status' => 'closed'
			);
		$page_id = wp_insert_post($insert);
	}
	
    $options = wp_parse_args($previous_install, Theme_My_Login::default_options());
        
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/theme-my-login/theme-my-login.php');
	$options['version'] = $plugin_data['Version'];
	$options['page_id'] = $page_id;
    return update_option('theme_my_login', $options);
}

function wdbj_tml_uninstall() {
    $options = get_option('theme_my_login');
	
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	// Run module uninstall hooks
	$modules = get_plugins('/theme-my-login/modules');
	foreach ( array_keys($modules) as $module ) {
		$module = plugin_basename(trim($module));

		$valid = wdbj_tml_validate_module($module);
		if ( is_wp_error($valid) )
			continue;
			
		@include (TML_MODULE_DIR . '/' . $module);
		do_action('uninstall_' . trim($module));
	}

	// Delete the page
    if ( get_page($options['page_id']) )
        wp_delete_post($options['page_id']);
		
	// Delete options
    delete_option('theme_my_login');
	delete_option('widget_theme-my-login');
}

?>
