$('#em-gateway-button-paypal').click(function(e){
	e.preventDefault();
	var booking_gateway_handler = function(event, response){
		if(response.result){
			$('<div class="em-booking-message-success em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
			if(response.paypal_vars){
				var ppForm = $('<form action="'+response.paypal_url+'" method="post" id="em-paypal-redirect-form"></form>');
				$.each( response.paypal_vars, function(index,value){
					ppForm.append('<input type="hidden" name="'+index+'" value="'+value+'" />');
				});
				ppForm.append('<input id="em-paypal-submit" type="submit" style="display:none" />');
				ppForm.insertAfter('#em-booking-form');
				$('#em-booking-form').remove();
				$('.em-booking-login').remove();
				$('#em-paypal-redirect-form').trigger('submit');
			}
			$('#em-booking-form').remove();
			$('.em-booking-login').remove();
		}else{
			if( response.errors != '' ){
				if( $.isArray() ){
					var error_msg;
					response.errors.each(function(i, el){ 
						error_msg = error_msg + el;
					});
					$('<div class="em-booking-message-error em-booking-message">'+response.errors+'</div>').insertBefore('#em-booking-form');
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
	$('#em-booking-form').append('<input type="hidden" name="gateway" value="paypal" />');
	$('#em-booking-form').trigger('submit');
});