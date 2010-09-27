<?php

function wdbj_tml_activate_module($module) {
	$module = plugin_basename(trim($module));
	$valid = wdbj_tml_validate_module($module);
	if ( is_wp_error($valid) )
		return $valid;
	
	$current = (array) wdbj_tml_get_option('active_modules');
	if ( ! wdbj_tml_is_module_active($module) ) {
		//ob_start();
		@include (TML_MODULE_DIR . '/' . $module);
		$current[] = $module;
		sort($current);
		do_action('tml_activate_module', trim($module));
		// We will not use this since our function modifies the global plugin object instead of saving to the DB
		//update_option('theme_my_login', $current);
		wdbj_tml_update_option($current, 'active_modules');
		do_action('activate_' . trim($module));
		do_action('tml_activated_module', trim($module));
		//ob_end_clean();
	}

	return null;
}

function wdbj_tml_deactivate_modules($modules, $silent= false) {
	$current = (array) wdbj_tml_get_option('active_modules');
	
	if ( ! is_array($modules) )
		$modules = array($modules);

	foreach ( $modules as $module ) {
		$module = plugin_basename($module);
		if( ! wdbj_tml_is_module_active($module) )
			continue;
		if ( ! $silent )
			do_action('tml_deactivate_module', trim($module));

		$key = array_search( $module, (array) $current );

		if ( false !== $key )
			array_splice( $current, $key, 1 );

		if ( ! $silent ) {
			do_action( 'deactivate_' . trim( $module ) );
			do_action( 'tml_deactivated_module', trim( $module ) );
		}
	}
	
	// We will not use this since the function modifies our global plugin object instead of saving to the DB
	//update_option('theme_my_login', $current);
	wdbj_tml_update_option($current, 'active_modules');
}

function wdbj_tml_activate_modules($modules) {
	if ( !is_array($modules) )
		$modules = array($modules);

	$errors = array();
	foreach ( (array) $modules as $module ) {
		$result = wdbj_tml_activate_module($module);
		if ( is_wp_error($result) )
			$errors[$module] = $result;
	}

	if ( !empty($errors) )
		return new WP_Error('plugins_invalid', __('One of the plugins is invalid.', 'theme-my-login'), $errors);

	return true;
}

function wdbj_tml_validate_module($module) {
	if ( validate_file($module) )
		return new WP_Error('plugin_invalid', __('Invalid plugin path.', 'theme-my-login'));
	if ( ! file_exists(TML_MODULE_DIR . '/' . $module) )
		return new WP_Error('plugin_not_found', __('Plugin file does not exist.', 'theme-my-login'));

	$installed_modules = get_plugins('/theme-my-login/modules');
	if ( ! isset($installed_modules[$module]) )
		return new WP_Error('no_plugin_header', __('The plugin does not have a valid header.', 'theme-my-login'));
	return 0;
}

function wdbj_tml_add_menu_page($menu_title, $file, $function = '', $function_args = array(), $position = NULL) {
    global $wdbj_tml_admin_menu;

    $file = plugin_basename($file);

    $hookname = get_plugin_page_hookname($file, '');
	$hookname = preg_replace('|[^a-zA-Z0-9_:.]|', '-', $hookname);
    if ( !empty($function) && !empty($hookname) )
        add_action($hookname, $function);

    $new_menu = array($menu_title, $file, $hookname, $function_args);

    if ( NULL === $position )
        $wdbj_tml_admin_menu[] = $new_menu;
    else
        $wdbj_tml_admin_menu[$position] = $new_menu;

    return $hookname;
}

function wdbj_tml_add_submenu_page($parent, $menu_title, $file, $function = array(), $function_args = '') {
	global $wdbj_tml_admin_submenu;
	
	$file = plugin_basename($file);
	$parent = plugin_basename($parent);
	
	$count = ( isset($wdbj_tml_admin_submenu[$parent]) && is_array($wdbj_tml_admin_submenu[$parent]) ) ? count($wdbj_tml_admin_submenu[$parent]) + 1 : 1;
	
	$hookname = get_plugin_page_hookname($parent . '-' . $count, '');
	$hookname = preg_replace('|[^a-zA-Z0-9_:.]|', '-', $hookname);
	if ( !empty($function) && !empty($hookname) )
		add_action($hookname, $function);
	
	$wdbj_tml_admin_submenu[$parent][] = array($menu_title, $file, $hookname, $function_args);
	
	return $hookname;
}

?>
