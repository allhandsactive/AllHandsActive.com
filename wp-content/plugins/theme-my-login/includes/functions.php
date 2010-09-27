<?php

function wdbj_tml_update_option() {
	global $theme_my_login;
	$args = func_get_args();
	return call_user_func_array(array(&$theme_my_login, 'update_option'), $args);
}

function wdbj_tml_delete_option() {
	global $theme_my_login;
	$args = func_get_args();
	return call_user_func_array(array(&$theme_my_login, 'delete_option'), $args);
}

function wdbj_tml_get_option() {
	global $theme_my_login;
	$args = func_get_args();
	return call_user_func_array(array(&$theme_my_login, 'get_option'), $args);
}

function wdbj_tml_save_options() {
	global $theme_my_login;
	return $theme_my_login->save_options();
}

function wdbj_tml_set_error($code = '', $error = '', $data = '') {
	global $theme_my_login;
	return $theme_my_login->set_error($code, $error, $data);
}

function wdbj_tml_get_error($code = '') {
	global $theme_my_login;
	return $theme_my_login->get_error($code);
}

function wdbj_tml_get_var() {
	global $theme_my_login;
	$args = func_get_args();
	return call_user_func_array(array(&$theme_my_login, 'get_var'), $args);
}

function wdbj_tml_set_var() {
	global $theme_my_login;
	$args = func_get_args();
	return call_user_func_array(array(&$theme_my_login, 'set_var'), $args);
}

function wdbj_tml_get_new_instance() {
	global $theme_my_login;
	return $theme_my_login->get_new_instance();
}

function wdbj_tml_get_current_url($query = '') {
    $schema = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
    $self =  $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    $keys = array('instance', 'action', 'checkemail', 'error', 'loggedout', 'registered', 'redirect_to', 'updated', 'key', '_wpnonce');
    $url = remove_query_arg($keys, $self);

    if ( !empty($query) ) {
        wp_parse_str($query, $r);
		foreach ( $r as $k => $v ) {
			if ( strpos($v, ' ') !== false )
				$r[$k] = rawurlencode($v);
		}
        $url = add_query_arg($r, $url);
    }
    return $url;
}

function wdbj_tml_get_css($file = 'theme-my-login.css') {
    if ( file_exists(get_stylesheet_directory() . "/$file") )
        $css_file = get_stylesheet_directory_uri() . "/$file";
    elseif ( file_exists(get_template_directory() . "/$file") )
        $css_file = get_template_directory_uri() . "/$file";
    else
        $css_file = plugins_url("/theme-my-login/$file");

    wp_enqueue_style('theme-my-login', $css_file);
}

function wdbj_tml_is_module_active($module) {
	$modules = apply_filters('tml_active_modules', wdbj_tml_get_option('active_modules'));
    return in_array($module, (array) $modules);
}

function wdbj_tml_load_active_modules() {
	$modules = apply_filters('tml_active_modules', wdbj_tml_get_option('active_modules'));
	if ( is_array($modules) ) {
		foreach ( $modules as $module ) {
			// check the $plugin filename
			// Validate plugin filename	
			if ( validate_file($module) // $module must validate as file
				|| '.php' != substr($module, -4) // $module must end with '.php'
				|| !file_exists(TML_MODULE_DIR . '/' . $module)	// $module must exist
				)
				continue;

			include_once(TML_MODULE_DIR . '/' . $module);
		}
		unset($module);
	}
	unset($modules);

	do_action('tml_modules_loaded');
}

?>
