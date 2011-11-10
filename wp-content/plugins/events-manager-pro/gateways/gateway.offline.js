$('#em-gateway-button-offline').click(function(e){
	e.preventDefault();
	var booking_gateway_handler = function(event, response){
		if(response.result){
			$('<div class="em-booking-message-success em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
			$('#em-booking-form').remove();
			$('.em-booking-login').remove();
		}else{
			if( response.errors != null ){
				if( $.isArray(response.errors) && response.errors.length > 0 ){
					var error_msg;
					response.errors.each(function(i, el){ 
						error_msg = error_msg + el;
					});
					$('<div class="em-booking-message-error em-booking-message">'+error_msg.errors+'</div>').insertBefore('#em-booking-form');
				}else{
					$('<div class="em-booking-message-error em-booking-message">'+response.errors+'</div>').insertBefore('#em-booking-form');							
				}
			}else{
				$('<div class="em-booking-message-error em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
			}
		}
		$('#em-booking-form input[name=gateway]').remove();
		$(document).unbind('em_booking_gateway_add',booking_gateway_handler);	
	};
	$(document).bind('em_booking_gateway_add',booking_gateway_handler);
	$('#em-booking-form').append('<input type="hidden" name="gateway" value="offline" />');
	$('#em-booking-form').trigger('submit');
});