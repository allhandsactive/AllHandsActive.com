<?php

$instance = wdbj_tml_get_var('request_instance');
$action = wdbj_tml_get_var('request_action');

//Set a cookie now to see if they are supported by the browser.
setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
if ( SITECOOKIEPATH != COOKIEPATH )
    setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);

// allow plugins to override the default actions, and to add extra actions if they want
if ( has_filter('login_action_' . $action) ) :

do_action('login_action_' . $action, $instance);

else :

$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
switch ( $action ) {
    case 'logout' :
        check_admin_referer('log-out');

        $user = wp_get_current_user();

        $redirect_to = site_url('wp-login.php?loggedout=true');
        $redirect_to = apply_filters('logout_redirect', $redirect_to, isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '', $user);

        wp_logout();

        wp_safe_redirect($redirect_to);
        exit();
        break;
    case 'lostpassword' :
    case 'retrievepassword' :
        if ( $http_post ) {
            require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/login-functions.php' );
            $errors = retrieve_password();
            if ( !is_wp_error($errors) ) {
                $redirect_to = site_url('wp-login.php?checkemail=confirm');
                if ( 'tml-page' != $instance )
                    $redirect_to = wdbj_tml_get_current_url('checkemail=confirm&instance=' . $instance);
                wp_redirect($redirect_to);
                exit();
            } else wdbj_tml_set_error($errors);
        }

        if ( isset($_REQUEST['error']) && 'invalidkey' == $_REQUEST['error'] )
			wdbj_tml_set_error('invalidkey', __('Sorry, that key does not appear to be valid.', 'theme-my-login'));
        break;
    case 'resetpass' :
    case 'rp' :
        require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/login-functions.php' );
        $errors = reset_password($_GET['key'], $_GET['login']);

        if ( !is_wp_error($errors) ) {
            $redirect_to = site_url('wp-login.php?checkemail=newpass');
            if ( 'tml-page' != $instance )
                $redirect_to = wdbj_tml_get_current_url('checkemail=newpass&instance=' . $instance);
			$redirect_to = apply_filters('resetpass_redirect', $redirect_to);
            wp_redirect($redirect_to);
            exit();
        } else wdbj_tml_set_error($errors);

        $redirect_to = site_url('wp-login.php?action=lostpassword&error=invalidkey');
        if ( 'tml-page' != $instance )
            $redirect_to = wdbj_tml_get_current_url('action=lostpassword&error=invalidkey&instance=' . $instance);
        wp_redirect($redirect_to);
        exit();
        break;
    case 'register' :
        if ( !get_option('users_can_register') ) {
            wp_redirect(wdbj_tml_get_current_url('registration=disabled'));
            exit();
        }

        $user_login = '';
        $user_email = '';
        $user_pass = '';
        if ( $http_post ) {
            require_once( ABSPATH . WPINC . '/registration.php' );
            require_once( WP_PLUGIN_DIR . '/theme-my-login/includes/login-functions.php' );

            $user_login = $_POST['user_login'];
            $user_email = $_POST['user_email'];
			
            $errors = register_new_user($user_login, $user_email);
            if ( !is_wp_error($errors) ) {
				$redirect_to = site_url('wp-login.php?checkemail=registered');
				if ( 'tml-page' != $instance )
					$redirect_to = wdbj_tml_get_current_url('checkemail=registered&instance=' . $instance);
				$redirect_to = apply_filters('register_redirect', $redirect_to);
                wp_redirect($redirect_to);
                exit();
            } else wdbj_tml_set_error($errors);
        }
        break;
    case 'login' :
    default:
        $secure_cookie = '';

        // If the user wants ssl but the session is not ssl, force a secure cookie.
        if ( !empty($_POST['log']) && !force_ssl_admin() ) {
            $user_name = sanitize_user($_POST['log']);
            if ( $user = get_userdatabylogin($user_name) ) {
                if ( get_user_option('use_ssl', $user->ID) ) {
                    $secure_cookie = true;
                    force_ssl_admin(true);
                }
            }
        }

        if ( isset($_REQUEST['redirect_to']) && !empty($_REQUEST['redirect_to']) ) {
            $redirect_to = $_REQUEST['redirect_to'];
            // Redirect to https if user wants ssl
            if ( $secure_cookie && false !== strpos($redirect_to, 'wp-admin') )
                $redirect_to = preg_replace('|^http://|', 'https://', $redirect_to);
        } else {
            $redirect_to = admin_url();
        }

        if ( !$secure_cookie && is_ssl() && force_ssl_login() && !force_ssl_admin() && ( 0 !== strpos($redirect_to, 'https') ) && ( 0 === strpos($redirect_to, 'http') ) )
            $secure_cookie = false;

		$user = wp_signon('', $secure_cookie);

		$redirect_to = apply_filters('login_redirect', $redirect_to, isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '', $user);
		
		wdbj_tml_set_var($redirect_to, 'redirect_to');
		
		if ( $http_post ) {
			if ( !is_wp_error($user) ) {
				// If the user can't edit posts, send them to their profile.
				if ( !$user->has_cap('edit_posts') && ( empty( $redirect_to ) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url() ) )
					$redirect_to = admin_url('profile.php');
				wp_safe_redirect($redirect_to);
				exit();
			}
			
			wdbj_tml_set_error($user);
		}
        break;
}

endif;

?>
