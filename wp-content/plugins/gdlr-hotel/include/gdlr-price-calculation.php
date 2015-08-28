<?php
	/*	
	*	Goodlayers Price Calculation File
	*/	

	if( !function_exists('gdlr_day_count') ){
		function gdlr_day_count($from, $to) {
			global $hotel_option;

			$workingDays = array(1, 2, 3, 4, 5);
			
			$hotel_option['special-season-date'] = str_replace(' ', '', $hotel_option['special-season-date']);
			$holidayDays = explode(',', $hotel_option['special-season-date']); //'*-12-25';
			
			$from = new DateTime($from);
			$to = new DateTime($to);
			$interval = new DateInterval('P1D');
			$periods = new DatePeriod($from, $interval, $to);

			$weekday = 0;
			$weekend = 0;
			$ss_weekday = 0;
			$ss_weekend = 0;

			foreach($periods as $period){
				$period_format = array($period->format('Y-m-d'), $period->format('*-m-d'),
					$period->format('Y-*-d'), $period->format('Y-m-*'), $period->format('*-*-d'), 
					$period->format('*-m-*'));
				$ss_intersect = array_intersect($holidayDays, $period_format);
				if( !empty($ss_intersect) ){
					$special_season = true;
				}else{
					$special_season = false;
				}
				
				if( in_array($period->format('N'), $workingDays) ){
					if( $special_season ){
						$ss_weekday++;
					}else{
						$weekday++;
					}
				}else{
					if( $special_season ){
						$ss_weekend++;
					}else{
						$weekend++;
					}
				}
			}
			return array(
				'weekday'=>$weekday,
				'weekend'=>$weekend,
				'ss_weekday'=>$ss_weekday,
				'ss_weekend'=>$ss_weekend,
			);
		}
	}
	
	if( !function_exists('gdlr_get_booking_price') ){
		function gdlr_get_booking_price($option){
			$ret = array(
				'adult-num'=>0, 'child-num'=>0,
				'weekday-night'=>0, 'weekend-night'=>0,
				'ss_weekday-night'=>0, 'ss_weekend-night'=>0,
			
				'base'=>0, 'base-weekend'=>0,
				'adult'=>0, 'adult-weekend'=>0,
				'child'=>0, 'child-weekend'=>0,	

				'ss_base'=>0, 'ss_base-weekend'=>0,
				'ss_adult'=>0, 'ss_adult-weekend'=>0,
				'ss_child'=>0, 'ss_child-weekend'=>0,
				
				'total'=>0
			);
			
			$date_count = gdlr_day_count($option['data']['check-in'], $option['data']['check-out']);
			$ret['weekday-night'] = $date_count['weekday'];
			$ret['weekend-night'] = $date_count['weekend'];
			$ret['ss_weekday-night'] = $date_count['ss_weekday'];
			$ret['ss_weekend-night'] = $date_count['ss_weekend'];
			
			$room_base_num = intval($option['room-base-price-number-people']);
			$adult_num = intval($option['data']['adult']);
			$child_num = intval($option['data']['children']);
			
			if( $room_base_num >= $adult_num ){
				$room_base_num = $room_base_num - $adult_num; 
				if( $room_base_num < $child_num ){
					$ret['child-num'] = $child_num - $room_base_num;
				}
			}else{
				$adult_num = $adult_num - $room_base_num;
				$ret['adult-num'] = $adult_num;
				$ret['child-num'] = $child_num;
			}
			
			$ret['base'] = $ret['weekday-night'] * floatval($option['room-base-price']);
			$ret['base-weekend'] = $ret['weekend-night'] * floatval($option['room-base-price-weekend']);
			$ret['adult'] = $ret['adult-num'] * $ret['weekday-night'] * floatval($option['adult-price-weekday']);
			$ret['adult-weekend'] = $ret['adult-num'] * $ret['weekend-night'] * floatval($option['adult-price-weekend']);
			$ret['child'] = $ret['child-num'] * $ret['weekday-night'] * floatval($option['children-price-weekday']);
			$ret['child-weekend'] = $ret['child-num'] * $ret['weekend-night'] * floatval($option['children-price-weekend']);
			
			$option['ss-room-base-price'] = empty($option['ss-room-base-price'])? 0: $option['ss-room-base-price'];
			$option['ss-room-base-price-weekend'] = empty($option['ss-room-base-price-weekend'])? 0: $option['ss-room-base-price-weekend'];
			$option['ss-adult-price-weekday'] = empty($option['ss-adult-price-weekday'])? 0: $option['ss-adult-price-weekday'];
			$option['ss-adult-price-weekend'] = empty($option['ss-adult-price-weekend'])? 0: $option['ss-adult-price-weekend'];
			$option['ss-children-price-weekday'] = empty($option['ss-children-price-weekday'])? 0: $option['ss-children-price-weekday'];
			$option['ss-children-price-weekend'] = empty($option['ss-children-price-weekend'])? 0: $option['ss-children-price-weekend'];
			
			$ret['ss_base'] = $ret['ss_weekday-night'] * floatval($option['ss-room-base-price']);
			$ret['ss_base-weekend'] = $ret['ss_weekend-night'] * floatval($option['ss-room-base-price-weekend']);
			$ret['ss_adult'] = $ret['adult-num'] * $ret['ss_weekday-night'] * floatval($option['ss-adult-price-weekday']);
			$ret['ss_adult-weekend'] = $ret['adult-num'] * $ret['ss_weekend-night'] * floatval($option['ss-adult-price-weekend']);
			$ret['ss_child'] = $ret['child-num'] * $ret['ss_weekday-night'] * floatval($option['ss-children-price-weekday']);
			$ret['ss_child-weekend'] = $ret['child-num'] * $ret['ss_weekend-night'] * floatval($option['ss-children-price-weekend']);
			
			$ret['total']  = $ret['base'] + $ret['base-weekend'] + $ret['adult'] + $ret['adult-weekend'] + $ret['child'] + $ret['child-weekend'];
			$ret['total'] += $ret['ss_base'] + $ret['ss_base-weekend'] + $ret['ss_adult'] + $ret['ss_adult-weekend'] + $ret['ss_child'] + $ret['ss_child-weekend'];

			return $ret;
		}
	}
	
	// get price breakdown popup
	if( !function_exists('get_price_breakdown_popup') ){
		function get_price_breakdown_popup($post_option){
			$price = gdlr_get_booking_price($post_option);
			
			$weekday_title  = '<span>x ' . $price['weekday-night'] . ' ' . __('Night', 'gdlr-hotel') . (($price['weekday-night']>1)? 's': '');
			$weekday_title .= ' ' . __('(Weekday)', 'gdlr-hotel') . '</span>';
			$weekend_title  = '<span>x ' . $price['weekend-night'] . ' ' . __('Night', 'gdlr-hotel') . (($price['weekday-night']>1)? 's': '');
			$weekend_title .= ' ' . __('(Weekend)', 'gdlr-hotel') . '</span>';				
			
			$ss_weekday_title  = '<span>x ' . $price['ss_weekday-night'] . ' ' . __('Night', 'gdlr-hotel') . (($price['ss_weekday-night']>1)? 's': '');
			$ss_weekday_title .= ' ' . __('(Weekday)', 'gdlr-hotel') . '</span>';
			$ss_weekend_title  = '<span>x ' . $price['ss_weekend-night'] . ' ' . __('Night', 'gdlr-hotel') . (($price['ss_weekday-night']>1)? 's': '');
			$ss_weekend_title .= ' ' . __('(Weekend)', 'gdlr-hotel') . '</span>';			
			
			$price_title = array(
				'base'=> __('Base Price', 'gdlr-hotel') . $weekday_title,
				'base-weekend'=> __('Base Price', 'gdlr-hotel') . $weekend_title,
				'ss_base'=> __('Special Season Base Price', 'gdlr-hotel') . $ss_weekday_title,
				'ss_base-weekend'=> __('Special Season Base Price', 'gdlr-hotel') . $ss_weekend_title,
				'adult'=> $price['adult-num'] . ' ' . __('Adult', 'gdlr-hotel') . $weekday_title,
				'adult-weekend'=> $price['adult-num'] . ' ' . __('Adult', 'gdlr-hotel') . $weekend_title,
				'ss_adult'=> $price['adult-num'] . ' ' . __('Special Season Adult', 'gdlr-hotel') . $ss_weekday_title,
				'ss_adult-weekend'=> $price['adult-num'] . ' ' . __('Special Season Adult', 'gdlr-hotel') . $ss_weekend_title,
				'child'=> $price['child-num'] . ' ' . __('Children', 'gdlr-hotel') . $weekday_title,
				'child-weekend'=> $price['child-num'] . ' ' .__('Children', 'gdlr-hotel') . $weekend_title,				
				'ss_child'=> $price['child-num'] . ' ' . __('Special Season Children', 'gdlr-hotel') . $ss_weekday_title,
				'ss_child-weekend'=> $price['child-num'] . ' ' .__('Special Season Children', 'gdlr-hotel') . $ss_weekend_title
			);
			
			$ret  = '<div class="price-breakdown-wrapper">';
			$ret .= '<div class="price-breakdown-close"></div>';
			$ret .= '<div class="price-breakdown-content">';
			
			foreach( $price_title as $slug => $title ){
				if( !empty($price[$slug]) ){
					$ret .= '<div class="price-breakdown-info">';
					$ret .= '<span class="gdlr-head">' . $price_title[$slug] . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($price[$slug]) . '</span>';
					$ret .= '</div>';
				}
			}			
			
			$ret .= '<div class="price-breakdown-total">';
			$ret .= '<span class="gdlr-head">' . __('Total', 'gdlr-hotel');
			$ret .= '<span class="">' . __('*vat is not included yet', 'gdlr-hotel') . '</span>';
			$ret .= '</span>';
			$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($price['total']) . '</span>';
			$ret .= '</div>';
			
			$ret .= '</div>'; // price-breakdown-content
			$ret .= '</div>'; // price-breakdown-wrapper
			
			return $ret;
		}
	}	
	
	// get total price for one booking
	if( !function_exists('gdlr_get_booking_total_price') ){
		function gdlr_get_booking_total_price($data, $coupon = ''){	
			global $hotel_option;
			$total_price = 0;

			for($i=0; $i<intval($data['gdlr-room-number']); $i++){
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta($data['gdlr-room-id'][$i], 'post-option', true)), true);
				$post_option['data'] = array(
					'check-in'=> $data['gdlr-check-in'],
					'check-out'=> $data['gdlr-check-out'],
					'adult'=> $data['gdlr-adult-number'][$i], 
					'children'=> $data['gdlr-children-number'][$i]
				);
				$price = gdlr_get_booking_price($post_option);
				$total_price += $price['total'];
			}
			
			// coupon discount
			$discount_price = gdlr_get_coupon_discount($data, $coupon);
			$total_price -= $discount_price;
			
			if( !empty($hotel_option['booking-vat-amount']) ){
				$vat_amount = ($total_price * floatval($hotel_option['booking-vat-amount'])) / 100;
				$total_price += $vat_amount;
			}
			
			$pay_amount = $total_price;
			if( !empty($hotel_option['booking-deposit-amount']) && $data['pay_deposit'] == 'true' ){
				$pay_amount = ($total_price * floatval($hotel_option['booking-deposit-amount'])) / 100;
			}
			return array('total_price'=> $total_price, 'pay_amount'=> $pay_amount);	
		}
	}
	
	if( !function_exists('gdlr_get_coupon_discount') ){
		function gdlr_get_coupon_discount($data, $coupon = ''){
			if( empty($coupon) ) return 0;
			
			$posts = get_posts(array('post_type'=>'coupon', 'posts_per_page'=>1, 'meta_key'=>'gdlr-coupon-code', 'meta_value'=>$coupon));
			$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta($posts[0]->ID, 'post-option', true)), true);
			
			// expire date
			if( !empty($post_option['coupon-expiry']) && strtotime(date("Y-m-d")) > strtotime($post_option['coupon-expiry']) ) return 0;
			
			// available num
			$coupon_num = get_post_meta($posts[0]->ID, 'gdlr-coupon-num', true);
			$coupon_num = empty($coupon_num)? 0: intval($coupon_num);
			if( $post_option['coupon-amount'] != -1 && $coupon_num >= $post_option['coupon-amount'] ) return 0;
			update_post_meta($posts[0]->ID, 'gdlr-coupon-num', $coupon_num + 1);

			// specify room
			if( !empty($post_option['specify-room']) ){
				$room_specify = explode(',', $post_option['specify-room']);
				foreach($data['gdlr-room-id'] as $key => $room_id){
					if( !in_array($room_id, $room_specify) ){
						unset($data['gdlr-room-id'][$key]);
					}
				}
			}
			if( empty($data['gdlr-room-id']) ) return 0;
			
			// number of 
			if( $post_option['coupon-discount-type'] == 'amount' ){
				return $post_option['coupon-discount-amount'];
			}else{
				// percent
				$total_price = 0;
				
				for($i=0; $i<sizeOf($data['gdlr-room-id']); $i++){
					$room_option = json_decode(gdlr_decode_preventslashes(get_post_meta($data['gdlr-room-id'][$i], 'post-option', true)), true);
					$room_option['data'] = array(
						'check-in'=> $data['gdlr-check-in'],
						'check-out'=> $data['gdlr-check-out'],
						'adult'=> $data['gdlr-adult-number'][$i], 
						'children'=> $data['gdlr-children-number'][$i]
					);
					$price = gdlr_get_booking_price($room_option);
					$total_price += $price['total'];
				}		

				return $total_price * floatval($post_option['coupon-discount-amount']) / 100;
			}
		}
	}

?>