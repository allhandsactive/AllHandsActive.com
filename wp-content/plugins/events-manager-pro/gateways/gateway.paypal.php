<?php

class EM_Gateway_Paypal extends EM_Gateway {
	//change these properties below if creating a new gateway, not advised to change this for PayPal
	var $gateway = 'paypal'; 
	var $title = 'PayPal';

	function EM_Gateway_Paypal() {
		parent::EM_Gateway();
		add_action('EM_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
		// If I want to override the transactions output - then I can use this action
		//add_action('EM_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));
		if($this->is_active()) {	
			// Catch gateway return handle for ipn monitoring
			add_action('em_handle_payment_return_' . $this->gateway, array(&$this, 'handle_paypal_return'));
			//Booking form tweaks
			add_action('em_gateway_js', array(&$this,'em_gateway_js'),10); //JS Replacement, so we can handle the ajax return differently
			add_filter('em_gateway_form_buttons', array(&$this,'booking_form_button'),1,1); //Replace button with PP image
			if( !get_option('em_booking_form_custom')) {
				add_filter('em_booking_form_show_register_form',create_function('$true','return false;'),1,1); //Prevent register form from showing.
			}
			//AJAX interception
			add_action('em_booking_add_'.$this->gateway, array(&$this,'em_booking_add'),1,2); //modify booking status code for this gateway
			//say thank you, come again!
			add_action('em_template_my_bookings_header',array(&$this,'say_thanks'));
			add_filter('em_my_bookings_booked_message',array(&$this,'em_my_bookings_booked_message'),1,2);
			add_filter('em_my_bookings_booking_status',array(&$this,'em_my_bookings_booked_message'),1,2);
			//Modify spaces calculations
			add_filter('em_bookings_get_pending_spaces', array(&$this, 'em_bookings_get_pending_spaces'),1,2);
			//set up cron
			$timestamp = wp_next_scheduled('emp_cron_hook');
			if( absint(get_option('em_paypal_booking_timeout')) > 0 && !$timestamp ){
				$result = wp_schedule_event(time(),'em_minute','emp_cron_hook');
			}elseif( !$timestamp ){
				wp_unschedule_event($timestamp, 'emp_cron_hook');
			}
		}else{
			//unschedule the cron
			$timestamp = wp_next_scheduled('emp_cron_hook');
			wp_unschedule_event($timestamp, 'emp_cron_hook');			
		}
	}

	/**
	 * @param integer $count
	 * @param EM_Bookings $EM_Bookings
	 */
	function em_bookings_get_pending_spaces($count, $EM_Bookings){
		foreach($EM_Bookings->bookings as $EM_Booking){
			if($EM_Booking->status == 4){
				$count += $EM_Booking->get_spaces();
			}
		}
		return $count;
	}
	
	function em_my_bookings_booked_message( $message, $EM_Booking){
		if($EM_Booking->status == 4){
			//user owes money!
			$paypal_vars = $this->get_paypal_vars($EM_Booking);
			$form = '<form action="'.$this->get_paypal_url().'" method="post">';
			foreach($paypal_vars as $key=>$value){
				$form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
			$form .= '<input type="submit" value="'.__('Resume Payment','em-pro').'">';
			$form .= '</form>';
			$message .= " ". $form;
		}
		return $message;		
	}
	
	/**
	 * Retreive the paypal vars needed to send to the gatway to proceed with payment
	 * @param EM_Booking $EM_Booking
	 */
	function get_paypal_vars($EM_Booking){
		global $wp_rewrite, $EM_Notices;
		$notify_url = $this->get_notify_url();
		$paypal_vars = array(
			'business' => get_option('em_'. $this->gateway . "_email" ), 
			'cmd' => '_cart',
			'upload' => 1,
			'currency_code' => get_option('dbem_bookings_currency', 'USD'),
			'notify_url' =>$notify_url,
			'custom' => $EM_Booking->id.':'.$EM_Booking->event_id
		);
		if( !get_option('dbem_bookings_tax_auto_add') && is_numeric(get_option('dbem_bookings_tax')) && get_option('dbem_bookings_tax') > 0 ){
			//tax only added if auto_add is disabled, since it would be added to individual ticket prices
			$paypal_vars['tax_cart'] = $EM_Booking->get_price(false,false,false) * (get_option('dbem_bookings_tax')/100);;
		}
		if( get_option('em_'. $this->gateway . "_return" ) != "" ){
			$paypal_vars['return'] = get_option('em_'. $this->gateway . "_return" );
		}
		if( get_option('em_'. $this->gateway . "_format_logo" ) !== false ){
			$paypal_vars['cpp_logo_image'] = get_option('em_'. $this->gateway . "_format_logo" );
		}
		if( get_option('em_'. $this->gateway . "_border_color" ) !== false ){
			$paypal_vars['cpp_cart_border_color'] = get_option('em_'. $this->gateway . "_format_border" );
		}
		$count = 1;
		foreach( $EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking ){
			$price = $EM_Ticket_Booking->get_ticket()->get_price();
			if( $price > 0 ){
				$paypal_vars['item_name_'.$count] = $EM_Ticket_Booking->get_ticket()->name;
				$paypal_vars['quantity_'.$count] = $EM_Ticket_Booking->get_spaces();
				$paypal_vars['amount_'.$count] = $price;
				$count++;
			}
		}
		return $paypal_vars;
	}
	
	/**
	 * gets paypal gateway url (sandbox or live mode)
	 * @returns string 
	 */
	function get_paypal_url(){
		return ( get_option('em_'. $this->gateway . "_status" ) == 'test') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr':'https://www.paypal.com/cgi-bin/webscr';
	}
	
	function get_notify_url(){
		global $wp_rewrite;
		return ($wp_rewrite->using_permalinks()) ? trailingslashit(EM_URI).'payments/'.$this->gateway.'/':em_add_get_params(trailingslashit(EM_URI),array('payment_gateway'=>$this->gateway));
	}
	
	function get_return_url(){
		global $wp_rewrite;
		return ($wp_rewrite->using_permalinks()) ? trailingslashit(EM_URI).'my-bookings/?thanks=1':em_add_get_params(trailingslashit(EM_URI),array('thanks'=>1));
	}
	
	/**
	 * We catch this booking before it's saved, and handle the saving from here.
	 * @param EM_Event $EM_Event
	 * @param EM_Booking $EM_Booking
	 */
	function em_booking_add($EM_Event,$EM_Booking){
		global $wpdb, $wp_rewrite, $EM_Notices;
		if( !$EM_Event->is_free() ){
			$post_result = $EM_Booking->get_post();
			if($EM_Booking->get_price() > 0 || !$post_result){
				$paypal_vars = array();
				$paypal_url = '';
				$result = false;
				$no_redirect = false;
				if( $post_result ){
					$EM_Booking->status = 4; //status 4 = paypal
					if( !is_user_logged_in() ){
						//anonymous users allowed with this payment method, we'll create the user if booked.
						$EM_Booking->person_id = 0;
						$EM_Booking->person = new EM_Person(0);
					}	
					if( $EM_Event->get_bookings()->add($EM_Booking) ){
						//If the user isn't logged in, we modify the user too and save here.
						$paypal_url = $this->get_paypal_url();			
						if( is_object($EM_Booking) && get_class($EM_Booking) == 'EM_Booking' ){			
							$paypal_vars = $this->get_paypal_vars($EM_Booking);						
							$result = true;
							$feedback = get_option('em_paypal_booking_feedback');
							$EM_Notices->add_confirm( $feedback );
						}else{
							$result = false;
							$EM_Notices->add_error( $EM_Event->get_bookings()->get_errors() );				
						}
					}else{
						$result = false;
						$EM_Notices->add_error( $EM_Booking->get_errors() );	
					}
				}else{
					$result = false;
					$EM_Notices->add_error( $EM_Booking->get_errors() );		
				}
				$feedback = empty($feedback) ? $EM_Event->get_bookings()->feedback_message:$feedback;
				if( defined('DOING_AJAX') ){
					$return = array('result'=>$result, 'message'=> $feedback, 'errors'=>$EM_Notices->get_errors(), 'paypal_url'=>$paypal_url, 'paypal_vars'=>$paypal_vars);
					echo EM_Object::json_encode(apply_filters('em_gateway_paypal_booking_add', $return, $EM_Event, $EM_Booking));
					exit();
				}else{
					$EM_Notices->add_error(__('You must enable javascript in order for you to place a booking.','em-pro'),true);
					wp_redirect(wp_get_referer());
					exit();
				}
			}else{
				add_filter('pre_option_dbem_booking_feedback',create_function('$true','return \''. get_option('em_paypal_booking_feedback_free').'\';'),1,1); //change the feedback msg
				add_filter('pre_option_dbem_booking_feedback_pending',create_function('$true','return \''. get_option('em_paypal_booking_feedback_free').'\';'),1,1); //change the feedback msg
			}
		}
	}
	
	/**
	 * replaces default js to pass on returned HTML to offline.
	 * @param EM_Event $EM_Event
	 * @return string
	 */
	function em_gateway_js(){
		include(dirname(__FILE__).'/gateway.paypal.js');		
	}
	
	/**
	 * This gets called when a booking form is added. 
	 * @param unknown_type $button
	 * @param EM_Event $EM_Event
	 * @return string
	 */
	function booking_form_button(){
		ob_start();
		?>
		<img id="em-gateway-button-paypal" style="cursor:pointer;" src="<?php echo get_option('em_'. $this->gateway . "_button", "http://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" ); ?>" class="em-booking-submit" alt="<?php _e('Pay with Paypal','em-pro'); ?>" />
		<span id="em-jswarning"><?php _e('Javascript is required in order to make bookings with Paypal. Please enable this in your browser settings.','dbem'); ?></span>
		<script type="text/javascript">document.getElementById('em-jswarning').style.display = 'none';</script>
		<?php
		return ob_get_clean();
	}
	
	function say_thanks(){
		if( $_REQUEST['thanks'] == 1 ){
			echo "<div class='em-booking-message-success'>".__('Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you along with a seperate email containing account details to access your booking information on this site. You may log into your account at www.paypal.com to view details of this transaction.','dbem').'</div>';
		}
	}

	function mysettings() {
		global $EM_options;
		?>
		<p><?php _e('<strong>Important:</strong>In order to connect PayPal with your site, you need to enable IPN on your account. Please visit the <a href="http://wp-events-plugin.com/documentation/events-with-paypal/">documentation</a> for further instructions.')?></p>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Email', 'em-pro') ?></th>
				  <td><input type="text" name="paypal_email" value="<?php esc_attr_e( get_option('em_'. $this->gateway . "_email" )); ?>" />
				  <br />
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Site', 'em-pro') ?></th>
			  <td>
				  <select name="paypal_site">
				  <?php
				      $paypal_site = get_option('em_'. $this->gateway . "_site" );
				      $sel_locale = empty($paypal_site) ? 'US' : $paypal_site;
				      $locales = array('AU'	=> 'Australia', 'AT'	=> 'Austria', 'BE'	=> 'Belgium', 'CA'	=> 'Canada', 'CN'	=> 'China', 'FR'	=> 'France', 'DE'	=> 'Germany', 'HK'	=> 'Hong Kong', 'IT'	=> 'Italy', 'MX'	=> 'Mexico', 'NL'	=> 'Netherlands', 'NZ'	=>	'New Zealand', 'PL'	=> 'Poland', 'SG'	=> 'Singapore', 'ES'	=> 'Spain', 'SE'	=> 'Sweden', 'CH'	=> 'Switzerland', 'GB'	=> 'United Kingdom', 'US'	=> 'United States');
		
				      foreach ($locales as $key => $value) {
							echo '<option value="' . esc_attr($key) . '"';
				 			if($key == $sel_locale) echo 'selected="selected"';
				 			echo '>' . esc_html($value) . '</option>' . "\n";
				      }
				  ?>
				  </select>
				  <br />
				  <?php //_e('Format: 00.00 - Ex: 1.25', 'supporter') ?>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Paypal Currency', 'em-pro') ?></th>
			  <td><?php echo esc_html(get_option('dbem_bookings_currency','USD')); ?><br /><i><?php echo sprintf(__('Set your currency in the <a href="%s">settings</a> page.','dbem'),get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager-options'); ?></i></td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Mode', 'em-pro') ?></th>
			  <td>
				  <select name="paypal_status">
					  <option value="live" <?php if (get_option('em_'. $this->gateway . "_status" ) == 'live') echo 'selected="selected"'; ?>><?php _e('Live Site', 'em-pro') ?></option>
					  <option value="test" <?php if (get_option('em_'. $this->gateway . "_status" ) == 'test') echo 'selected="selected"'; ?>><?php _e('Test Mode (Sandbox)', 'em-pro') ?></option>
				  </select>
				  <br />
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Return URL', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_return" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_return" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('Once a payment is completed, users will be offered a link to this URL which confirms to the user that a payment is made. If you would to customize the thank you page, create a new page and add the link here. For automatic redirect, you need to turn auto-return on in your PayPal settings.', 'em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Payment button', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_button" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_button", 'http://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('You can use a different button image if required by entering the URL of that image here.', 'em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Page Logo', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_format_logo" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_format_logo" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('Add your logo to the PayPal payment page. It\'s highly recommended you link to a https:// address.', 'em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Border Color', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_format_border" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_format_border" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('Provide a hex value color to change the color from the default blue to another color (e.g. #CCAAAA).','em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Delete Bookings Pending Payment', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_booking_timeout" style="width:50px;" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_timeout" )); ?>" style='width: 40em;' /> <?php _e('minutes','em-pro'); ?><br />
			  	<em><?php _e('Once a booking is started and the user is taken to PayPal, Events Manager stores a booking record in the database to identify the incoming payment. These spaces may be considered reserved if you enable <em>Reserved unconfirmed spaces?</em> in your Events &gt; Settings page. If you would like these bookings to expire after x minutes, please enter a value above (note that bookings will be deleted, and any late payments will need to be refunded manually via PayPal).','em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Manually approve completed transactions?', 'em-pro') ?></th>
			  <td>
			  	<input type="checkbox" name="paypal_manual_approval" value="1" <?php echo (get_option('em_'. $this->gateway . "_manual_approval" )) ? 'checked="checked"':''; ?> /><br />
			  	<em><?php _e('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-pro'); ?></em><br />
			  	<em><?php echo sprintf(__('Approvals must also be required for all bookings in your <a href="%s">settings</a> for this to work properly.','em-pro'),'?page=events-manager-options'); ?></em>
			  </td>
		  </tr>
		  <tr><td colspan="2"><strong><?php _e('Success Messages','em-pro'); ?></strong></td></tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Success Message', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_booking_feedback" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('The message that is shown to a user when a booking is successful whilst being redirected to PayPal for payment.','em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Success Free Message', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_booking_feedback_free" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback_free" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be redirected to PayPal.','em-pro'); ?></em>
			  </td>
		  </tr>
		</tbody>
		</table>
		<?php
	}

	function update() {
		if( !empty($_REQUEST['paypal_email']) ) {
			$gateway_options = array(
				$this->gateway . "_email" => $_REQUEST[ 'paypal_email' ],
				$this->gateway . "_site" => $_REQUEST[ 'paypal_site' ],
				$this->gateway . "_currency" => $_REQUEST[ 'currency' ],
				$this->gateway . "_status" => $_REQUEST[ 'paypal_status' ],
				$this->gateway . "_button" => $_REQUEST[ 'paypal_button' ],
				$this->gateway . "_tax" => $_REQUEST[ 'paypal_button' ],
				$this->gateway . "_format_logo" => $_REQUEST[ 'paypal_format_logo' ],
				$this->gateway . "_format_border" => $_REQUEST[ 'paypal_format_border' ],
				$this->gateway . "_manual_approval" => $_REQUEST[ 'paypal_manual_approval' ],
				$this->gateway . "_booking_feedback" => $_REQUEST[ 'paypal_booking_feedback' ],
				$this->gateway . "_booking_feedback_free" => $_REQUEST[ 'paypal_booking_feedback_free' ],
				$this->gateway . "_booking_timeout" => $_REQUEST[ 'paypal_booking_timeout' ],
				$this->gateway . "_return" => $_REQUEST[ 'paypal_return' ]
			);
			foreach($gateway_options as $key=>$option){
				update_option('em_'.$key, $option);
			}
		}
		//default action is to return true
		return true;

	}

	// IPN stuff
	function handle_paypal_return() {
		// PayPal IPN handling code
		if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {
			
			if (get_option( $this->gateway . "_status" ) == 'live') {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			$req = 'cmd=_notify-validate';
			if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
			foreach ($_POST as $k => $v) {
				if (get_magic_quotes_gpc()) $v = stripslashes($v);
				$req .= '&' . $k . '=' . $v;
			}

			$header = 'POST /cgi-bin/webscr HTTP/1.0' . "\r\n"
					. 'Content-Type: application/x-www-form-urlencoded' . "\r\n"
					. 'Content-Length: ' . strlen($req) . "\r\n"
					. "\r\n";

			@set_time_limit(60);
			if (false && $conn = @fsockopen($domain, 80, $errno, $errstr, 30)) {
				fputs($conn, $header . $req);
				socket_set_timeout($conn, 30);

				$response = '';
				$close_connection = false;
				while (true) {
					if (feof($conn) || $close_connection) {
						fclose($conn);
						break;
					}

					$st = @fgets($conn, 4096);
					if ($st === false) {
						$close_connection = true;
						continue;
					}

					$response .= $st;
				}

				$error = '';
				$lines = explode("\n", str_replace("\r\n", "\n", $response));
				// looking for: HTTP/1.1 200 OK
				if (count($lines) == 0) $error = 'Response Error: Header not found';
				else if (substr($lines[0], -7) != ' 200 OK') $error = 'Response Error: Unexpected HTTP response';
				else {
					// remove HTTP header
					while (count($lines) > 0 && trim($lines[0]) != '') array_shift($lines);

					// first line will be empty, second line will have the result
					if (count($lines) < 2) $error = 'Response Error: No content found in transaction response';
					else if (strtoupper(trim($lines[1])) != 'VERIFIED') $error = 'Response Error: Unexpected transaction response';
				}

				if ($error != '') {
					echo $error;
					//fwrite($log,"\n".date('[Y-m-d H:s:i]').' Exiting, PP not verified.');
					//fclose($log);
					exit;
				}
			}
			
			// handle cases that the system must ignore
			$new_status = false;
			//Common variables
			$amount = $_POST['mc_gross'];
			$currency = $_POST['mc_currency'];
			$timestamp = date('Y-m-d H:i:s', strtotime($_POST['payment_date']));
			$custom_values = explode(':',$_POST['custom']);
			$booking_id = $custom_values[0];
			$event_id = !empty($custom_values[1]) ? $custom_values[1]:0;
			$EM_Booking = new EM_Booking($booking_id);
			if( !empty($EM_Booking->id) ){
				//booking exists
				$EM_Booking->manage_override = true; //since we're overriding the booking ourselves.
				$user_id = $EM_Booking->person_id;
				
				// process PayPal response
				switch ($_POST['payment_status']) {
					case 'Partially-Refunded':
						break;
	
					case 'In-Progress':
						break;
	
					case 'Completed':
					case 'Processed':
						// case: successful payment
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], '');
				
						//get booking metadata
						$user_data = array();
						if( !get_option('dbem_bookings_registration_disable') && !empty($EM_Booking->meta['registration']) && is_array($EM_Booking->meta['registration']) ){
							foreach($EM_Booking->meta['registration'] as $fieldid => $field){
								if( trim($field) !== '' ){
									$user_data[$fieldid] = $field;
								}
							}
							//if user was anonymous, let's figure out who they are and create an acct if necessary
							if( (int) $EM_Booking->person_id === 0){
								//get user email via paypal email (superceeded by booking meta value)
								if( !is_email( $user_data['user_email'] )){
									$user_data['user_email'] = $_POST['payer_email'];
								}
								//get payer's name from paypal if custom form doesn't have it
								if( empty($user_data['user_name']) && empty($user_data['first_name']) && empty($user_data['last_name']) ){
									$user_data['first_name'] = $_POST['first_name'];
									$user_data['last_name'] = $_POST['last_name'];
								}elseif( !empty($user_data['user_name']) && empty($user_data['first_name']) && empty($user_data['last_name']) ){
									$name = explode(' ', $user_data['user_name']);
									$user_data['first_name'] = array_shift($name);
									$user_data['last_name'] = implode(' ',$name);
								}
								$user = get_user_by_email($user_data['user_email']);	
								if( is_object($user) ){
									//user exists, so make this person the booking
									$EM_Booking->person_id = $user->ID;
								}else{
									//create new user with this email
									//get custom username if supplied in booking metadata and valid, otherwise, generate one
									if( empty($user_data['user_login']) ){
										$username_root = explode('@', $user_data['user_email']);
										$user_data['user_login'] = $username_root[0].rand(1,1000);
										while( username_exists($user_data['user_login']) ){
											$user_data['user_login'] = $username_root[0].rand(1,1000);
										}
									}
									//add phone number if it doesn't exist and paypal supplies it
									if( empty($user_data['dbem_phone']) && !empty($_POST['contact_phone']) ){
										$user_data['dbem_phone'] = $_POST['contact_phone'];
									}
									//not sure the below is needed, left out for now
									//do_action( 'register_post', $sanitized_user_login, $user_email, $errors );
															
									//set to go, let's register user directly, to prevent errors thrown by default registration
									$user_data['user_pass'] = wp_generate_password( 12, false);	
									$user_id = wp_insert_user( $user_data );
									
									if ( is_numeric($user_id)) {
										$EM_Booking->person_id = $user_id;
										if( !empty($user_data['dbem_phone']) ){
											update_user_meta($user_id, 'dbem_phone', $user_data['dbem_phone']);
										}
										update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.
										em_new_user_notification( $user_id, $user_data['user_pass'] );									
									}else{
										/* @var $user_id WP_Error */
										//TODO add a failsafe in case for some uknown reason a user isn't created (e.g. plguin conflict)
									}
									$user_id = apply_filters('em_register_new_user',$user_id); //filter which coincides with original filter for consistency
								}
							}
						}else{
							$EM_Booking->person_id = get_option('dbem_bookings_registration_user');
						}
						
						if( $_POST['mc_gross'] >= $EM_Booking->get_price(false, false, true) && (!get_option('em_'.$this->gateway.'_manual_approval', false) || !get_option('dbem_bookings_approval')) ){
							$EM_Booking->approve();
						}else{
							//TODO do something if pp payment not enough
							$EM_Booking->set_status(0); //Set back to normal "pending"
						}
						do_action('em_payment_processed', $EM_Booking, $this);
						break;
	
					case 'Reversed':
						// case: charge back
						$note = 'Last transaction has been reversed. Reason: Payment has been reversed (charge back)';
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						//We need to cancel their booking.
						$EM_Booking->cancel();
						do_action('em_payment_reversed', $EM_Booking, $this);
						
						break;
	
					case 'Refunded':
						// case: refund
						$note = 'Last transaction has been reversed. Reason: Payment has been refunded';
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						$EM_Booking->cancel();
						do_action('em_payment_refunded', $EM_Booking, $this);
						break;
	
					case 'Denied':
						// case: denied
						$note = 'Last transaction has been reversed. Reason: Payment Denied';
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						$EM_Booking->cancel();
						do_action('em_payment_denied', $EM_Booking, $this);
						break;
	
					case 'Pending':
						// case: payment is pending
						$pending_str = array(
							'address' => 'Customer did not include a confirmed shipping address',
							'authorization' => 'Funds not captured yet',
							'echeck' => 'eCheck that has not cleared yet',
							'intl' => 'Payment waiting for aproval by service provider',
							'multi-currency' => 'Payment waiting for service provider to handle multi-currency process',
							'unilateral' => 'Customer did not register or confirm his/her email yet',
							'upgrade' => 'Waiting for service provider to upgrade the PayPal account',
							'verify' => 'Waiting for service provider to verify his/her PayPal account',
							'paymentreview' => 'Paypal is currently reviewing the payment and will approve or reject within 24 hours',
							'*' => ''
							);
						$reason = @$_POST['pending_reason'];
						$note = 'Last transaction is pending. Reason: ' . (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);
	
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						do_action('em_payment_pending', $EM_Booking, $this);
						break;
	
					default:
						// case: various error cases		
				}
			}else{
				if( $_POST['payment_status'] == 'Completed' || $_POST['payment_status'] == 'Processed' ){
					$message = apply_filters('em_gateway_paypal_bad_booking_email',"
A Payment has been received by PayPal for a non-existent booking. 

Event Details : %event%

It may be that this user's booking has timed out yet they proceeded with payment at a later stage.

To refund this transaction, you must go to your PayPal account and search for this transaction:

Transaction ID : %transaction_id%
Email : %payer_email%

When viewing the transaction details, you should see an option to issue a refund.

If there is still space available, the user must book again.

Sincerely,
Events Manager
					", $booking_id, $event_id);
					if( !empty($event_id) ){
						$EM_Event = new EM_Event($event_id);
						$event_details = $EM_Event->name . " - " . date_i18n(get_option('date_format'), $EM_Event->start);
					}else{ $event_details = __('Unknown','em-pro'); }
					$message  = str_replace(array('%transaction_id%','%payer_email%', '%event%'), array($_POST['txn_id'], $_POST['payer_email'], $event_details), $message);
					wp_mail(get_option('em_'. $this->gateway . "_email" ), __('Unprocessed payment needs refund'), $message);
				}else{
					header('Status: 404 Not Found');
					echo 'Error: Bad IPN request, custom ID does not correspond with any pending booking.';
					//echo "<pre>"; print_r($_POST); echo "</pre>";
					exit;
				}
			}
			//fclose($log);
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('Status: 404 Not Found');
			echo 'Error: Missing POST variables. Identification is not possible.';
			exit;
		}
	}
}
emp_register_gateway('paypal', 'EM_Gateway_Paypal');

/**
 * Deletes bookings pending payment that are more than x minutes old, defined by paypal options. 
 */
function em_gateway_paypal_booking_timeout(){
	global $wpdb;
	//Get a time from when to delete
	$minutes_to_subtract = absint(get_option('em_paypal_booking_timeout'));
	if( $minutes_to_subtract > 0 ){
		//Run the SQL query
		//first delete ticket_bookings with expired bookings
		$sql = "DELETE FROM ".EM_TICKETS_BOOKINGS_TABLE." WHERE booking_id IN (SELECT booking_id FROM ".EM_BOOKINGS_TABLE." WHERE booking_date < TIMESTAMPADD(MINUTE, -{$minutes_to_subtract}, NOW()) AND booking_status=4);";
		$wpdb->query($sql);
		//then delete the bookings themselves
		$sql = "DELETE FROM ".EM_BOOKINGS_TABLE." WHERE booking_date < TIMESTAMPADD(MINUTE, -{$minutes_to_subtract}, NOW()) AND booking_status=4;";
		$wpdb->query($sql);
		update_option('emp_result_try',$sql);
	}
}
add_action('emp_cron_hook', 'em_gateway_paypal_booking_timeout');
?>