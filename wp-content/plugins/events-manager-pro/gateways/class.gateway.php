<?php

if(!class_exists('EM_Gateway')) {

	class EM_Gateway {
		
		// Class Identification
		var $gateway = 'Not Set';
		var $title = 'Not Set';

		// Tables
		var $transactions_table;

		function EM_Gateway() {
			global $wpdb;
			$this->db =& $wpdb;
			$this->transactions_table = EM_TRANSACTIONS_TABLE;
			// Actions and Filters
			add_filter('EM_gateways_list', array(&$this, 'gateways_list'));
			add_filter('EM_active_gateways', array(&$this, 'active_gateways'));
			add_filter('em_booking_form_js', array(&$this,'booking_form_js'),1,2); //JS Replacement, so we can handle the ajax return differently
			//Add options and tables to EM admin pages
			if( current_user_can('manage_others_bookings') ){
				add_action('em_bookings_dashboard', array(&$this, 'transactions'),10,1);
				add_action('em_bookings_ticket_footer', array(&$this, 'transactions'),10,1);
				add_action('em_bookings_single_footer', array(&$this, 'transactions'),10,1);
				add_action('em_bookings_person_footer', array(&$this, 'transactions'),10,1);
				add_action('em_bookings_event_footer', array(&$this, 'transactions'),10,1);
				//add_action('em_options_page_footer', array(&$this, 'settings'));
			}
		}
		
		function init(){			
			//Menus
			add_action('em_create_events_submenu',array('EM_Gateway', 'admin_menu'),1,1);
			add_action('admin_init', array('EM_Gateway', 'handle_payment_gateways'),1,1);
			add_action('admin_init', array('EM_Gateway', 'handle_gateways_panel_updates'),1,1);
			//Booking interception
			add_filter('em_booking_add', array('EM_Gateway', 'em_booking_add'), 1, 2);
			// Payment return
			add_action('pre_get_posts', array('EM_Gateway', 'handle_payment_gateways'), 1 );
			add_filter('em_booking_form_buttons', array('EM_Gateway','booking_form_buttons'),1,2); //Replace button with PP image
		}
		
		function em_booking_add($EM_Event,$EM_Booking){
			global $EM_Gateways;
			if( array_key_exists( $_REQUEST['gateway'], get_option('em_payment_gateways',array()))){
				do_action('em_booking_add_'.$_REQUEST['gateway'], $EM_Event, $EM_Booking);
			}
		}
	
		/**
		 * This gets called when a booking form is added. 
		 * @param unknown_type $button
		 * @param EM_Event $EM_Event
		 * @return string
		 */
		function booking_form_buttons($button, $EM_Event){
			global $EM_Gateways;
			$gateway_buttons = array();
			if(!$EM_Event->is_free()){
				$active_gateways = get_option('em_payment_gateways');
				if( is_array($active_gateways) ){
					foreach($active_gateways as $gateway => $active_val){
						if(array_key_exists($gateway, $EM_Gateways)) {
							$gateway_button = $EM_Gateways[$gateway]->booking_form_button();
							if(!empty($gateway_button)){
								$gateway_buttons[$gateway] = $gateway_button;
							}
						}
					}
					if( count($gateway_buttons) > 0 ){
						$button = '<div class="em-gateway-buttons"><div class="em-gateway-button first">'. implode('</div><div class="em-gateway-button">', $gateway_buttons).'</div></div>';			
					}
				}
			}
			return apply_filters('em_gateway_booking_form_buttons', $button, $gateway_buttons);
		}
		
		/**
		 * Override this
		 * @param string $button
		 * @return string
		 */
		function booking_form_button($button){
			return $button;
		}

		function handle_payment_gateways($wp_query) {
			if( !empty($wp_query->query_vars['payment_gateway'])) {
				do_action( 'em_handle_payment_return_' . $wp_query->query_vars['payment_gateway']);
				exit();
			}
		}
		
		function admin_menu($plugin_pages){
			$plugin_pages[] = add_submenu_page('events-manager', __('Payment Gateways'),__('Payment Gateways'),'activate_plugins','events-manager-gateways',array('EM_Gateway','handle_gateways_panel'));
			return $plugin_pages;
		}

		function gateways_list($gateways) {
			$gateways[$this->gateway] = $this->title;
			return $gateways;
		}
		
		function active_gateways($gateways) {
			if($this->is_active()){
				$gateways[$this->gateway] = $this->title;
			}
			return $gateways;
		}

		function toggleactivation() {
			global $EM_Pro;
			$active = get_option('em_payment_gateways');

			if(array_key_exists($this->gateway, $active)) {
				unset($active[$this->gateway]);
				update_option('em_payment_gateways',$active);
				return true;
			} else {
				$active[$this->gateway] = true;
				update_option('em_payment_gateways',$active);
				return true;
			}
		}

		function activate() {
			global $EM_Pro;
			$active = get_option('em_payment_gateways', array());
			if(array_key_exists($this->gateway, $active)) {
				return true;
			} else {
				$active[$this->gateway] = true;
				update_option('em_payment_gateways', $active);
				return true;
			}
		}

		function deactivate() {
			global $EM_Pro;
			$active = get_option('em_payment_gateways');
			if(array_key_exists($this->gateway, $active)) {
				unset($active[$this->gateway]);
				update_option('em_payment_gateways', $active);
				return true;
			} else {
				return true;
			}
		}

		function is_active() {
			global $EM_Pro;
			$active = get_option('em_payment_gateways', array());
			if( array_key_exists($this->gateway, $active)) {
				return true;
			} else {
				return false;
			}
		}

		function settings() {
			global $page, $action;
			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php echo sprintf(__('Edit &quot;%s&quot; settings','em-pro'), esc_html($this->title) ); ?></h2>
				<form action='?page=<?php echo $page; ?>' method='post' name='gatewaysettingsform'>
					<input type='hidden' name='action' id='action' value='updated' />
					<input type='hidden' name='gateway' id='gateway' value='<?php echo $this->gateway; ?>' />
					<?php
					wp_nonce_field('updated-' . $this->gateway);
					do_action('EM_gateways_settings_' . $this->gateway);
					?>
					<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
					</p>
				</form>
			</div> <!-- wrap -->
			<?php
		}

		function update() {
			// default action is to return true
			return true;
		}

		function get_transactions($type, $startat, $num, $context=false) {
			global $wpdb;
			$join = '';
			$conditions = array();
			$table = EM_BOOKINGS_TABLE;
			//we can determine what to search for, based on if certain variables are set.
			if( is_object($context) && get_class($context)=="EM_Booking" && $context->can_manage('manage_bookings','manage_others_bookings') ){
				$conditions[] = "booking_id = ".$context->id;
			}elseif( is_object($context) && get_class($context)=="EM_Event" && $context->can_manage('manage_bookings','manage_others_bookings') ){
				$join = "tx JOIN $table ON $table.booking_id=tx.booking_id";	
				$conditions[] = "event_id = ".$context->id;		
			}elseif( is_object($context) && get_class($context)=="EM_Person" ){
				//FIXME peole could potentially view other's txns like this
				$join = "tx JOIN $table ON $table.booking_id=tx.booking_id";
				$conditions[] = "person_id = ".$context->ID;			
			}elseif( is_object($context) && get_class($context)=="EM_Ticket" && $context->can_manage('manage_bookings','manage_others_bookings') ){
				$booking_ids = array();
				foreach($context->get_bookings()->bookings as $EM_Booking){
					$booking_ids[] = $EM_Booking->id;
				}
				if( count($booking_ids) > 0 ){
					$conditions[] = "booking_id IN (".implode(',', $booking_ids).")";
				}else{
					return new stdClass();
				}			
			}
			if( is_multisite() && !is_main_blog() ){ //if not main blog, we show only blog specific booking info
				global $blog_id;
				$join = "tx JOIN $table ON $table.booking_id=tx.booking_id";
				$conditions[] = "booking_id IN (SELECT booking_id FROM $table, ".EM_EVENTS_TABLE." e WHERE e.blog_id=".$blog_id.")";
			}
			$scope_conditions = array(
				'past' => "transaction_status NOT IN ('Pending', 'Future')",
				'pending' => "transaction_status IN ('Pending')",
				'future' => "transaction_status IN ('Future')"
			);
			foreach( $scope_conditions as $count_type => $condition){
				$count_conditions = $conditions;
				$count_conditions[] = $condition;
				$this->transaction_count[$count_type] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->transactions_table} $join WHERE ".implode(' AND ', $count_conditions)." AND transaction_gateway = %s", $this->gateway)) ; 
			}
			$conditions[] = $scope_conditions[$type];
			$sql = $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->transactions_table} $join WHERE ".implode(' AND ', $conditions)." AND transaction_gateway = %s ORDER BY transaction_id DESC  LIMIT %d, %d", $this->gateway, $startat, $num );
			return $wpdb->get_results( $sql );
		}

		function record_transaction($EM_Booking, $amount, $currency, $timestamp, $paypal_id, $status, $note) {
			global $wpdb;
			$data = array();
			$data['booking_id'] = $EM_Booking->id;
			$data['transaction_gateway_id'] = $paypal_id;
			$data['transaction_timestamp'] = $timestamp;
			$data['transaction_currency'] = $currency;
			$data['transaction_status'] = $status;
			$data['transaction_total_amount'] = $amount;
			$data['transaction_note'] = $note;
			$data['transaction_gateway'] = $this->gateway;

			if( !empty($paypal_id) ){
				$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT transaction_id FROM {$this->transactions_table} WHERE transaction_gateway_id = %s", $paypal_id ) );
			}

			if( !empty($existing_id) ) {
				// Update
				$wpdb->update( $this->transactions_table, $data, array('transaction_id' => $existing_id) );
			} else {
				// Insert
				$wpdb->insert( $this->transactions_table, $data );
			}
		}

		function get_total() {
			global $wpdb;
			return $wpdb->get_var( "SELECT FOUND_ROWS();" );
		}

		function transactions( $context = false ) {
			global $page, $action, $type, $wp_query;
			if( function_exists('wp_reset_vars') ){ wp_reset_vars( array('type') ); }
			if(empty($type)) $type = 'past';
			ob_start();
			if(has_action('em_gateway_transactions_' . $this->gateway)) {
				do_action('em_gateway_transactions_' . $this->gateway, $type);
			} else {
				$this->mytransactions($type, $context);
			}
			$transactions = ob_get_clean(); //so we have event counts and avoid doing it twice.
			?>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php echo sprintf(__('%s Transactions','dbem'), esc_html($this->title)); ?></h2>
			<?php
			echo $transactions;
		}

		function mytransactions($type = 'past', $context=false) {

			if(empty($_GET['paged'])) {
				$paged = 1;
			} else {
				$paged = ((int) $_GET['paged']);
			}

			$startat = ($paged - 1) * 20;

			$transactions = $this->get_transactions($type, $startat, 20, $context);
			$total = $this->get_total();

			$columns = array();

			$columns['event'] = __('Event','em-pro');
			$columns['user'] = __('User','em-pro');
			$columns['date'] = __('Date','em-pro');
			$columns['amount'] = __('Amount','em-pro');
			$columns['transid'] = __('Transaction id','em-pro');
			$columns['status'] = __('Status','em-pro');
			$columns['note'] = __('Notes','em-pro');

			$trans_navigation = paginate_links( array(
				'base' => add_query_arg( 'paged', '%#%' ),
				'format' => '',
				'total' => ceil($total / 20),
				'current' => $paged
			));
			?>
				<div class="tablenav">
					<ul class="subsubsub">
						<li><a href="<?php echo add_query_arg('type', 'past'); ?>" class="rbutton <?php if($type == 'past') echo 'current'; ?>"><?php echo  __('Recent transactions', 'em-pro') . " ({$this->transaction_count['past']})"; ?></a> | </li>
						<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php echo  __('Pending transactions', 'em-pro') . " ({$this->transaction_count['pending']})"; ?></a></li>
						<?php if( $this->transaction_count['future'] > 0 ): ?>
						<li> | <a href="<?php echo add_query_arg('type', 'future'); ?>" class="rbutton <?php if($type == 'future') echo 'current'; ?>"><?php echo  __('Future transactions', 'em-pro') . " ({$this->transaction_count['future']})"; ?></a></li>
						<?php endif; ?>
					</ul>
					<?php if ( $trans_navigation ) : ?>
					<div class='tablenav-pages'><?php echo $trans_navigation ?></div>	
					<?php endif; ?>
				</div>


				<table cellspacing="0" class="widefat">
					<thead>
					<tr>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
						<?php
							reset($columns);
							foreach($columns as $key => $col) {
								?>
								<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
								<?php
							}
						?>
					</tr>
					</tfoot>

					<tbody>
						<?php
							echo $this->print_transactions($transactions);
						?>

					</tbody>
				</table>
			<?php
		}
		
		function print_transactions($transactions, $columns=7){
			ob_start();
			if($transactions) {
				foreach($transactions as $key => $transaction) {
					?>
					<tr valign="middle" class="alternate">
						<td>
							<?php
								$EM_Booking = new EM_Booking($transaction->booking_id);
								echo '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager-bookings&amp;event_id='.$EM_Booking->get_event()->id.'">'.$EM_Booking->get_event()->name.'</a>';
							?>
						</td>
						<td>
							<?php
								echo '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager-bookings&amp;person_id='.$EM_Booking->get_person()->ID.'">'.$EM_Booking->get_person()->get_name().'</a>';
							?>
						</td>
						<td class="column-date">
							<?php
								echo mysql2date("d-m-Y", $transaction->transaction_timestamp);
							?>
						</td>
						<td class="column-amount">
							<?php
								$amount = $transaction->transaction_total_amount;
								echo $transaction->transaction_currency;
								echo "&nbsp;" . number_format($amount, 2, '.', ',');
							?>
						</td>
						<td class="column-transid">
							<?php
								if(!empty($transaction->transaction_gateway_id)) {
									echo $transaction->transaction_gateway_id;
								} else {
									echo __('None yet','em-pro');
								}
							?>
						</td>
						<td class="column-transid">
							<?php
								if(!empty($transaction->transaction_status)) {
									echo $transaction->transaction_status;
								} else {
									echo __('None yet','em-pro');
								}
							?>
						</td>
						<td class="column-transid">
							<?php
								if(!empty($transaction->transaction_note)) {
									echo esc_html($transaction->transaction_note);
								} else {
									echo __('None','em-pro');
								}
							?>
						</td>
				    </tr>
					<?php
				}
			} else {
				$columncount = count($columns);
				?>
				<tr valign="middle" class="alternate" >
					<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Transactions','em-pro'); ?></td>
			    </tr>
				<?php
			}
			return ob_get_clean();
		}

		function handle_gateways_panel() {
			global $action, $page, $EM_Gateways, $EM_Pro;
			wp_reset_vars( array('action', 'page') );
			switch(addslashes($action)) {
				case 'edit':	
					if(isset($EM_Gateways[addslashes($_GET['gateway'])])) {
						$EM_Gateways[addslashes($_GET['gateway'])]->settings();
					}
					return; // so we don't show the list below
					break;
				case 'transactions':
					if(isset($EM_Gateways[addslashes($_GET['gateway'])])) {
						$EM_Gateways[addslashes($_GET['gateway'])]->transactions();
					}
					return; // so we don't show the list below
					break;
			}
			$messages = array();
			$messages[1] = __('Gateway updated.');
			$messages[2] = __('Gateway not updated.');
			$messages[3] = __('Gateway activated.');
			$messages[4] = __('Gateway not activated.');
			$messages[5] = __('Gateway deactivated.');
			$messages[6] = __('Gateway not deactivated.');
			$messages[7] = __('Gateway activation toggled.');
			?>
			<div class='wrap'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php _e('Edit Gateways','em-pro'); ?></h2>
				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">
					<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />
					<div class="tablenav">
						<div class="alignleft actions">
							<select name="action">
								<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
								<option value="toggle"><?php _e('Toggle activation'); ?></option>
							</select>
							<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">		
						</div>		
						<div class="alignright actions"></div>		
						<br class="clear">
					</div>	
					<div class="clear"></div>	
					<?php
						wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-gateways');	
						$columns = array(	
							"name" => __('Gateway Name','em-pro'),
							"active" =>	__('Active','em-pro'),
							"transactions" => __('Transactions','em-pro')
						);
						$columns = apply_filters('EM_gateways_columns', $columns);	
						$gateways = apply_filters('EM_gateways_list', array());	
						$active = get_option('em_payment_gateways', array());
					?>	
					<table cellspacing="0" class="widefat fixed">
						<thead>
						<tr>
						<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<?php
							foreach($columns as $key => $col) {
								?>
								<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
								<?php
							}
							?>
						</tr>
						</thead>	
						<tfoot>
						<tr>
						<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
							<?php
							reset($columns);
							foreach($columns as $key => $col) {
								?>
								<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
								<?php
							}
							?>
						</tr>
						</tfoot>
						<tbody>
							<?php
							if($gateways) {
								foreach($gateways as $key => $gateway) {
									if(!isset($EM_Gateways[$key])) {
										continue;
									}
									?>
									<tr valign="middle" class="alternate">
										<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($key); ?>" name="gatewaycheck[]"></th>
										<td class="column-name">
											<strong><a title="Edit <?php echo esc_attr($gateway); ?>" href="?page=<?php echo $page; ?>&amp;action=edit&amp;gateway=<?php echo $key; ?>" class="row-title"><?php echo esc_html($gateway); ?></a></strong>
											<?php
												$actions = array();
												$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;gateway=" . $key . "'>" . __('Settings') . "</a></span>";
	
												if(array_key_exists($key, $active)) {
													$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=deactivate&amp;gateway=" . $key . "", 'toggle-gateway_' . $key) . "'>" . __('Deactivate') . "</a></span>";
												} else {
													$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=activate&amp;gateway=" . $key . "", 'toggle-gateway_' . $key) . "'>" . __('Activate') . "</a></span>";
												}
											?>
											<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
											</td>
										<td class="column-active">
											<?php
												if(array_key_exists($key, $active)) {
													echo "<strong>" . __('Active', 'em-pro') . "</strong>";
												} else {
													echo __('Inactive', 'em-pro');
												}
											?>
										</td>
										<td class="column-transactions">
											<a href='?page=<?php echo $page; ?>&amp;action=transactions&amp;gateway=<?php echo $key; ?>'><?php _e('View transactions','em-pro'); ?></a>
										</td>
								    </tr>
									<?php
								}
							} else {
								$columncount = count($columns) + 1;
								?>
								<tr valign="middle" class="alternate" >
									<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Payment gateways where found for this install.','em-pro'); ?></td>
							    </tr>
								<?php
							}
							?>
						</tbody>
					</table>	
					<div class="tablenav">	
						<div class="alignleft actions">
							<select name="action2">
								<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
								<option value="toggle"><?php _e('Toggle activation'); ?></option>
							</select>
							<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
						</div>
						<div class="alignright actions"></div>
						<br class="clear">
					</div>
				</form>
	
			</div> <!-- wrap -->
			<?php
		}
				
		function handle_gateways_panel_updates() {	
			global $action, $page, $EM_Gateways;	
			wp_reset_vars ( array ('action', 'page' ) );
			$request = $_REQUEST;
			if (isset ( $_REQUEST ['doaction'] ) || isset ( $_REQUEST ['doaction2'] )) {
				if ( (!empty($_GET ['action']) && addslashes ( $_GET ['action'] ) == 'toggle') || (!empty( $_GET ['action2']) && addslashes ( $_GET ['action2'] ) == 'toggle') ) {
					$action = 'bulk-toggle';
				}
			}	
			if( !empty($_REQUEST['gateway']) || !empty($_REQUEST['bulk-gateways']) ){
				switch (addslashes ( $action )) {		
					case 'deactivate' :
						$key = addslashes ( $_REQUEST ['gateway'] );
						if (isset ( $EM_Gateways [$key] )) {
							if ($EM_Gateways [$key]->deactivate ()) {
								wp_safe_redirect ( add_query_arg ( 'msg', 5, wp_get_referer () ) );
							} else {
								wp_safe_redirect ( add_query_arg ( 'msg', 6, wp_get_referer () ) );
							}
						}
						break;		
					case 'activate' :
						$key = addslashes ( $_REQUEST ['gateway'] );
						if (isset ( $EM_Gateways[$key] )) {
							if ($EM_Gateways[$key]->activate ()) {
								wp_safe_redirect ( add_query_arg ( 'msg', 3, wp_get_referer () ) );
							} else {
								wp_safe_redirect ( add_query_arg ( 'msg', 4, wp_get_referer () ) );
							}
						}
						break;		
					case 'bulk-toggle' :
						check_admin_referer ( 'bulk-gateways' );
						foreach ( $_REQUEST ['gatewaycheck'] as $key ) {
							if (isset ( $EM_Gateways [$key] )) {					
								$EM_Gateways [$key]->toggleactivation ();				
							}
						}
						wp_safe_redirect ( add_query_arg ( 'msg', 7, wp_get_referer () ) );
						break;		
					case 'updated' :
						$gateway = addslashes ( $_REQUEST ['gateway'] );		
						check_admin_referer ( 'updated-'.$EM_Gateways[$gateway]->gateway );
						if ($EM_Gateways[$gateway]->update ()) {
							wp_safe_redirect ( add_query_arg ( 'msg', 1, 'admin.php?page=' . $page ) );
						} else {
							wp_safe_redirect ( add_query_arg ( 'msg', 2, 'admin.php?page=' . $page ) );
						}			
						break;
				}
			}
		}
	}
}
EM_Gateway::init();
function emp_register_gateway($gateway, $class) {
	global $EM_Gateways;
	if(!is_array($EM_Gateways)) {
		$EM_Gateways = array();
	}
	$EM_Gateways[$gateway] = new $class;
}

?>