<?php

function wdbj_tml_display() {
	$current_instance = wdbj_tml_get_var('current_instance');
	$request_instance = wdbj_tml_get_var('request_instance');

    $action = isset($current_instance['default_action']) ? $current_instance['default_action'] : 'login';
    if ( $request_instance == $current_instance['instance_id'] )
        $action = wdbj_tml_get_var('request_action');

    ob_start();
    echo $current_instance['before_widget'];
    if ( $current_instance['show_title'] )
        echo $current_instance['before_title'] . wdbj_tml_get_title($action) . $current_instance['after_title'] . "\n";
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $user_role = reset($user->roles);
		echo '<div class="login" id="' . $current_instance['instance_id'] . '">' . "\n";
        if ( $current_instance['show_gravatar'] )
            echo '<div class="tml-user-avatar">' . get_avatar( $user->ID, $current_instance['gravatar_size'] ) . '</div>' . "\n";
        echo '<ul class="tml-user-links">' . "\n";
        $user_links = array(
            array('title' => __('Dashboard', 'theme-my-login'), 'url' => admin_url()),
            array('title' => __('Profile', 'theme-my-login'), 'url' => admin_url('profile.php'))
            );
        $user_links = apply_filters('tml_user_links', $user_links);
        if ( $user_links ) {
            foreach ( $user_links as $link ) {
                echo '<li><a href="' . $link['url'] . '">' . $link['title'] . '</a></li>' . "\n";
            }
        }
		echo '<li><a href="' . wp_logout_url() . '">' . __('Log out', 'theme-my-login') . '</a></li>' . "\n";
		echo "</ul>\n</div>\n";
    } else {
		if ( has_filter('login_form_' . $action) ) {
			do_action('login_form_' . $action, $current_instance['instance_id']);
		} else {
			switch ( $action ) {
				case 'lostpassword' :
				case 'retrievepassword' :
					wdbj_tml_get_lost_password_form();
					break;
				case 'register' :
					wdbj_tml_get_register_form();
					break;
				case 'login' :
				default :
					wdbj_tml_get_login_form();
                break;
			}
        }
    }
    echo $current_instance['after_widget'] . "\n";
	unset($current_instance, $request_instance, $action, $user_links, $link);
    $contents = ob_get_contents();
    ob_end_clean();
    return apply_filters('tml_display', $contents);
}

function wdbj_tml_get_display_options() {
    $display_options = array(
        'instance_id' => 'tml-page',
        'is_active' => 0,
        'default_action' => 'login',
        'show_title' => 1,
        'show_log_link' => 1,
        'show_reg_link' => 1,
        'show_pass_link' => 1,
        'register_widget' => 0,
        'lost_pass_widget' => 0,
        'logged_in_widget' => 1,
        'show_gravatar' => 1,
        'gravatar_size' => 50,
        'before_widget' => '<li>',
        'after_widget' => '</li>',
        'before_title' => '<h2>',
        'after_title' => '</h2>'
        );
    return apply_filters('tml_display_options', $display_options);
}

function wdbj_tml_get_title($action = '') {
    if ( empty($action) )
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $title = sprintf(__('Welcome, %s', 'theme-my-login'), $user->display_name);
    } else {
        switch ( $action ) {
            case 'register':
                $title = __('Register', 'theme-my-login');
                break;
            case 'lostpassword':
            case 'retrievepassword':
            case 'resetpass':
            case 'rp':
                $title = __('Lost Password', 'theme-my-login');
                break;
            case 'login':
            default:
                $title = __('Log In', 'theme-my-login');
        }
    }
    return apply_filters('tml_title', $title, $action);
}

function wdbj_tml_get_header($message = '') {
    global $error;
	
	$wp_error = wdbj_tml_get_var('errors');
	$current_instance = wdbj_tml_get_var('current_instance');

    if ( empty($wp_error) )
        $wp_error = new WP_Error();

    echo '<div class="login" id="' . $current_instance['instance_id'] . '">';

	$message = apply_filters('login_message', $message);
    if ( !empty($message) )
        echo '<p class="message">' . $message . "</p>\n";

    // Incase a plugin uses $error rather than the $errors object
    if ( !empty( $error ) ) {
        $wp_error->add('error', $error);
        unset($error);
    }

    if ( $current_instance['is_active'] ) {
        if ( $wp_error->get_error_code() ) {
            $errors = '';
            $messages = '';
            foreach ( $wp_error->get_error_codes() as $code ) {
                $severity = $wp_error->get_error_data($code);
                foreach ( $wp_error->get_error_messages($code) as $error ) {
                    if ( 'message' == $severity )
                        $messages .= '    ' . $error . "<br />\n";
                    else
                        $errors .= '    ' . $error . "<br />\n";
                }
            }
            if ( !empty($errors) )
                echo '<p class="error">' . apply_filters('login_errors', $errors) . "</p>\n";
            if ( !empty($messages) )
                echo '<p class="message">' . apply_filters('login_messages', $messages) . "</p>\n";
        }
    }
}

function wdbj_tml_get_footer($login_link = true, $register_link = true, $password_link = true) {
	$current_instance = wdbj_tml_get_var('current_instance');
    
    echo '<ul class="tml-links">' . "\n";
    if ( $login_link && $current_instance['show_log_link'] ) {
        $url = wdbj_tml_get_current_url('instance=' . $current_instance['instance_id']);
        echo '<li><a href="' . esc_url($url) . '">' . wdbj_tml_get_title('login') . '</a></li>' . "\n";
    }
    if ( $register_link && $current_instance['show_reg_link'] && get_option('users_can_register') ) {
        $url = ( $current_instance['register_widget'] ) ? wdbj_tml_get_current_url('action=register&instance=' . $current_instance['instance_id']) : site_url('wp-login.php?action=register', 'login');
        echo '<li><a href="' . esc_url($url) . '">' . wdbj_tml_get_title('register') . '</a></li>' . "\n";
    }
    if ( $password_link && $current_instance['show_pass_link'] ) {
        $url = ( $current_instance['lost_pass_widget'] ) ? wdbj_tml_get_current_url('action=lostpassword&instance=' . $current_instance['instance_id']) : site_url('wp-login.php?action=lostpassword', 'login');
        echo '<li><a href="' . esc_url($url) . '">' . wdbj_tml_get_title('lostpassword') . '</a></li>' . "\n";
    }
    echo '</ul>' . "\n";
    echo '</div>' . "\n";
}

function wdbj_tml_get_login_form() {
	$current_instance = wdbj_tml_get_var('current_instance');
	
    // Clear errors if loggedout is set.
    if ( !empty($_GET['loggedout']) )
        wdbj_tml_set_error();

    // If cookies are disabled we can't log in even with a valid user+pass
    if ( isset($_POST['testcookie']) && empty($_COOKIE[TEST_COOKIE]) )
        wdbj_tml_set_error('test_cookie', __("<strong>ERROR</strong>: Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to use WordPress.", 'theme-my-login'));

    // Some parts of this script use the main login form to display a message
    if ( $current_instance['is_active'] ) {
        if        ( isset($_GET['loggedout']) && TRUE == $_GET['loggedout'] )
            wdbj_tml_set_error('loggedout', __('You are now logged out.', 'theme-my-login'), 'message');
        elseif    ( isset($_GET['registration']) && 'disabled' == $_GET['registration'] )
            wdbj_tml_set_error('registerdisabled', __('User registration is currently not allowed.', 'theme-my-login'));
        elseif    ( isset($_GET['checkemail']) && 'confirm' == $_GET['checkemail'] )
            wdbj_tml_set_error('confirm', __('Check your e-mail for the confirmation link.', 'theme-my-login'), 'message');
        elseif    ( isset($_GET['checkemail']) && 'newpass' == $_GET['checkemail'] )
            wdbj_tml_set_error('newpass', __('Check your e-mail for your new password.', 'theme-my-login'), 'message');
        elseif    ( isset($_GET['checkemail']) && 'registered' == $_GET['checkemail'] )
            wdbj_tml_set_error('registered', __('Registration complete. Please check your e-mail.', 'theme-my-login'), 'message');
    }

    wdbj_tml_get_header();

    if ( isset($_POST['log']) )
        $user_login = ( wdbj_tml_get_error('incorrect_password') || wdbj_tml_get_error('empty_password') ) ? esc_attr(stripslashes($_POST['log'])) : '';

    $user_login = ( $current_instance['is_active'] && isset($user_login) ) ? $user_login : '';

    if ( ( ! ( isset($_GET['checkemail']) && $current_instance['is_active'] ) ) ||
		( ! ( in_array($_GET['checkemail'], array('confirm', 'newpass') ) && $current_instance['is_active'] ) ) ) {
        ?>
        <form name="loginform" id="loginform-<?php echo $current_instance['instance_id']; ?>" action="<?php echo esc_url(wdbj_tml_get_current_url('action=login&instance=' . $current_instance['instance_id'])); ?>" method="post">
            <p>
                <label for="log-<?php echo $current_instance['instance_id']; ?>"><?php _e('Username', 'theme-my-login') ?></label>
                <input type="text" name="log" id="log-<?php echo $current_instance['instance_id']; ?>" class="input" value="<?php echo isset($user_login) ? $user_login : ''; ?>" size="20" />
            </p>
            <p>
                <label for="pwd-<?php echo $current_instance['instance_id']; ?>"><?php _e('Password', 'theme-my-login') ?></label>
                <input type="password" name="pwd" id="pwd-<?php echo $current_instance['instance_id']; ?>" class="input" value="" size="20" />
            </p>
        <?php do_action('login_form', $current_instance['instance_id']); ?>
            <p class="forgetmenot"><input name="rememberme" type="checkbox" id="rememberme-<?php echo $current_instance['instance_id']; ?>" value="forever" /> <label for="rememberme-<?php echo $current_instance['instance_id']; ?>"><?php _e('Remember Me', 'theme-my-login'); ?></label></p>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit-<?php echo $current_instance['instance_id']; ?>" value="<?php _e('Log In', 'theme-my-login'); ?>" />
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr(wdbj_tml_get_var('redirect_to')); ?>" />
                <input type="hidden" name="testcookie" value="1" />
            </p>
        </form>
        <?php
    }
    if ( $current_instance['is_active'] && isset($_GET['checkemail']) && in_array( $_GET['checkemail'], array('confirm', 'newpass') ) )
        $login_link = true;
    else
        $login_link = false;
    wdbj_tml_get_footer($login_link, true, true);
}

function wdbj_tml_get_register_form() {
    $current_instance = wdbj_tml_get_var('current_instance');
    
    $user_login = isset($_POST['user_login']) ? $_POST['user_login'] : '';
    $user_email = isset($_POST['user_email']) ? $_POST['user_email'] : '';

    $message = apply_filters('register_message', __('A password will be e-mailed to you.', 'theme-my-login'));

    wdbj_tml_get_header($message);
    ?>
    <form name="registerform" id="registerform-<?php echo $current_instance['instance_id']; ?>" action="<?php echo esc_url(wdbj_tml_get_current_url('action=register&instance=' . $current_instance['instance_id'])); ?>" method="post">
        <p>
            <label for="user_login-<?php echo $current_instance['instance_id']; ?>"><?php _e('Username', 'theme-my-login') ?></label>
            <input type="text" name="user_login" id="user_login-<?php echo $current_instance['instance_id']; ?>" class="input" value="<?php echo esc_attr(stripslashes($user_login)); ?>" size="20" />
        </p>
        <p>
            <label for="user_email-<?php echo $current_instance['instance_id']; ?>"><?php _e('E-mail', 'theme-my-login') ?></label>
            <input type="text" name="user_email" id="user_email-<?php echo $current_instance['instance_id']; ?>" class="input" value="<?php echo esc_attr(stripslashes($user_email)); ?>" size="20" />
        </p>
        <?php do_action('register_form', $current_instance['instance_id']); ?>
        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit-<?php echo $current_instance['instance_id']; ?>" value="<?php _e('Register', 'theme-my-login'); ?>" />
        </p>
    </form>
    <?php
    wdbj_tml_get_footer(true, false, true);
}

function wdbj_tml_get_lost_password_form() {
    $current_instance = wdbj_tml_get_var('current_instance');
    
    do_action('lost_password', $current_instance['instance_id']);
    
    $message = apply_filters('lostpassword_message', __('Please enter your username or e-mail address. You will receive a new password via e-mail.', 'theme-my-login'));
    
    wdbj_tml_get_header($message);
    
    $user_login = isset($_POST['user_login']) ? stripslashes($_POST['user_login']) : '';
    ?>
    <form name="lostpasswordform" id="lostpasswordform-<?php echo $current_instance['instance_id']; ?>" action="<?php echo esc_url(wdbj_tml_get_current_url('action=lostpassword&instance=' . $current_instance['instance_id'])); ?>" method="post">
        <p>
            <label for="user_login-<?php echo $current_instance['instance_id']; ?>"><?php _e('Username or E-mail:', 'theme-my-login') ?></label>
            <input type="text" name="user_login" id="user_login-<?php echo $current_instance['instance_id']; ?>" class="input" value="<?php echo esc_attr($user_login); ?>" size="20" />
        </p>
        <?php do_action('lostpassword_form', $current_instance['instance_id']); ?>
        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit-<?php echo $current_instance['instance_id']; ?>" value="<?php _e('Get New Password', 'theme-my-login'); ?>" />
        </p>
    </form>
    <?php
    wdbj_tml_get_footer(true, true, false);
}

?>
