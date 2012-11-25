<?php function tml_init() {
	global $pagenow, $theme_my_login;

	if ( 'wp-login.php' == $pagenow ) {
		$redirect_to = $theme_my_login->get_login_page_link();
		wp_redirect( $redirect_to );
		exit;
	}
}

add_action( 'init', 'tml_init' ); ?>
