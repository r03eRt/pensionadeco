<?php
	/*	
	*	Goodlayers Utility File
	*/	
	
	// send the mail
	if( !function_exists('gdlr_hotel_mail') ){
		function gdlr_hotel_mail($recipient, $title, $message){
			global $hotel_option;

			$headers = 'From: ' . $hotel_option['recipient-name'] . ' <' . $hotel_option['recipient-mail'] . '>' . "\r\n";
			$headers = $headers . 'Content-Type: text/plain; charset=UTF-8 ' . " \r\n";
			wp_mail($recipient, $title, $message, $headers);		
		}
	}
	
	if( !function_exists('gdlr_hotel_mail_content') ){
		function gdlr_hotel_mail_content($contact, $data, $payment_info, $price){
			$content  = "Contact Info \n";
			$content .= "Name : {$contact['first_name']}\n";
			$content .= "Last Name : {$contact['last_name']}\n";
			$content .= "Phone : {$contact['phone']}\n";
			$content .= "Email : {$contact['email']}\n";
			$content .= "Address : {$contact['address']}\n";
			$content .= "Additional Note : {$contact['additional-note']}\n";
			$content .= "Coupon : {$contact['coupon']}\n";
			if( !empty($data['gdlr-hotel-branches']) ){
				$term = get_term_by('id', $data['gdlr-hotel-branches'], 'room_category');
				$content .= "Branches : {$term->name}\n";
				
				$category_meta = get_option('gdlr_hotel_branch', array());
				if( !empty($category_meta[$term->slug]['content']) ){
					$content .= "Location : {$category_meta[$term->slug]['content']}\n";
				}
			}
			$content .= "\n";
			
			$content .= "Room Information \n";
			for( $i=0; $i<intval($data['gdlr-room-number']); $i++){
				$room_num = $i+1;
				
				$content .= "Room {$room_num} : " . get_the_title($data['gdlr-room-id'][$i]) . " \n";
				$content .= "Adult : {$data['gdlr-adult-number'][$i]}\n";
				$content .= "Children : {$data['gdlr-children-number'][$i]}\n";
			}			
			$content .= "Check In : {$data['gdlr-check-in']} \n";
			$content .= "Check Out : {$data['gdlr-check-out']} \n";
			$content .= "\n";
			
			$content .= "Payment Information \n";
			$content .= "Total Price : {$price['total_price']} \n";
			$content .= "Pay Amount : {$price['pay_amount']} \n";
			if( !empty($price['booking_code']) ){
				$content .= "Booking Code : {$price['booking_code']} \n";
			}
			if( !empty($contact['payment-method']) && !empty($payment_info) ){
				if( $contact['payment-method'] == 'stripe' ){
					$content .= "Payment Method : Stripe \n";
					$content .= "Transaction ID : {$payment_info['balance_transaction']} \n";
				}else if( $contact['payment-method'] == 'paypal' ){
					$content .= "Payment Method : Paypal \n";
					$content .= "Transaction ID : {$payment_info['txn_id']} \n";
				}else if( $contact['payment-method'] == 'paymill' ){
					$content .= "Payment Method : Paymill \n";
					$content .= "Transaction ID : {$payment_info->getId()} \n";
				}else if( $contact['payment-method'] == 'authorize' ){
					$content .= "Payment Method : Authorize \n";
					$content .= "Transaction ID : {$payment_info->transaction_id} \n";
				}
			}
			
			return $content;
		}
	}	
	
	// format the currency
	if( !function_exists('gdlr_hotel_money_format') ){
		function gdlr_hotel_money_format($amount, $format = ''){
			if( empty($format) ){
				global $hotel_option;
				$format = $hotel_option['booking-money-format'];
			}
			if( strpos($format, 'NUMBER') === false ){
				$format .= 'NUMBER';
			}
			return str_replace('NUMBER', number_format_i18n($amount, 2), $format);
		}
	}
	
	// validate the contact form fields
	if( !function_exists('gdlr_validate_contact_form') ){
		function gdlr_validate_contact_form( $contact ){
			if( empty($contact['first_name']) || empty($contact['last_name']) || 
				empty($contact['email']) || empty($contact['phone']) ){
				return __('Please fill all required fields.', 'gdlr-hotel');
			}
			if( !is_email($contact['email']) ){
				return __('Email is invalid.', 'gdlr-hotel');
			}
			return false;
		}
	}
	
	// save the booking any payment to database
	if( !function_exists('gdlr_save_booking_db') ){
		function gdlr_insert_booking_db($options){
			global $wpdb, $hotel_option;
			$pricing = gdlr_get_booking_total_price($options['data'], $options['contact']['coupon']);
			
			$customer_code  = $hotel_option['booking-code-prefix'];
			$customer_code .= mb_substr($options['contact']['first_name'], 0, 1);
			$customer_code .= mb_substr($options['contact']['last_name'], 0, 1);
			$code_count = get_option('gdlr-customer-code-count', 0);
			update_option('gdlr-customer-code-count', $code_count+1);
			$customer_code  = strtoupper($customer_code . $code_count);
			
			$result = $wpdb->insert( $wpdb->prefix . 'gdlr_hotel_payment',
				array(
					'total_price'=>$pricing['total_price'], 
					'pay_amount'=>$pricing['pay_amount'], 
					'booking_data'=>serialize($options['data']), 
					'contact_info'=>serialize($options['contact']), 
					'payment_status'=>$options['payment_status'], 
					'payment_date'=>date('Y-m-d H:i:s'),
					'customer_code'=>$customer_code
				),
				array('%f', '%f', '%s', '%s', '%s', '%s', '%s')
			);

			if( $result > 0 ){
				$payment_id = $wpdb->insert_id;
				
				for( $i=0; $i<$options['data']['gdlr-room-number']; $i++ ){
					$wpdb->insert($wpdb->prefix . 'gdlr_hotel_booking',
						array(
							'payment_id'=>$payment_id, 
							'room_id'=>$options['data']['gdlr-room-id'][$i], 
							'start_date'=>$options['data']['gdlr-check-in'], 
							'end_date'=>$options['data']['gdlr-check-out']
						),
						array('%s', '%s', '%s', '%s')
					);
				}
			}
			
			return array(
				'invoice' => $payment_id, 
				'total-price' => $pricing['total_price'],
				'pay-amount' => $pricing['pay_amount'],
				'code' => $customer_code
			);
		}
	}
	
?>