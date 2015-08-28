<?php
	/*	
	*	Goodlayers Booking Item
	*/

	if( !function_exists('gdlr_booking_process_bar') ){
		function gdlr_booking_process_bar( $state = 1 ){
			$ret  = '<div class="gdlr-booking-process-bar" id="gdlr-booking-process-bar" data-state="' . $state . '" >';
			$ret .= '<div data-process="1" class="gdlr-booking-process ' . (($state==1)? 'gdlr-active': '') . '">' . __('1. Choose Date', 'gdlr-hotel') . '</div>';
			$ret .= '<div data-process="2" class="gdlr-booking-process ' . (($state==2)? 'gdlr-active': '') . '">' . __('2. Choose Room', 'gdlr-hotel') . '</div>';
			$ret .= '<div data-process="3" class="gdlr-booking-process ' . (($state==3)? 'gdlr-active': '') . '">' . __('3. Make a Reservation', 'gdlr-hotel') . '</div>';
			$ret .= '<div data-process="4" class="gdlr-booking-process ' . (($state==4)? 'gdlr-active': '') . '">' . __('4. Confirmation', 'gdlr-hotel') . '</div>';
			$ret .= '</div>';
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_booking_date_range') ){
		function gdlr_booking_date_range( $state = 1 ){ 
?>
<div class="gdlr-datepicker-range-wrapper" >
<div class="gdlr-datepicker-range" id="gdlr-datepicker-range" ></div>
</div>
<?php
		}
	}

	// ajax action for booking form
	add_action( 'wp_ajax_gdlr_hotel_booking', 'gdlr_ajax_hotel_booking' );
	add_action( 'wp_ajax_nopriv_gdlr_hotel_booking', 'gdlr_ajax_hotel_booking' );
	if( !function_exists('gdlr_ajax_hotel_booking') ){
		function gdlr_ajax_hotel_booking(){	
			if( !empty($_POST['data']) ){
				parse_str($_POST['data'], $data);
			}
			if( !empty($_POST['contact']) ){
				parse_str($_POST['contact'], $contact);
			}
			$ret = array();

			// query section
			if( $_POST['state'] == 2 ){
				$data['gdlr-room-id'] = empty($data['gdlr-room-id'])? array(): $data['gdlr-room-id'];
				$room_number = gdlr_get_edited_room($data['gdlr-room-number'], $data['gdlr-room-id']);
				
				// room form
				$ret['room_form'] = gdlr_get_reservation_room_form($data, $room_number);
				
				// content area
				if( $data['gdlr-room-number'] > $room_number ){
					$ret['content'] = gdlr_get_booking_room_query($data, $room_number);
				}else{
					$ret['content']  = '<div class="gdlr-room-selection-complete">';
					$ret['content'] .= '<div class="gdlr-room-selection-title" >' . __('Room Selection is Complete', 'gdlr-hotel') . '</div>';
					$ret['content'] .= '<div class="gdlr-room-selection-caption" >' . __('You can edit your booking by using the panel on the left', 'gdlr-hotel') . '</div>';
					$ret['content'] .= '<a class="gdlr-button with-border gdlr-room-selection-next">' . __('Go to next step', 'gdlr-hotel') . '</a>';
					$ret['content'] .= '</div>';
				}
				
				$ret['state'] = 2;
			}else if( $_POST['state'] == 3 ){
				if( empty($_POST['contact']) ){
					$ret['summary_form'] = gdlr_get_summary_form($data);
					$ret['content'] = gdlr_get_booking_contact_form();
					$ret['state'] = 3;
				}else{
					$validate = gdlr_validate_contact_form($contact);
					
					if( !empty($validate) ){
						$ret['state'] = 3;
						$ret['error_message'] = $validate;
					}else{
						$ret['summary_form'] = gdlr_get_summary_form($data, false);
						
						if( $_POST['contact_type'] == 'contact' ){
							$booking = gdlr_insert_booking_db(array('data'=>$data, 'contact'=>$contact, 'payment_status'=>'booking'));
							
							global $hotel_option;
							
							$mail_content = gdlr_hotel_mail_content( $contact, $data, array(), array(
								'total_price'=>$booking['total-price'], 'pay_amount'=>0, 'booking_code'=>$booking['code'])
							);
							gdlr_hotel_mail($contact['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
							gdlr_hotel_mail($hotel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);
							
							$ret['content'] = gdlr_booking_complete_message();
							$ret['state'] = 4;
						}else{
							global $hotel_option;
							$booking = gdlr_insert_booking_db(array('data'=>$data, 'contact'=>$contact, 'payment_status'=>'pending'));
							
							if( $contact['payment-method'] == 'paypal' ){
								$ret['payment'] = 'paypal';
								$ret['payment_url'] = $hotel_option['paypal-action-url'];
								$ret['addition_part'] = gdlr_additional_paypal_part(array(
									'title' => __('Room Booking', 'gdlr-hotel'), 
									'invoice' => $booking['invoice'],
									'price' => $booking['pay-amount']
								));
							}else if( $contact['payment-method'] == 'stripe' ){
								$ret['content'] = gdlr_get_stripe_form(array(
									'invoice' => $booking['invoice']
								));
							}else if( $contact['payment-method'] == 'paymill' ){
								$ret['content'] = gdlr_get_paymill_form(array(
									'invoice' => $booking['invoice']
								));
							}else if( $contact['payment-method'] == 'authorize' ){
								$ret['content'] = gdlr_get_authorize_form(array(
									'invoice' => $booking['invoice'],
									'price' => $booking['pay-amount']
								));
							}
							
							// made payment
							$ret['state'] = 3;
						}
					}
				}
			}
			
			if( !empty($data) ){
				$ret['data'] = $data;
			}
			
			die(json_encode($ret));
		}
	}
	
	// check if every room is selected.
	if( !function_exists('gdlr_get_edited_room)') ){
		function gdlr_get_edited_room($max_room = 0, $rooms = array()){
			for( $i=0; $i<$max_room; $i++ ){
				if( empty($rooms[$i]) ) return $i;
			}
			
			return $max_room;
		}
	}
	
	// booking room style
	if( !function_exists('gdlr_get_booking_room_query') ){
		function gdlr_get_booking_room_query($data, $room_number){
			global $wpdb, $hotel_option, $sitepress;

			$num_people = intval($data['gdlr-adult-number'][$room_number]) + intval($data['gdlr-children-number'][$room_number]);
			$hotel_option['preserve-booking-room'] = empty($hotel_option['preserve-booking-room'])? 'paid': $hotel_option['preserve-booking-room'];
			
			$sql  = "SELECT DISTINCT post_id FROM $wpdb->postmeta ";
			$sql .= "WHERE meta_key = 'gdlr_max_people' AND meta_value >= $num_people ";
			$sql .= "ORDER BY post_id DESC";
			
			$rooms = array();
			$room_temp = array();
			if( !empty($data['gdlr-room-id']) ){
				foreach( $data['gdlr-room-id'] as $selected_room_id ){
					$room_temp[$selected_room_id] = empty($room_temp[$selected_room_id])? 1: $room_temp[$selected_room_id] + 1; 
				}
			}
			
			$room_query =  $wpdb->get_results($sql, OBJECT);
			foreach($room_query as $room){ 
				$avail_num = intval(get_post_meta($room->post_id, 'gdlr_room_amount', true));
				if( !empty($room_temp[$room->post_id]) ){ $avail_num = $avail_num - $room_temp[$room->post_id]; }
				
				$sql  = "SELECT COUNT(*) "; 
				$sql .= "FROM {$wpdb->prefix}gdlr_hotel_booking, {$wpdb->prefix}gdlr_hotel_payment WHERE ";
				
				// for wpml
				if( !empty($sitepress) ){
					$count = 0;
					$trid = $sitepress->get_element_trid(4233, 'post_page');
					$translations = $sitepress->get_element_translations($trid,'post_page');
					
					$sql .= "(";
					foreach( $translations as $translation ){ $count++;
						$sql .= ($count > 1)? 'OR ': '';
						$sql .= "{$wpdb->prefix}gdlr_hotel_booking.room_id = {$translation->element_id} ";
					}
					$sql .= ") AND ";
				}else{
					$sql .= "{$wpdb->prefix}gdlr_hotel_booking.room_id = {$room->post_id} AND ";
				}
				$sql .= "{$wpdb->prefix}gdlr_hotel_payment.id = {$wpdb->prefix}gdlr_hotel_booking.payment_id AND ";
				if( $hotel_option['preserve-booking-room'] == 'paid' ){ 
					$sql .= "{$wpdb->prefix}gdlr_hotel_payment.payment_status = 'paid' AND ";
				}else{
					$sql .= "{$wpdb->prefix}gdlr_hotel_payment.payment_status != 'pending' AND ";
				}
				// $sql .= "((start_date >= '{$data['gdlr-check-in']}' AND start_date < '{$data['gdlr-check-out']}') OR ";
				// $sql .= "(end_date > '{$data['gdlr-check-in']}' AND end_date <= '{$data['gdlr-check-out']}'))";
				$sql .= "(start_date < '{$data['gdlr-check-out']}' AND '{$data['gdlr-check-in']}' < end_date)";

				if($avail_num > $wpdb->get_var($sql)){
					$rooms[] = $room->post_id;
				}
			}
			
			if( !empty($rooms) ){			
				$paged = empty($_POST['paged'])? 1: $_POST['paged'];
				$args = array(
					'post_type'=>'room', 
					'post__in' => $rooms, 
					'posts_per_page'=>$hotel_option['booking-num-fetch'],
					'paged' => $paged
				);
				if( !empty($data['gdlr-hotel-branches']) ){
					$args['tax_query'] = array(array(
						'taxonomy' => 'room_category',
						'field' => 'id',
						'terms' => intval($data['gdlr-hotel-branches'])
					));
				} 
				$query = new WP_Query($args);
					
				return gdlr_get_booking_room($query, array(
					'check-in'=> $data['gdlr-check-in'],
					'check-out'=> $data['gdlr-check-out'],
					'adult'=> $data['gdlr-adult-number'][$room_number], 
					'children'=> $data['gdlr-children-number'][$room_number]
				)) . gdlr_get_ajax_pagination($query->max_num_pages, $paged);
			}else{
				return '<div class="gdlr-hotel-missing-room">' . __('No room available', 'gdlr-hotel') . '</div>';
			}			
		}
	}
	if( !function_exists('gdlr_get_booking_room') ){
		function gdlr_get_booking_room($query, $data){
			global $hotel_option;
			global $gdlr_excerpt_length, $gdlr_excerpt_read_more; 
			$gdlr_excerpt_read_more = false;
			$gdlr_excerpt_length = $hotel_option['booking-num-excerpt'];
			add_filter('excerpt_length', 'gdlr_set_excerpt_length');

			$ret  = '<div class="gdlr-booking-room-wrapper" >';
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
				$post_option['data'] = $data;
				
				$ret .= '<div class="gdlr-item gdlr-room-item gdlr-medium-room">';
				$ret .= '<div class="gdlr-ux gdlr-medium-room-ux">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $hotel_option['booking-thumbnail-size']) . '</div>';	
				$ret .= '<div class="gdlr-room-content-wrapper">';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){
					$terms = get_the_terms(get_the_ID(), 'room_category');
					$ret .= '<div class="gdlr-room-hotel-branches">';
					foreach( $terms as $term ){
						$ret .= '<span class="gdlr-separator">,</span>' . $term->name;
					}
					$ret .= '</div>';
				}
				$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view'));
				$ret .= '<div class="gdlr-room-content">' . get_the_excerpt() . '</div>';
				$ret .= '<a class="gdlr-room-selection gdlr-button with-border" href="#" ';
				$ret .= 'data-roomid="' . get_the_ID() . '" >' . __('Select this room', 'gdlr-hotel') . '</a>';
				$ret .= gdlr_hotel_room_info($post_option, array('price-break-down'), false);
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>'; // gdlr-ux
				$ret .= '</div>'; // gdlr-item
			}
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>';
			wp_reset_postdata();
		
			$gdlr_excerpt_read_more = true;
			remove_filter('excerpt_length', 'gdlr_set_excerpt_length');	
		
			return $ret;
		}
	}
	
	// booking room style
	if( !function_exists('gdlr_get_booking_contact_form') ){
		function gdlr_get_booking_contact_form(){
			global $hotel_option;
			
			ob_start(); 
?>
<div class="gdlr-booking-contact-container">
	<form class="gdlr-booking-contact-form" method="post" data-ajax="<?php echo AJAX_URL; ?>">
		<p class="gdlr-form-half-left">
			<span><?php _e('Name *', 'gdlr-hotel'); ?></span>
			<input type="text" name="first_name" value="" />
		</p>
		<p class="gdlr-form-half-right">
			 <span><?php _e('Last Name *', 'gdlr-hotel'); ?></span>
			 <input type="text" name="last_name" value="" />
		</p>
		<div class="clear"></div>
		<p class="gdlr-form-half-left">
			<span><?php _e('Email *', 'gdlr-hotel'); ?></span>
			<input type="text" name="email" value="" />
		</p>
		<p class="gdlr-form-half-right">
			 <span><?php _e('Phone *', 'gdlr-hotel'); ?></span>
			 <input type="text" name="phone" value="" />
		</p>
		<div class="clear"></div>
		<p class="gdlr-form-half-left">
			<span><?php _e('Address', 'gdlr-hotel'); ?></span>
			<textarea name="address" ></textarea>
		</p>
		<p class="gdlr-form-half-right">
			<span><?php _e('Additional Note', 'gdlr-hotel'); ?></span>
			<textarea name="additional-note" ></textarea>
		</p>
		<div class="clear"></div>
		<p class="gdlr-form-coupon">
			<span><?php _e('Coupon Code', 'gdlr-hotel'); ?></span>
			<input type="text" name="coupon" value="" />
		</p>
		<div class="clear"></div>
		<div class="gdlr-error-message"></div>
		
		<a class="gdlr-button with-border gdlr-booking-contact-submit"><?php _e('Book now by email and we will contact you back.', 'gdlr-hotel'); ?></a>
		
		<?php 
			if( $hotel_option['payment-method'] == 'instant' ){ 
				echo '<div class="gdlr-booking-contact-or">' . __('Or', 'gdlr-hotel');
				echo '<div class="gdlr-booking-contact-or-divider gdlr-left"></div>';
				echo '<div class="gdlr-booking-contact-or-divider gdlr-right"></div>';
				echo '</div>';
			
				if( empty($hotel_option['instant-payment-method']) ){
					$hotel_option['instant-payment-method'] = array('paypal', 'stripe', 'paymill', 'authorize');
				}
				
				if( sizeof($hotel_option['instant-payment-method']) > 1 ){
					echo '<div class="gdlr-payment-method" >';
					foreach( $hotel_option['instant-payment-method'] as $key => $payment_method ){
						echo '<label ' . (($key == 0)? 'class="gdlr-active"':'') . ' >';
						echo '<input type="radio" name="payment-method" value="' . $payment_method . '" ' . (($key == 0)? 'checked':'') . ' />';
						echo '<img src="' . plugins_url('../images/' . $payment_method . '.png', __FILE__) . '" alt="" />';
						echo '</label>';
					}
					echo '</div>';
				}else{
					echo '<input type="hidden" name="payment-method" value="' . $hotel_option['instant-payment-method'][0] . '" />';
				}
				echo '<a class="gdlr-button with-border gdlr-booking-payment-submit">' . __('Pay Now', 'gdlr-hotel') . '</a>';
			}
		?>		
	</form>
</div>
<?php	
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		}
	}
	
		// booking room style
	if( !function_exists('gdlr_booking_complete_message') ){
		function gdlr_booking_complete_message(){
			global $hotel_option;
			
			if( !empty($_GET['response_code']) && !empty($_GET['response_reason_text']) ){
				$ret  = '<div class="gdlr-booking-failed">';
				$ret .= '<div class="gdlr-booking-failed-title" >';
				$ret .= __('Payment Failed', 'gdlr-hotel');
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-booking-failed-caption" >';
				$ret .= '<span>' . $_GET['response_code'] . '</span> '; 
				$ret .= $_GET['response_reason_text']; 
				$ret .= '</div>';
				$ret .= '</div>';
			}else{
				$ret  = '<div class="gdlr-booking-complete">';
				$ret .= '<div class="gdlr-booking-complete-title" >';
				$ret .= __('Reservation Completed!', 'gdlr-hotel');
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-booking-complete-caption" >';
				$ret .= __('Your reservation details have just been sent to your email. If you have any question, please don\'t hesitate to contact us. Thank you!', 'gdlr-hotel'); 
				$ret .= '</div>';
				
				if( !empty($hotel_option['booking-complete-contact']) ){
					$ret .= '<div class="gdlr-booking-complete-additional" >' . gdlr_escape_string($hotel_option['booking-complete-contact']) . '</div>';
				}
			}
			$ret .= '</div>';
			return $ret;
		}
	}
		
?>