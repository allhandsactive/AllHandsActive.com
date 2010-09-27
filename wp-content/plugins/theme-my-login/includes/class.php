<?php

if ( ! class_exists('Theme_My_Login') ) :
class Theme_My_Login {
	var $options;
	var $errors;
	var $request_action;
	var $request_instance;
	var $current_instance;
	var $count;
	var $redirect_to;
	
	function get_new_instance() {
		++$this->count;
		return 'tml-' . $this->count;
	}
	
	function update_option() {
		$args = func_get_args();
		if ( !is_array($args) )
			return false;
			
		$value = array_shift($args);
		
		$option =& $this->options;
		foreach ( $args as $arg ) {
			$option =& $option[$arg];
		}
		$option = $value;
		return true;
	}

	function delete_option() {
		$args = func_get_args();
		if ( !is_array($args) )
			return false;

		$option = 'options';
		foreach ( $args as $arg ) {
			$option .= "['$arg']";
		}
		eval("unset(\$this->{$option});");
		return true;
	}

	function get_option() {
		$args = func_get_args();
		if ( !is_array($args) )
			return false;

		$option = $this->options;
		foreach ( $args as $arg ) {
			if ( !isset($option[$arg]) )
				return false;
			$option = $option[$arg];
		}
		return $option;
	}

	function save_options() {
		return update_option('theme_my_login', $this->options);
	}

	function set_error($code = '', $error = '', $data = '') {
		if ( empty($code) )
			$this->errors = new WP_Error();
		elseif ( is_a($code, 'WP_Error') )
			$this->errors = $code;
		elseif ( is_a($this->errors, 'WP_Error') )
			$this->errors->add($code, $error, $data);
		else
			$this->errors = new WP_Error($code, $error, $data);
	}

	function get_error($code = '') {
		if ( is_a($this->errors, 'WP_Error') )
			return $this->errors->get_error_message($code);
		return false;
	}
	
	function set_var() {
		$args = func_get_args();
		if ( !is_array($args) || count($args) < 2 )
			return false;
		
		$value = array_shift($args);
		$key = array_shift($args);

		$option = $key;
		foreach ( $args as $arg ) {
			$option .= "['$arg']";
		}
		eval("\$this->{$option} = \$value;");
		return true;
	}
	
	function get_var() {
		$args = func_get_args();
		if ( !is_array($args) )
			return false;

		$key = array_shift($args);
		if ( !isset($this->{$key}) )
			return false;
		
		$option = $this->{$key};
		foreach ( $args as $arg ) {
			if ( !isset($option[$arg]) )
				return $option;
			$option = $option[$arg];
		}
		return $option;
	}

	function default_options() {
		$options = array(
			'show_page' => 1,
			'rewrite_links' => 1,
			'enable_css' => 1,
			'enable_template_tag' => 0,
			'enable_widget' => 0,
			'active_modules' => array()
			);
		return apply_filters('tml_default_option', $options);
	}
	
	function __construct() {
		$this->options = get_option('theme_my_login', $this->default_options());
		$this->request_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
		$this->request_instance = isset($_REQUEST['instance']) ? $_REQUEST['instance'] : 'tml-page';
		$this->count = 0;
	}
	
	function Theme_My_Login() {
		$this->__construct();
	}
}
endif;

?>