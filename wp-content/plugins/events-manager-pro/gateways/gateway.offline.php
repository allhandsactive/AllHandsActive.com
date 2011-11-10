<?php

class EM_Gateway_Offline extends EM_Gateway {

	var $gateway = 'offline';
	var $title = 'Offline';

	function EM_Gateway_Offline() {
		parent::EM_Gateway();
		add_action('EM_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
		// If I want to override the transactions output - then I can use this action
		//add_action('EM_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));
		add_action('init',array(&$this, 'actions'),1);
		add_action('em_bookings_single_metabox_footer', array(&$this, 'add_payment_form'),1,1); //add payment to booking
		add_action('em_bookings_manual_booking', array(&$this, 'add_booking_form'),1,1);
		//status control when editing bookings
		add_filter('em_booking_set_status',array(&$this,'em_booking_set_status'),1,2);
		//Extra Links on pages
		add_action('em_admin_event_booking_options_buttons', array(&$this, 'event_booking_options_buttons'),10);
		add_action('em_admin_event_booking_options', array(&$this, 'event_booking_options'),10);
		//Awaiting Payments Pane
		add_filter('em_bookings_pending_count', array(&$this, 'em_bookings_pending_count'),1,1);
		add_action('em_bookings_dashboard', array(&$this, 'get_pending'),1,1);
		add_action('em_bookings_event_footer', array(&$this, 'get_pending'),1,1);
		//Modify spaces calculations
		add_filter('em_bookings_get_pending_spaces', array(&$this, 'em_bookings_get_pending_spaces'),1,2);
		//Active-only feeatures
		if($this->is_active()) {	
			//Booking form tweaks
			add_action('em_gateway_js', array(&$this,'em_gateway_js'),10); //JS Replacement, so we can handle the ajax return differently
			add_filter('em_gateway_form_buttons', array(&$this,'booking_form_button'),1,1); //Replace button with PP image
			//Booking interception
			add_action('em_booking_add_'.$this->gateway, array(&$this,'em_booking_add'),1,2); //modify booking status code for this gateway
		}
	}
	
	function actions(){
		global $EM_Notices, $EM_Booking, $EM_Event, $wpdb;
		//Check if manual payment has been added
		if( !empty($_REQUEST['booking_id']) && !empty($_REQUEST['action']) && !empty($_REQUEST['_wpnonce'])){
			$EM_Booking = new EM_Booking($_REQUEST['booking_id']);
			if( $_REQUEST['action'] == 'gateway_add_payment' && is_object($EM_Booking) && wp_verify_nonce($_REQUEST['_wpnonce'], 'gateway_add_payment') ){
				if( !empty($_REQUEST['transaction_total_amount']) && is_numeric($_REQUEST['transaction_total_amount']) ){
					$this->record_transaction($EM_Booking, $_REQUEST['transaction_total_amount'], get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', $_REQUEST['transaction_note']);
					$string = __('Payment has been registered.','em-pro');
					$total = $wpdb->get_var('SELECT SUM(transaction_total_amount) FROM '.EM_TRANSACTIONS_TABLE." WHERE booking_id={$EM_Booking->id}");
					if( $total >= $EM_Booking->get_price() ){
						$EM_Booking->approve();
						$string .= " ". __('Booking is now fully paid and confirmed.','em-pro');
					}
					$EM_Notices->add_confirm($string);
					do_action('em_payment_processed', $EM_Booking, $this);
				}
			}
		}
		//manual bookings
		if( !empty($_REQUEST['event_id']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'manual_booking' && current_user_can('activate_plugins') && wp_verify_nonce($_REQUEST['_wpnonce'],'manual_booking')){ //TODO allow manual bookings for any event owner that can manage bookings
			$EM_Booking = new EM_Booking();
			$EM_Event = new EM_Event($_REQUEST['event_id']);
			if( $EM_Booking->get_post() ){
				//Assign a user to this booking
				$EM_Booking->person = new EM_Person($_REQUEST['person_id']);
				$EM_Booking->status = !empty($_REQUEST['booking_paid']) ? 1 : 5;
				if( $EM_Event->get_bookings()->add($EM_Booking) ){
					$result = true;
					if( !empty($_REQUEST['booking_paid']) ){
						$this->record_transaction($EM_Booking, $EM_Booking->get_price(), get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', '');
					}
					$EM_Notices->add_confirm( $EM_Event->get_bookings()->feedback_message );
				}else{
					ob_start();
					$result = false;
					$EM_Booking->feedback_message = ob_get_clean();
					$EM_Notices->add_error( $EM_Event->get_bookings()->get_errors() );				
				}
			}else{
				$result = false;
				$EM_Notices->add_error( $EM_Booking->get_errors() );
			}		
		}
	}
	
	/**
	 * This gets called when a booking form is added. 
	 * @param unknown_type $button
	 * @param EM_Event $EM_Event
	 * @return string
	 */
	function booking_form_button(){
		ob_start();
		if( get_option('em_'. $this->gateway . "_button") ): ?>
			<img id="em-gateway-button-<?php echo $this->gateway; ?>" src="<?php echo get_option('em_'. $this->gateway . "_button", WP_PLUGIN_URL."/events-manager-pro/includes/images/offline_button.png" ); ?>" class="em-booking-submit" alt="<?php _e('Pay Offline','em-pro'); ?>" />
		<?php else: ?>
			<input type="submit" class="em-booking-submit" id="em-gateway-button-<?php echo $this->gateway; ?>" value="<?php echo get_option('em_'. $this->gateway . "_button_text") ? get_option('em_'. $this->gateway . "_button_text"):_e('Pay Offline','em-pro'); ?>" />
		<?php endif;
		return ob_get_clean();
	}
	
	/**
	 * replaces default js to pass on returned HTML to offline.
	 * @param unknown_type $original_js
	 * @return string
	 */
	function em_gateway_js(){
		include(dirname(__FILE__).'/gateway.offline.js');		
	}
	
	/**
	 * We catch this booking before it's saved, and handle the saving from here.
	 * @param EM_Event $EM_Event
	 * @param EM_Booking $EM_Booking
	 */
	function em_booking_add($EM_Event,$EM_Booking){
		global $wpdb, $wp_rewrite, $EM_Notices;
		if( !get_option('em_booking_form_custom')) {
			add_filter('pre_option_dbem_bookings_anonymous',create_function('$true','return \'\';'),1,1); //Prevent register form from showing.
		}
		if( !$EM_Event->is_free() ){
			$EM_Booking->status = 5; //status 5 = awaiting payment
			//add_filter('pre_option_dbem_booking_feedback',array(&$this,'pre_option_dbem_booking_feedback'),1,1); //Prevent register form from showing.
			add_filter('pre_option_dbem_booking_feedback',create_function('$true','return \''. get_option('em_offline_booking_feedback').'\';'),1,1); //change the feedback msg
		}
	}
	
	/**
	 * Change the feedback notice
	 * @param EM_Booking $EM_Booking
	 */
	function pre_option_dbem_booking_feedback($feedback){
		return get_option('em_offline_booking_feedback');	
	}
	
	function em_booking_set_status($status, $EM_Booking){
		if($status == 1 && $EM_Booking->previous_status == 5 && (empty($_REQUEST['action']) || $_REQUEST['action'] != 'gateway_add_payment') ){
			$this->record_transaction($EM_Booking, $EM_Booking->get_price(false,false,true), get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', '');								
		}
	}
	
	function add_payment_form() {
		?>
		<div id="em-gateway-payment" class="stuffbox">
			<h3>
				<?php _e('Add Offline Payment', 'dbem'); ?>
			</h3>
			<div class="inside">
				<div>
					<form method="post" action="" style="padding:5px;">
						<table class="form-table">
							<tbody>
							  <tr valign="top">
								  <th scope="row"><?php _e('Amount', 'em-pro') ?></th>
									  <td><input type="text" name="transaction_total_amount" value="<?php echo $_REQUEST['transaction_total_amount']; ?>" />
									  <br />
								  </td>
							  </tr>
							  <tr valign="top">
								  <th scope="row"><?php _e('Comments', 'em-pro') ?></th>
								  <td>
										<textarea name="transaction_note"><?php echo $_REQUEST['transaction_note']; ?></textarea>
								  </td>
							  </tr>
							</tbody>
						</table>							
						<input type="hidden" name="action" value="gateway_add_payment" />
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('gateway_add_payment'); ?>" />
						<input type="submit" value="<?php _e('Add Offline Payment', 'dbem'); ?>" />
					</form>
				</div>					
			</div>
		</div> 
		<?php
	}

	function add_booking_form() {
		/* @var $EM_Event EM_Event */   
		global $EM_Notices, $EM_Event;
		if( !is_object($EM_Event) ) { return; }
		$booked_places_options = array();
		for ( $i = 1; $i <= 10; $i++ ) {
			$booking_spaces = (!empty($_POST['booking_spaces']) && $_POST['booking_spaces'] == $i) ? 'selected="selected"':'';
			array_push($booked_places_options, "<option value='$i' $booking_spaces>$i</option>");
		}
		$EM_Tickets = $EM_Event->get_bookings()->get_tickets();	
		?>
		<div class='wrap'>
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php echo sprintf(__('Add Booking For &quot;%s&quot;','em-pro'), $EM_Event->name); ?></h2>
			<div id="em-booking">
				<?php if( $EM_Event->start < current_time('timestamp') ): ?>
					<p><?php _e('Bookings are closed for this event.','dbem'); ?></p>
				<?php else: ?>
					<?php echo $EM_Notices; ?>		
					<?php if( count($EM_Tickets->tickets) > 0) : ?>
						<?php //Tickets exist, so we show a booking form. ?>
						<form id='em-booking-form' name='booking-form' method='post' action=''>
							<?php do_action('em_booking_form_before_tickets'); ?>
							<?php if( count($EM_Tickets->tickets) > 1 ): ?>
								<div class='table-wrap'>
								<table class="em-tickets widefat post" cellspacing="0" cellpadding="0">
									<thead>
										<tr>
											<th><?php _e('Ticket Type','dbem') ?></th>
											<?php if( !$EM_Event->is_free() ): ?>
											<th><?php _e('Price','dbem') ?></th>
											<?php endif; ?>
											<th><?php _e('Spaces','dbem') ?></th>
										</tr>
									</thead>
									<tbody>
									<?php foreach( $EM_Tickets->tickets as $EM_Ticket ): ?>
										<?php if( $EM_Ticket->is_available() || get_option('dbem_bookings_tickets_show_unavailable') ): ?>
										<tr>
											<td><?php echo wp_kses_data($EM_Ticket->name); ?></td>
											<?php if( !$EM_Event->is_free() ): ?>
											<td><?php echo $EM_Ticket->get_price(true); ?></td>
											<?php endif; ?>
											<td>
												<?php 
													$spaces_options = $EM_Ticket->get_spaces_options();
													if( $spaces_options ){
														echo $spaces_options;
													}else{
														echo "<strong>".__('N/A','dbem')."</strong>";
													}
												?>
											</td>
										</tr>
										<?php endif; ?>
									<?php endforeach; ?>
									</tbody>
								</table>	
								</div>	
							<?php endif; ?>
							<?php do_action('em_booking_form_after_tickets'); ?>
							<div class='em-booking-form-details'>
							
								<?php $EM_Ticket = $EM_Tickets->get_first(); ?>
								<?php if( is_object($EM_Ticket) && count($EM_Tickets->tickets) == 1 ): ?>
								<p>
									<label for='em_tickets'><?php _e('Spaces', 'dbem') ?></label>
									<?php 
										$spaces_options = $EM_Ticket->get_spaces_options(false);
										if( $spaces_options ){
											echo $spaces_options;
										}else{
											echo "<strong>".__('N/A','dbem')."</strong>";
										}
									?>
								</p>	
								<?php endif; ?>
								
								<?php //Here we have extra information required for the booking. ?>
								<?php do_action('em_booking_form_before_user_details'); ?>
								<p>
									<label for='booking_comment'><?php _e('User', 'dbem') ?></label>
									<?php
									$person_id = (!empty($_REQUEST['person_id'])) ? $_REQUEST['person_id'] : false;
									wp_dropdown_users ( array ('name' => 'person_id', 'show_option_none' => __ ( "Select User", 'dbem' ), 'selected' => $person_id  ) );
									?>
								</p>
								<p>
									<label for='booking_comment'><?php _e('Already Paid?', 'dbem') ?></label>
									<input type="checkbox" name="booking_paid" value="1" style="width:auto;"/>
								</p>
								<?php if( get_option('em_booking_form_custom') ) : ?>
									<?php do_action('em_booking_form_custom'); ?>
								<?php else: //temporary fix, don't depend on this ?>	
									<p>
										<label for='booking_comment'><?php _e('Comment', 'dbem') ?></label>
										<textarea name='booking_comment'><?php echo !empty($_POST['booking_comment']) ? $_POST['booking_comment']:'' ?></textarea>
									</p>
									<?php do_action('em_booking_form_after_user_details'); ?>	
								<?php endif; ?>	
								<?php do_action('em_booking_form_after_user_details'); ?>					
								<div class="em-booking-buttons">
									<input type='submit' value="<?php _e('Submit Booking','em-pro'); ?>" />
								 	<input type='hidden' name='gateway' value='offline'/>
								 	<input type='hidden' name='action' value='manual_booking'/>
								 	<input type='hidden' name='event_id' value='<?php echo $EM_Event->id; ?>'/>
								 	<input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('manual_booking'); ?>'/>
								</div>
							</div>
						</form>	
					<?php elseif( count($EM_Tickets->tickets) == 0 ): ?>
						<div><?php _e('No more tickets available at this time.','dbem'); ?></div>
					<?php endif; ?>  
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	
	function mysettings() {

		global $EM_options;
		?>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
			  <th scope="row"><?php _e('Button Image', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="offline_button" value="<?php echo esc_attr_e(get_option('em_'. $this->gateway . "_button" )); ?>" style='width: 40em;' /><br />
			  	<i><?php _e('If you enter an image URL, this will be used instead of a button.','em-pro'); ?></i>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Button Text', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="offline_button_text" value="<?php echo esc_attr_e(get_option('em_'. $this->gateway . "_button_text")); ?>" style='width: 40em;' /><br />
			  	<i><?php _e('Change the default button text.','em-pro'); ?></i>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Success Message', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="offline_booking_feedback" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('The message that is shown to a user when a booking with offline payments is successful.','em-pro'); ?></em>
			  </td>
		  </tr>
		</tbody>
		</table>
		<?php
	}

	function update() {
		$gateway_options = array(
			$this->gateway . "_button_text" => $_REQUEST[ 'offline_button_text' ],
			$this->gateway . "_button" => $_REQUEST[ 'offline_button' ],
			$this->gateway . "_booking_feedback" => $_REQUEST[ 'offline_booking_feedback' ]
		);
		foreach($gateway_options as $key=>$option){
			update_option('em_'.$key, $option);
		}
		//default action is to return true
		return true;

	}
	
	function em_bookings_pending_count($count){
		return $count + count(EM_Bookings::get(array('status'=>'5'))->bookings);
	}
	
	/**
	 * @param integer $count
	 * @param EM_Bookings $EM_Bookings
	 */
	function em_bookings_get_pending_spaces($count, $EM_Bookings){
		foreach($EM_Bookings->bookings as $EM_Booking){
			if($EM_Booking->status == 5){
				$count += $EM_Booking->get_spaces();
			}
		}
		return $count;
	}
	
	/**
	 * Generates a "widget" table of bookings awaiting payment with some quick admin operation options. 
	 * If event id supplied then only pending bookings for that event will show.
	 */
	function get_pending(){
		global $EM_Event, $EM_Booking, $EM_Ticket, $wpdb, $current_user;
		
		$action_scope = ( !empty($_REQUEST['em_obj']) && $_REQUEST['em_obj'] == 'em_bookings_pending_table' );
		$action = ( $action_scope && !empty($_GET ['action']) ) ? $_GET ['action']:'';
		$order = ( $action_scope && !empty($_GET ['order']) ) ? $_GET ['order']:'ASC';
		$limit = ( $action_scope && !empty($_GET['limit']) ) ? $_GET['limit'] : 20;//Default limit
		$page = ( $action_scope && !empty($_GET['pno']) ) ? $_GET['pno']:1;
		$offset = ( $action_scope && $page > 1 ) ? ($page-1)*$limit : 0;
		
		if( is_object($EM_Event) ){
			//We search transactions and get a list of booking IDs to load
			$EM_Bookings = EM_Bookings::get(array('status'=>5,'event'=>$EM_Event->id));
		}else{
			$EM_Bookings = EM_Bookings::get(array('status'=>5));
		}
		$bookings_count = (is_array($EM_Bookings->bookings)) ? count($EM_Bookings->bookings):0;
		?>
		<h2><?php echo _e('Bookings Awaiting Payment','em-pro'); ?></h2>
			<div class='wrap em_bookings_payment_table em_obj'>
				<form id='bookings-filter' method='get' action='<?php bloginfo('wpurl') ?>/wp-admin/edit.php'>
					<input type="hidden" name="em_obj" value="em_bookings_pending_table" />
					<!--
					<ul class="subsubsub">
						<li>
							<a href='edit.php?post_type=post' class="current">All <span class="count">(1)</span></a> |
						</li>
					</ul>
					<p class="search-box">
						<label class="screen-reader-text" for="post-search-input"><?php _e('Search'); ?>:</label>
						<input type="text" id="post-search-input" name="em_search" value="<?php echo (!empty($_GET['em_search'])) ? $_GET['em_search']:''; ?>" />
						<input type="submit" value="<?php _e('Search'); ?>" class="button" />
					</p>
					-->
					<?php if ( $bookings_count >= $limit ) : ?>
					<div class='tablenav'>
						<!--
						<div class="alignleft actions">
							<select name="action">
								<option value="-1" selected="selected">
									<?php _e('Bulk Actions'); ?>
								</option>
								<option value="approve">
									<?php _e('Approve', 'dbem'); ?>
								</option>
								<option value="decline">
									<?php _e('Decline', 'dbem'); ?>
								</option>
							</select> 
							<input type="submit" id="post-query-submit" value="Filter" class="button-secondary" />
						</div>
						-->
						<!--
						<div class="view-switch">
							<a href="/wp-admin/edit.php?mode=list"><img class="current" id="view-switch-list" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="List View" alt="List View" name="view-switch-list" /></a> <a href="/wp-admin/edit.php?mode=excerpt"><img id="view-switch-excerpt" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="Excerpt View" alt="Excerpt View" name="view-switch-excerpt" /></a>
						</div>
						-->
						<?php 
						if ( $bookings_count >= $limit ) {
							$bookings_nav = em_admin_paginate( $bookings_count, $limit, $page, array('em_ajax'=>0, 'em_obj'=>'em_bookings_pending_table'));
							echo $bookings_nav;
						}
						?>
						<div class="clear"></div>
					</div>
					<?php endif; ?>
					<div class="clear"></div>
					<?php if( $bookings_count > 0 ): ?>
					<div class='table-wrap'>
					<table id='dbem-bookings-table' class='widefat post '>
						<thead>
							<tr>
								<th class='manage-column column-cb check-column' scope='col'>
									<input class='select-all' type="checkbox" value='1' />
								</th>
								<th class='manage-column' scope='col'>Booker</th>
								<?php if( (empty($EM_Event) || !is_object($EM_Event)) && (empty($EM_Ticket) || !is_object($EM_Ticket)) ): ?>
								<th class='manage-column' scope="col">Event</th>
								<?php endif; ?>
								<th class='manage-column' scope='col'>E-mail</th>
								<th class='manage-column' scope='col'>Phone number</th>
								<th class='manage-column' scope='col'>Spaces</th>
								<th class='manage-column' scope='col'>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							$rowno = 0;
							$event_count = 0;
							foreach ($EM_Bookings->bookings as $EM_Booking) {
								if( ($rowno < $limit || empty($limit)) && ($event_count >= $offset || $offset === 0) ) {
									$rowno++;
									?>
									<tr>
										<th scope="row" class="check-column" style="padding:7px 0px 7px;"><input type='checkbox' value='<?php echo $EM_Booking->id ?>' name='bookings[]'/></th>
										<td><a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-bookings&amp;person_id=<?php echo $EM_Booking->person->ID; ?>"><?php echo $EM_Booking->person->get_name() ?></a></td>
										<?php if( (empty($EM_Event) || !is_object($EM_Event)) && (empty($EM_Ticket) || !is_object($EM_Ticket)) ): ?>
										<td><a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-bookings&amp;event_id=<?php echo $EM_Booking->event_id; ?>"><?php echo $EM_Booking->get_event()->name ?></a></td>
										<?php endif; ?>
										<td><?php echo $EM_Booking->person->user_email ?></td>
										<td><?php echo $EM_Booking->person->phone ?></td>
										<td><?php echo $EM_Booking->get_spaces() ?></td>
										<td>
											<?php
											$approve_url = em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->id));
											$reject_url = em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_reject', 'booking_id'=>$EM_Booking->id));
											$delete_url = em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_delete', 'booking_id'=>$EM_Booking->id));
											?>
											<a class="em-bookings-approve em-bookings-approve-offline" href="<?php echo $approve_url ?>"><?php _e('Approve','dbem'); ?></a> |
											<a class="em-bookings-reject" href="<?php echo $reject_url ?>"><?php _e('Reject','dbem'); ?></a> |
											<span class="trash"><a class="em-bookings-delete" href="<?php echo $delete_url ?>"><?php _e('Delete','dbem'); ?></a></span> |
											<a class="em-bookings-edit" href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-bookings&amp;booking_id=<?php echo $EM_Booking->id; ?>"><?php _e('Edit/View','dbem'); ?></a>
										</td>
									</tr>
									<?php
								}
								$event_count++;
							}
							?>
						</tbody>
					</table>
					<script type="text/javascript">
						jQuery(document).ready(function($){
							$('.em-bookings-approve-offline').click(function(e){
								if( !confirm('<?php _e('Be aware that by approving a booking awaiting payment, a full payment transaction will be registered against this booking, meaning that it will be considered as paid.','dbem'); ?>') ){
									return false; 
								}
							});
						});
					</script>
					</div>
					<?php else: ?>
						<?php _e('No pending bookings.', 'dbem'); ?>
					<?php endif; ?>
				</form>
				<?php if( !empty($bookings_nav) && $EM_Bookings >= $limit ) : ?>
				<div class='tablenav'>
					<?php echo $bookings_nav; ?>
					<div class="clear"></div>
				</div>
				<?php endif; ?>
			</div>	
		<?php
	}
	
	function event_booking_options_buttons(){
		global $EM_Event;
		?><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=events-manager-bookings&amp;action=manual_booking&amp;event_id=<?php echo $EM_Event->id ?>" class="button add-new-h2"><?php _e('Add Booking','dbem') ?></a><?php	
	}
	
	function event_booking_options(){
		global $EM_Event;
		?><a href="<?php bloginfo('wpurl'); ?>/wp-admin/admin.php?page=events-manager-bookings&amp;action=manual_booking&amp;event_id=<?php echo $EM_Event->id ?>"><?php _e('add booking','dbem') ?></a><?php	
	}
}
emp_register_gateway('offline', 'EM_Gateway_Offline');
?>