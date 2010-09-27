<?php

function wdbj_tml_custom_user_links_admin_menu() {
	global $wp_roles;
	$parent = plugin_basename(TML_MODULE_DIR . '/custom-user-links/admin/options.php');
	wdbj_tml_add_menu_page(__('User Links', 'theme-my-login'), $parent);
	foreach ( $wp_roles->get_names() as $role => $label ) {
		if ( 'pending' == $role )
			continue;
		wdbj_tml_add_submenu_page($parent, translate_user_role($label), '', 'wdbj_tml_custom_user_links_user_role_admin_page', array('role' => $role));
	}
}

function wdbj_tml_custom_user_links_save_settings($settings) {
	if ( defined('DOING_AJAX') && DOING_AJAX )
		return $settings;
	if ( isset($_POST['user_links']) && is_array($_POST['user_links']) && !empty($_POST['user_links']) ) {
		foreach ( $_POST['user_links'] as $role => $links ) {
			foreach ( $links as $key => $link_data ) {
				$clean_title = wp_kses($link_data['title'], null);
				$clean_url = wp_kses($link_data['url'], null);
				$links[$key] = array('title' => $clean_title, 'url' => $clean_url);
				if ( ( empty($clean_title) && empty($clean_url) ) || ( isset($_POST['delete_user_link'][$role][$key]) && $_POST['delete_user_link'][$role][$key] ) )
					unset($links[$key]);
			}
			$settings['user_links'][$role] = array_values($links);
		}
		unset($role, $links, $key, $link_data, $clean_title, $clean_url);
	}
	if ( isset($_POST['new_user_link']) && is_array($_POST['new_user_link']) && !empty($_POST['new_user_link']) ) {
		foreach ( $_POST['new_user_link'] as $role => $link_data ) {
			$clean_title = wp_kses($link_data['title'], null);
			$clean_url = wp_kses($link_data['url'], null);
			if ( !empty($clean_title) && !empty($clean_url) )
				$settings['user_links'][$role][] = array('title' => $clean_title, 'url' => $clean_url);
		}
		unset($role, $link_data, $clean_title, $clean_url);
	}
	// Reset link keys
	foreach ( $settings['user_links'] as $role => $links ) {
		$settings['user_links'][$role] = array_values($links);
	}
	return $settings;
}

function wdbj_tml_custom_user_links_admin_styles() {
	wp_enqueue_style('theme-my-login-custom-user-links-admin', plugins_url('theme-my-login/modules/custom-user-links/admin/admin.css'));
	wp_enqueue_script('jquery');
	wp_enqueue_script('wp-lists');
	add_action('admin_print_footer_scripts', 'wdbj_tml_custom_user_links_admin_scripts', 20);
}

function wdbj_tml_custom_user_links_admin_scripts() {
	global $wp_roles;
	
	echo '<script type="text/javascript">' . "\n";
	echo 'jQuery(document).ready(function($) {' . "\n";
	foreach ( $wp_roles->get_names() as $role => $label ) {
		?>
	$('#<?php echo $role; ?>-link-list').wpList( {
		addAfter: function( xml, s ) {
			$('table#<?php echo $role; ?>-link-table').show();
		},
		addBefore: function( s ) {
			s.data += '&user_role=<?php echo $role; ?>';
			return s;
		},
		delBefore: function( s ) {
			s.data.user_role = '<?php echo $role; ?>';
			return s;
		},
		delAfter: function( r, s ) {
			$('#' + s.element).remove();
		}
	} );
<?php
	}
	echo '});' . "\n";
	echo '</script>' . "\n";
}

function wdbj_tml_custom_user_links_user_role_admin_page($role) {
	$links = wdbj_tml_get_option('user_links', $role);
	if ( empty($links) )
		$links = array();
	?>
<div id="<?php echo $role; ?>-user-links" class="user-links">
<div id="ajax-response-<?php echo $role; ?>" class="ajax-response"></div>
<?php
wdbj_tml_custom_user_links_list_links($links, $role);
wdbj_tml_custom_user_links_link_form($role); ?>
</div>	
<?php
}

function wdbj_tml_custom_user_links_list_links($links, $role) {
	// Exit if no links
	if ( ! $links ) {
		echo '
<table id="' . $role . '-link-table" style="display: none;">
	<thead>
	<tr>
		<th class="left">' . __( 'Title', 'theme-my-login' ) . '</th>
		<th>' . __( 'URL', 'theme-my-login' ) . '</th>
		<th></th>
	</tr>
	</thead>
	<tbody id="' . $role . '-link-list" class="list:' . $role . '-link">
	<tr><td></td></tr>
	</tbody>
</table>'; //TBODY needed for list-manipulation JS
		return;
	}
	$count = 0;
?>
<table id="<?php echo $role; ?>-link-table">
	<thead>
	<tr>
		<th class="left"><?php _e( 'Title', 'theme-my-login' ) ?></th>
		<th><?php _e( 'URL', 'theme-my-login' ) ?></th>
		<th></th>
	</tr>
	</thead>
	<tbody id='<?php echo $role; ?>-link-list' class='list:<?php echo $role; ?>-link'>
<?php
	foreach ( $links as $key => $link ) {
		$link['id'] = $key + 1; // Artificially inflate as not to use 0 as a key
		echo _wdbj_tml_custom_user_links_link_row( $link, $role, $count );
	}
?>
	</tbody>
</table>
<?php
}

function _wdbj_tml_custom_user_links_link_row( $link, $role, &$count ) {
	$r = '';
	++ $count;
	if ( $count % 2 )
		$style = 'alternate';
	else
		$style = '';
		
	$link = (object) $link;

	$delete_nonce = wp_create_nonce( 'delete-' . $role . '-link_' . $link->id );
	$update_nonce = wp_create_nonce( 'add-' . $role . '-link' );

	$r .= "\n\t<tr id='$role-link-$link->id' class='$style'>";
	$r .= "\n\t\t<td class='left'><label class='screen-reader-text' for='user_links[$role][$link->id][title]'>" . __( 'Title', 'theme-my-login' ) . "</label><input name='user_links[$role][$link->id][title]' id='user_links[$role][$link->id][title]' tabindex='6' type='text' size='20' value='$link->title' />";
	$r .= wp_nonce_field( 'change-' . $role . '-link', '_ajax_nonce', false, false );
	$r .= "</td>";

	$r .= "\n\t\t<td class='center'><label class='screen-reader-text' for='user_links[$role][$link->id][url]'>" . __( 'URL', 'theme-my-login' ) . "</label><input name='user_links[$role][$link->id][url]' id='user_links[$role][$link->id][url]' tabindex='6' type='text' size='20' value='$link->url' /></td>";
	
	$r .= "\n\t\t<td class='submit'><input name='delete_user_link[$role][$link->id]' type='submit' class='delete:$role-link-list:$role-link-$link->id::_ajax_nonce=$delete_nonce deletelink' tabindex='6' value='". esc_attr__( 'Delete' ) ."' />";
	$r .= "\n\t\t<input name='updatelink' type='submit' class='add:$role-link-list:$role-link-$link->id::_ajax_nonce=$update_nonce updatelink' tabindex='6' value='". esc_attr__( 'Update' ) ."' /></td>\n\t</tr>";
	return $r;
}

function wdbj_tml_custom_user_links_link_form($role) {
?>
<p><strong><?php _e( 'Add New link:' , 'theme-my-login') ?></strong></p>
<table id="new-<?php echo $role; ?>-link">
<thead>
<tr>
<th class="left"><label for="new_user_link[<?php echo $role; ?>][title]"><?php _e( 'Title', 'theme-my-login' ) ?></label></th>
<th><label for="new_user_link[<?php echo $role; ?>][url]"><?php _e( 'URL', 'theme-my-login' ) ?></label></th>
<th></th>
</tr>
</thead>

<tbody>
<tr>
<td class="left"><input id="new_user_link[<?php echo $role; ?>][title]" name="new_user_link[<?php echo $role; ?>][title]" type="text" tabindex="8" size="20" /></td>
<td class="center"><input id="new_user_link[<?php echo $role; ?>][url]" name="new_user_link[<?php echo $role; ?>][url]" type="text" tabindex="8" size="20" /></td>

<td class="submit">
<input type="submit" id="add_new_user_link_<?php echo $role; ?>" name="add_new_user_link[<?php echo $role; ?>]" class="add:<?php echo $role; ?>-link-list:new-<?php echo $role; ?>-link" tabindex="9" value="<?php esc_attr_e( 'Add link', 'theme-my-login' ) ?>" />
<?php wp_nonce_field( 'add-' . $role . '-link', '_ajax_nonce', false ); ?>
</td></tr>
</tbody>
</table>
<?php
}

function wdbj_tml_custom_user_links_add_user_link_ajax() {
	
	$user_role = isset($_POST['user_role']) ? $_POST['user_role'] : '';
	
	check_ajax_referer( 'add-' . $user_role . '-link' );
	
	$c = 0;
	if ( isset($_POST['new_user_link'][$user_role]['title']) || isset($_POST['new_user_link'][$user_role]['url']) ) {
		if ( !current_user_can( 'manage_options' ) )
			die('-1');
			
		$clean_title = wp_kses($_POST['new_user_link'][$user_role]['title'], null);
		$clean_url = wp_kses($_POST['new_user_link'][$user_role]['url'], null);
		
		if ( empty($clean_title) || empty($clean_url) )
			die('1');

		// Get current links
		$links = wdbj_tml_get_option('user_links', $user_role);
		// Add new link
		$links[] = array('title' => $clean_title, 'url' => $clean_url);
		// Update links
		wdbj_tml_update_option($links, 'user_links', $user_role);
		// Save links
		wdbj_tml_save_options();
		
		$link_row = array_merge( array('id' => max(array_keys($links)) + 1), array_pop($links) );

		$x = new WP_Ajax_Response( array(
			'what' => 'tml-user-link',
			'id' => $link_row['id'],
			'data' => _wdbj_tml_custom_user_links_link_row( $link_row, $user_role, $c ),
			'position' => 1,
			'supplemental' => array('user_role' => $user_role)
		) );
	} else {
		$user_links = array_pop($_POST['user_links']);
		$id = (int) key($user_links);
		$clean_title = wp_kses($user_links[$id]['title'], null);
		$clean_url = wp_kses($user_links[$id]['url'], null);
		--$id; // Fix id offset
		if ( !$link = wdbj_tml_get_option( 'user_links', $user_role, $id ) )
			die('0'); // if link doesn't exist
		if ( !current_user_can( 'manage_options' ) )
			die('-1');
		if ( $link['title'] != $clean_title || $link['url'] != $clean_url ) {
			$link_row = array('title' => $clean_title, 'url' => $clean_url);
			if ( !$u = wdbj_tml_update_option( $link_row, 'user_links', $user_role, $id ) )
				die('0'); // We know link exists; we also know it's unchanged (or DB error, in which case there are bigger problems).
			wdbj_tml_save_options();
		}
		
		++$id;
		$link_row['id'] = $id;

		$x = new WP_Ajax_Response( array(
			'what' => $user_role . '-link',
			'id' => $id, 'old_id' => $id,
			'data' => _wdbj_tml_custom_user_links_link_row( $link_row, $user_role, $c ),
			'position' => 0,
			'supplemental' => array('user_role' => $user_role)
		) );
	}
	$x->send();
}

function wdbj_tml_custom_user_links_delete_user_link_ajax() {
	global $id;
	
	$user_role = isset($_POST['user_role']) ? $_POST['user_role'] : '';
	
	check_ajax_referer( "delete-$user_role-link_$id" );
	
	--$id; // Fix id offset
	
	// Get current links
	if ( $links = wdbj_tml_get_option('user_links', $user_role) ) {
		if ( isset($links[$id]) ) {
			// Delete link
			unset($links[$id]);
			// Update links
			wdbj_tml_update_option($links, 'user_links', $user_role);
			// Save links
			wdbj_tml_save_options();
		}
		die('1');
	}
	die('0');
}

?>
