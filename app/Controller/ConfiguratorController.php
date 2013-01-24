<?php
/**
 * Main configurator controller.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppController', 'Controller');

class ConfiguratorController extends AppController
{
	public $uses = array('UberApi', 'UberServicePlan', 'Cart', 'CartItem', 'CartItemUpgrade', 'CartItemHostname', 'Utilities');
	
	public $components = array('Session', 'UbersmithCart', 'SWFTool');
	
	public $helpers = array('Session');
	
	public $name = 'Configurator';
	
	public $service_plans = null;
	
	public $service_plan_id = 0;
	
	public $cart_item = 0;
	
	public function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->layout = 'default';
	}
	
	public function logout()
	{
		$this->Session->destroy();
		header('Location: /', true, 302);
		exit;
	}
	
	public function configure($step = 0, $service_plan_id = 0, $cart_item_id = 0)
	{
		$this->service_plan_id = $service_plan_id;
		
		$this->cart_item_id = $cart_item_id;
		
		switch ($step) {
			case 'cart':
				$step = 2;
				break;
			case 'account-info':
				$step = 3;
				break;
			case 'payment-method':
				$step = 4;
				break;
			case 'order-review':
				$step = 5;
				break;
			case 'order-complete':
				$step = 6;
				break;
		}
		
		if (!is_numeric($step)) {
			throw new BadRequestException(__('Invalid step specified'));
		}
		
		$method = 'step_' . $step;
		
		$this->view = $method;
		
		$this->$method();
	}
	
	public function create_cart_review_google_analytics($cart = null)
	{
		if (empty($cart)) {
			$session_cart = $this->Session->read('Cart');
			if (!empty($session_cart)) {
				$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
			}
		}
		
		if (empty($cart)) {
			$google_analytics_info = array(
				'items' => array(),
				'monthly_total' => 0,
				'setup_fee_total' => 0,
				'total_due_today' => 0,
			);
			return $google_analytics_info;
		}
		
		$monthly_price_subtotal = 0;
		$setup_fee_subtotal = 0;
		
		$monthly_total = 0;
		$setup_fee_total = 0;
		
		$items_temp = $items = array();
		
		foreach ($cart['CartItem'] as $key => $cart_item) {
			foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
				$selected_upgrades[$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
			}
			
			$item = new stdClass;
			
			$service_plan_id = $cart_item['service_plan_id'];
			
			if (!in_array($service_plan_id, $this->UbersmithCart->service_plans())) {
				continue;
			}
			
			$service_plan_api_array = array(
				'method' => 'uber.service_plan_get',
				'plan_id' => $service_plan_id,
			);
			$uber_service_plan = $this->UberApi->call_api($service_plan_api_array);
			if (empty($uber_service_plan->error_code)) {
				$service_plan = $uber_service_plan->data;
			}
			else {
				continue;
			}
			
			$item->id = $cart_item['id'];
			$item->service_plan_id = $cart_item['service_plan_id'];
			foreach ($service_plan->pricing as $service_plan_pricing) {
				if ($service_plan_pricing->api_label != 'monthly') {
					continue;
				}
				$item->monthly_price = $service_plan_pricing->price;
				$item->setup_fee = $service_plan_pricing->setup_fee;
			}
			$item->name = $service_plan->title;
			$item->quantity = $cart_item['quantity'];
			
			$monthly_price_subtotal += $item->monthly_price;
			$setup_fee_subtotal += $item->setup_fee;
			
			foreach ($service_plan->upgrades as $upgrade_id => $upgrade) {
				if (empty($upgrade->options) || !empty($upgrade->spg_data->hide_option)) {
					continue;
				}
				foreach ($upgrade->options as $upgrade_option_id => $upgrade_option) {
					if ($selected_upgrades[$upgrade_id] == $upgrade_option_id) {
						$setup_fee_subtotal += $upgrade_option->spo_setup_fee;
						$item->setup_fee += $upgrade_option->spo_setup_fee;
						foreach ($upgrade_option->pricing as $upgrade_pricing) {
							if ($upgrade_pricing->api_label == 'monthly') {
								$monthly_price_subtotal += $upgrade_pricing->price;
								$item->monthly_price += $upgrade_pricing->price;
							}
						}
					}
				}
			}
			
			$item->monthly_price_subtotal = ($item->monthly_price * $item->quantity);
			$item->setup_fee_subtotal = ($item->setup_fee * $item->quantity);
			
			$monthly_total += $item->monthly_price_subtotal;
			$setup_fee_total += $item->setup_fee_subtotal;
			
			$items_temp[] = $item;
		}
		
		// group by service_plan_id for google analytics' sake
		foreach ($items_temp as $item) {
			// the first time, or there's only one
			if (empty($items[$item->service_plan_id])) {
				unset($item->monthly_price, $item->setup_fee);
				$items[$item->service_plan_id] = $item;
			}
			else {
				// these values are irrelevant in this context
				if (isset($items[$item->service_plan_id]->monthly_price)) {
					unset($items[$item->service_plan_id]->monthly_price, $items[$item->service_plan_id]->setup_fee);
				}
				// combine with the new one
				$items[$item->service_plan_id]->quantity += $item->quantity;
				$items[$item->service_plan_id]->monthly_price_subtotal += $item->monthly_price_subtotal;
				$items[$item->service_plan_id]->setup_fee_subtotal += $item->setup_fee_subtotal;
			}
		}
		
		foreach ($items as $service_plan_id => $item) {
			$items[$item->service_plan_id]->monthly_price_average = round($item->monthly_price_subtotal / $item->quantity, 2);
		}
		
		$google_analytics_info = array(
			'items' => $items,
			'monthly_total' => $monthly_total,
			'setup_fee_total' => $setup_fee_total,
			'total_due_today' => ($monthly_total + $setup_fee_total),
		);
		
		return $google_analytics_info;
	}
	
	public function create_cart_review($cart = null)
	{
		if (empty($cart)) {
			$session_cart = $this->Session->read('Cart');
			if (!empty($session_cart)) {
				$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
			}
		}
		
		if (empty($cart)) {
			$this->set('monthly_total', 0);
			$this->set('setup_fee_total', 0);
			$this->set('total_due_today', 0);
			$this->set('items', array());
			return;
		}
		
		$monthly_price_subtotal = 0;
		$setup_fee_subtotal = 0;
		
		$monthly_one_time_total = 0;
		$monthly_recurring_total = 0;
		$setup_fee_total = 0;
		
		$items = array();
		
		$item->monthly_price_recurring_discount_amount = $item->monthly_price_one_time_discount_amount = $item->setup_fee_discount_amount = 0;
		
		foreach ($cart['CartItem'] as $key => $cart_item) {
			foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
				$selected_upgrades[$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
			}
			
			$item = new stdClass;
			
			$service_plan_id = $cart_item['service_plan_id'];
			
			if (!in_array($service_plan_id, $this->UbersmithCart->service_plans())) {
				throw new NotFoundException(__('Service plan not valid'));
			}
			
			$service_plan_api_array = array(
				'method' => 'uber.service_plan_get',
				'plan_id' => $service_plan_id,
			);
			$uber_service_plan = $this->UberApi->call_api($service_plan_api_array);
			if (empty($uber_service_plan->error_code)) {
				$service_plan = $uber_service_plan->data;
			}
			else {
				throw new NotFoundException(__('Service plan not valid'));
			}
			
			$item->id = $cart_item['id'];
			$item->service_plan_id = $cart_item['service_plan_id'];
			foreach ($service_plan->pricing as $service_plan_pricing) {
				if ($service_plan_pricing->api_label != 'monthly') {
					continue;
				}
				$item->monthly_price = $service_plan_pricing->price;
				$item->setup_fee = $service_plan_pricing->setup_fee;
			}
			$item->name = $service_plan->title;
			$item->quantity = $cart_item['quantity'];
			
			$item->monthly_price_discount_amount = 0;
			$item->monthly_price_recurring_discount_amount = 0;
			$item->monthly_price_one_time_discount_amount = 0;
			$item->setup_fee_discount_amount = 0;
			
			$monthly_price_subtotal += $item->monthly_price;
			$setup_fee_subtotal += $item->setup_fee;
			
			// this is an EXTREMELY convoluted way to get the proper ordering of upgrade options in the cart since we're looping through service plan upgrades and options twice
			$coupons = $this->UbersmithCart->coupons();
			$item->upgrades = array();
			foreach ($selected_upgrades as $upgrade_id => $upgrade_option_id) {
				$selected_upgrade = new stdClass;
				foreach ($service_plan->upgrades as $key => $upgrade) {
					if (!empty($upgrade->spg_data->hide_option)) {
						continue;
					}
					$check_upsell = false;
					foreach ($upgrade->spg_data as $key1 => $value1) {
						if ($key1 == 'callout_upsell' && preg_match('/(yes|true|1)/i', $value1) > 0) {
							$check_upsell = true;
						}
					}
					$upgrade_needs_callout = false;
					foreach ($upgrade->options as $upgrade_option_id_2 => $upgrade_option) {
						if ($selected_upgrades[$upgrade_id] != $upgrade_option_id_2) {
							continue;
						}
						if ($check_upsell == true) {
							$selected_upgrade->is_callout_upsell = true;
							foreach ($upgrade_option->spo_data as $key2 => $value2) {
								if ($key2 == 'is_callout_upsell_option' && preg_match('/(yes|true|1)/i', $value2) > 0) {
									$upgrade_needs_callout = true;
								}
								if ($key2 == 'callout_text') {
									$selected_upgrade->callout_text = $value2;
								}
							}
						}
						if (empty($selected_upgrade->is_callout_upsell)) {
							foreach ($upgrade_option->pricing as $upgrade_pricing) {
								if ($upgrade_pricing->api_label == 'monthly') {
									$selected_upgrade->monthly_price = $upgrade_pricing->price;
								}
							}
						}
						$selected_upgrade->id = $upgrade->spg_id;
						$selected_upgrade->category = $upgrade->spg_name;
						$selected_upgrade->value = $upgrade_option->spo_description;
					}
					if (!$upgrade_needs_callout) {
						continue;
					}
					$upgrade_options = array();
					foreach ($upgrade->options as $upgrade_option_id_3 => $service_plan_upgrade_option) {
						$upgrade_option = new stdClass;
						$upgrade_option->id = $upgrade_option_id_3;
						$upgrade_option->name = $service_plan_upgrade_option->spo_description;
						$upgrade_option->description = $service_plan_upgrade_option->spo_desc;
						$upgrade_option->setup_fee = $service_plan_upgrade_option->spo_setup_fee;
						foreach ($service_plan_upgrade_option->pricing as $upgrade_pricing) {
							if ($upgrade_pricing->api_label == 'monthly') {
								$upgrade_option->monthly_price = $upgrade_pricing->price;
							}
						}
						if ($upgrade_option_id == $upgrade_option_id_3) {
							$default_upgrade_option_id3 = $upgrade_option_id_3;
							$upgrade_option->is_default_upgrade_option = true;
						}
						$upgrade_options[$upgrade_option->id] = $upgrade_option;
						$upgrade_options[$upgrade_option->id]->monthly_price_discount_amount = 0;
						$upgrade_options[$upgrade_option->id]->monthly_price_one_time_discount_amount = 0;
						$upgrade_options[$upgrade_option->id]->monthly_price_recurring_discount_amount = 0;
						$upgrade_options[$upgrade_option->id]->setup_fee_discount_amount = 0;
						if (!empty($coupons)) {
							foreach ($coupons as $plan_id => $coupon) {
								$coupon_id = $coupon->coupon->coupon_id;
								$coupon_option_array = array();
								if (!empty($coupon->options)) {
									foreach ($coupon->options as $coupon_upgrade_option_id => $coupon_option) {
										$coupon_option_array[$coupon_upgrade_option_id] = $coupon_option;
									}
								}
								if (!array_key_exists($upgrade_option->id, $coupon_option_array)) {
									continue;
								}
								if ($coupon_option_array[$upgrade_option->id]->discount == 0 && $coupon_option_array[$upgrade_option->id]->setup_discount == 0) {
									continue;
								}
								if ($coupon_option_array[$upgrade_option->id]->discount > 0) {
									// 0 = percent, 1 = dollar
									$upgrade_options[$upgrade_option->id]->monthly_price_discount_type = ($coupon_option_array[$upgrade_option->id]->discount_type == '1') ? 'dollar' : 'percent';
									if ($upgrade_options[$upgrade_option->id]->monthly_price_discount_type == 'dollar') {
										$discount_amount = $coupon_option_array[$upgrade_option->id]->discount;
										$upgrade_options[$upgrade_option->id]->monthly_price_discount_amount += $discount_amount;
									}
									else {
										$discount_amount = ($upgrade_options[$upgrade_option->id]->monthly_price * $coupon_option_array[$upgrade_option->id]->discount / 100);
										$upgrade_options[$upgrade_option->id]->monthly_price_discount_amount += $discount_amount;
										if ($upgrade_options[$upgrade_option->id]->monthly_price_discount_amount < 0) {
											// let's not get crazy with negative numbers
											$upgrade_options[$upgrade_option->id]->monthly_price_discount_amount = 0;
										}
									}
									if ($coupon->coupon->recurring == '1') {
										$upgrade_options[$upgrade_option->id]->monthly_price_recurring_discount_amount += $discount_amount;
									}
									$upgrade_options[$upgrade_option->id]->monthly_price_one_time_discount_amount += $discount_amount;
								}
								if ($coupon_option_array[$upgrade_option->id]->setup_discount > 0) {
									// 0 = percent, 1 = dollar
									$upgrade_options[$upgrade_option->id]->setup_fee_discount_type = ($coupon_option_array[$upgrade_option->id]->setup_discount_type == '1') ? 'dollar' : 'percent';
									if ($upgrade_options[$upgrade_option->id]->setup_fee_discount_type == 'dollar') {
										$upgrade_options[$upgrade_option->id]->setup_fee_discount_amount += $coupon_option_array[$upgrade_option->id]->setup_discount;
									}
									else {
										$upgrade_options[$upgrade_option->id]->setup_fee_discount_amount += ($upgrade_options[$upgrade_option->id]->setup_fee * $coupon_option_array[$upgrade_option->id]->setup_discount / 100);
										if ($upgrade_options[$upgrade_option->id]->setup_fee_discount_amount < 0) {
											// let's not get crazy with negative numbers
											$upgrade_options[$upgrade_option->id]->setup_fee_discount_amount = 0;
										}
									}
								}
							}
						}
					}
					$selected_upgrade->callout_upgrades = $upgrade_options;
					$selected_upgrade->default_callout_upgrade_option_id = @$default_upgrade_option_id3;
				}
				if (
					(
						!isset($selected_upgrade->monthly_price) ||
						(
							$selected_upgrade->monthly_price == 0 &&
							preg_match('/^none$/i', $selected_upgrade->value) > 0
						)
					) &&
					empty($selected_upgrade->is_callout_upsell)
				) {
					continue;
				}
				$item->upgrades[] = $selected_upgrade;
			}
			
			foreach ($service_plan->upgrades as $upgrade_id => $upgrade) {
				if (empty($upgrade->options) || !empty($upgrade->spg_data->hide_option)) {
					continue;
				}
				foreach ($upgrade->options as $upgrade_option_id => $upgrade_option) {
					if (!empty($selected_upgrades[$upgrade_id]) && $selected_upgrades[$upgrade_id] != $upgrade_option_id) {
						continue;
					}
					$setup_fee_subtotal += $upgrade_option->spo_setup_fee;
					$item->setup_fee += $upgrade_option->spo_setup_fee;
					foreach ($upgrade_option->pricing as $upgrade_pricing) {
						if ($upgrade_pricing->api_label == 'monthly') {
							$monthly_price_subtotal += $upgrade_pricing->price;
							$item->monthly_price += $upgrade_pricing->price;
							$upgrade_monthly_price = $upgrade_pricing->price;
						}
					}
					if (!empty($coupons)) {
						foreach ($coupons as $plan_id => $coupon) {
							$coupon_id = $coupon->coupon->coupon_id;
							$coupon_option_array = array();
							if (!empty($coupon->options)) {
								foreach ($coupon->options as $coupon_upgrade_option_id => $coupon_option) {
									$coupon_option_array[$coupon_upgrade_option_id] = $coupon_option;
								}
							}
							if (!array_key_exists($upgrade_option_id, $coupon_option_array)) {
								continue;
							}
							if ($coupon_option_array[$upgrade_option_id]->discount == 0 && $coupon_option_array[$upgrade_option_id]->setup_discount == 0) {
								continue;
							}
							if ($coupon_option_array[$upgrade_option_id]->discount > 0) {
								// 0 = percent, 1 = dollar
								$monthly_price_discount_type = ($coupon_option_array[$upgrade_option_id]->discount_type == '1') ? 'dollar' : 'percent';
								if ($monthly_price_discount_type == 'dollar') {
									$discount_amount = $coupon_option_array[$upgrade_option_id]->discount;
									$item->monthly_price_discount_amount += $discount_amount;
								}
								else {
									$discount_amount = ($upgrade_monthly_price * $coupon_option_array[$upgrade_option_id]->discount / 100);
									if ($discount_amount > 0) {
										$item->monthly_price_discount_amount += $discount_amount;
									}
								}
								if ($coupon->coupon->recurring == '1') {
									$item->monthly_price_recurring_discount_amount += $discount_amount;
								}
								$item->monthly_price_one_time_discount_amount += $discount_amount;
							}
							if ($coupon_option_array[$upgrade_option_id]->setup_discount > 0) {
								// 0 = percent, 1 = dollar
								$upgrade_options[$upgrade_option_id]->setup_fee_discount_type = ($coupon_option_array[$upgrade_option_id]->setup_discount_type == '1') ? 'dollar' : 'percent';
								if ($upgrade_options[$upgrade_option_id]->setup_fee_discount_type == 'dollar') {
									$item->setup_fee_discount_amount += $coupon_option_array[$upgrade_option_id]->setup_discount;
								}
								else {
									$setup_fee_discount_amount += ($upgrade_option->spo_setup_fee * $coupon_option_array[$upgrade_option_id]->setup_discount / 100);
									if ($setup_fee_discount_amount > 0) {
										$item->setup_fee_discount_amount += $setup_fee_discount_amount;
									}
								}
							}
						}
					}
				}
			}
			
			if (!empty($cart_item['CartItemHostname'])) {
				$item->hostnames = array();
				foreach ($cart_item['CartItemHostname'] as $hk => $cart_item_hostname) {
					$item->hostnames[] = $cart_item_hostname['hostname'];
				}
			}
			
			if (!empty($coupons)) {
				foreach ($coupons as $plan_id => $coupon) {
					$coupon_id = $coupon->coupon->coupon_id;
					if (empty($coupon->coupon->discount_value) && empty($coupon->coupon->setup_discount_value)) {
						continue;
					}
					if ($coupon->coupon->discount_value > 0) {
						$discount_type = ($coupon->coupon->dollar == '1') ? 'dollar' : 'percent';
						if ($discount_type == 'dollar') {
							$discount_amount = $coupon->coupon->discount_value;
						}
						else {
							$discount_amount = ($item->monthly_price * $coupon->coupon->discount_value / 100);
						}
						if ($discount_amount > 0) {
							$item->monthly_price_discount_amount += $discount_amount;
							if ($coupon->coupon->recurring == '1') {
								$item->monthly_price_recurring_discount_amount += $discount_amount;
							}
							$item->monthly_price_one_time_discount_amount += $discount_amount;
						}
					}
					if ($coupon->coupon->setup_discount_value > 0) {
						$discount_type = ($coupon->coupon->setup_dollar == '1') ? 'dollar' : 'percent';
						if ($discount_type == 'dollar') {
							$discount_amount = $coupon->coupon->setup_discount_value;
						}
						else {
							$discount_amount = ($item->setup_fee * $coupon->coupon->setup_discount_value / 100);
						}
						if ($discount_amount > 0) {
							$item->setup_fee_discount_amount += $discount_amount;
						}
					}
				}
			}
			
			if (($item->setup_fee - $item->setup_fee_discount_amount) < 0) {
				$item->setup_fee_discount_amount = $item->setup_fee;
			}
			
			if (($item->monthly_price - $item->monthly_price_one_time_discount_amount) < 0) {
				$item->monthly_price_one_time_discount_amount = $item->monthly_price;
			}
			
			if (($item->monthly_price - $item->monthly_price_recurring_discount_amount) < 0) {
				$item->monthly_price_recurring_discount_amount = $item->monthly_price;
			}
			
			$item->monthly_price_one_time_discount_subtotal = ($item->monthly_price_one_time_discount_amount * $item->quantity);
			$item->monthly_price_recurring_discount_subtotal = ($item->monthly_price_recurring_discount_amount * $item->quantity);
			$item->setup_fee_discount_subtotal = ($item->setup_fee_discount_amount * $item->quantity);
			
			$item->monthly_price_subtotal = ($item->monthly_price * $item->quantity);
			$item->setup_fee_subtotal = ($item->setup_fee * $item->quantity);
			
			$monthly_one_time_total += $item->monthly_price_subtotal - $item->monthly_price_one_time_discount_subtotal;
			$monthly_recurring_total += $item->monthly_price_subtotal - $item->monthly_price_recurring_discount_subtotal;
			
			$setup_fee_total += $item->setup_fee_subtotal - $item->setup_fee_discount_subtotal;
			
			$items[] = $item;
		}
		
		$this->set('monthly_one_time_total', $monthly_one_time_total);
		$this->set('monthly_recurring_total', $monthly_recurring_total);
		$this->set('setup_fee_total', $setup_fee_total);
		$this->set('total_due_today', ($monthly_one_time_total + $setup_fee_total));
		
		$this->set('items', $items);
	}
	
	public function remove_from_cart($item_id =  0)
	{
		try {
			$this->UbersmithCart->remove_item(array('item_id' => $item_id));
		} catch (UberException $e) {
			throw new UberException($e->getMessage(), 1);
		}
		
		$this->Session->setFlash(__('Item removed from cart'), 'default', array('class' => 'info'));
		$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'cart'));
	}
	
	/**
	 * Perform luhn cc validation, as per http://en.wikipedia.org/wiki/Luhn_algorithm
	 * @author Nathan Goulding <ngoulding@ubersmith.com>
	 */
	public function validate_credit_card_luhn($number)
	{
		// turn our string into a proper array
		$number = str_split($number);
		
		// calculate its length
		$len = count($number) - 1;
		
		// if there aren't enough pieces to the number to have a checksum and at least one digit, we've failed
		if ($len < 1) {
			return false;
		}
		
		// pop the last digit off the end
		$checksum = array_pop($number);
		
		// perform luhn checksum calculation
		$luhn = (array_sum( // by adding up all the elements of the array
			array_map( // while performing some simple logic along the way
				function($k, $v) {
					if ($k % 2) {
						return (int) $v; // for every other, return the unmolested value
					} else {
						return array_sum(str_split((string) ($v * 2))); // for every other other, multiply it by two and add the digits
					}
				},
				range(0,$len), // by creating an array
				array_reverse($number) // that consists of the number sans checksum, backwards
			)
		) % 10); // (10 - $luhn) should match the checksum
		
		// ensure that $luhn != 0 because valid credit cards don't end in zeros
		return ((10 - $luhn == $checksum) && ($luhn != 0));
	}
	
	public function validate_credit_card_length($card_number)
	{
		$cards = array(
			array(
				'name' => 'amex',
				'length' => '15',
			),
			array (
				'name' => 'dinersclub',
				'length' => '14',
			),
			array (
				'name' => 'dinersclub',
				'length' => '14,16',
			),
			array (
				'name' => 'discover',
				'length' => '16',
			),
			array (
				'name' => 'dinersclub',
				'length' => '15',
			),
			array (
				'name' => 'jcb',
				'length' => '16',
			),
			array (
				'name' => 'maestro',
				'length' => '12,13,14,15,16,18,19',
			),
			array (
				'name' => 'mastercard',
				'length' => '16',
			),
			array (
				'name' => 'solo',
				'length' => '16,18,19',
			),
			array (
				'name' => 'switch',
				'length' => '16,18,19',
			),
			array (
				'name' => 'visa',
				'length' => '13,16',
			),
			array (
				'name' => 'visa',
				'length' => '16',
			),
			array (
				'name' => 'lasercard',
				'length' => '16,17,18,19',
			)
		);
		
		// Check that the number is numeric and of the right sort of length.
		if (preg_match('/^[0-9]{13,19}$/', $card_number) == 0)  {
			return false;
		}
		
		$card_type = $this->get_credit_card_type($card_number); 
		
		// If card type not found, report an error
		if ($card_type == false) {
			return false; 
		}
		
		foreach ($cards as $card_array) {
			if ($card_array['name'] == $card_type) {
				$lengths = split(',', $card_array['length']);
			}
		}
		
		for ($j = 0; $j < sizeof($lengths); $j++) {
			if (strlen($card_number) == $lengths[$j]) {
				$length_valid = true;
				break;
			}
		}
		
		// See if all is OK by seeing if the length was valid. 
		if (empty($length_valid)) {
			return false; 
		};
		
		// The credit card is the right length
		return true;
	}
	
	public function get_credit_card_type($card_number)
	{
		$prefixes = array(
			
			// the only 2
			'2014,2149' => 'dinersclub', // Diners Club Enroute
			
			// the 3s
			'300,301,302,303,304' => 'dinersclub', // Diners Club Carte Blance, this could also be 305, but default to diners club in that case
			'305,36,38,54,55' => 'dinersclub',
			'34,37' => 'amex',
			'35' => 'jcb',
			
			// the 4s (plus switch which has a 5 and some 6s)
			'4903,4905,4911,4936,564182,633110,6333,6759' => 'switch',
			'417500,4917,4913,4508,4844' => 'visa', // Visa Electron
			'4' => 'visa',
			
			// the 5s (plus maestro which has some 6s)
			'5018,5020,5038,6304,6759,6761' => 'maestro',
			'51,52,53,54,55' => 'mastercard',
			
			// the 6s
			'6011,622,64,65' => 'discover',
			'6304,6706,6771,6709' => 'lasercard',
			'6334,6767' => 'solo',
		);
		
		foreach ($prefixes as $prefix_raw => $type) {
			$prefix_array = explode(',', $prefix_raw);
			foreach ($prefix_array as $prefix) {
				if (preg_match('/^' . $prefix . '/', $card_number) > 0) {
					$card_type = $type;
					break;
				}
			}
		}
		
		return (!empty($card_type)) ? $card_type : false;
	}
	
	public function step_1()
	{
		if (!in_array($this->service_plan_id, $this->UbersmithCart->service_plans())) {
			throw new BadRequestException(__('Invalid %s specified','service plan id'));
		}
		
		$service_plan_api_array = array(
			'method' => 'uber.service_plan_get',
			'plan_id' => $this->service_plan_id,
		);
		$uber_service_plan = $this->UberApi->call_api($service_plan_api_array);
		if (empty($uber_service_plan->error_code)) {
			$service_plan = $uber_service_plan->data;
		} else {
			throw new BadRequestException(__('Invalid %s specified','service plan'));
		}
		
		$uber_client = $this->Session->read('uber_client');
		
		$selected_upgrades = array();
		
		if (!empty($this->cart_item_id) && !empty($this->UbersmithCart->cart['CartItem'])) {
			foreach ($this->UbersmithCart->cart['CartItem'] as $k => $cart_item) {
				if ($this->cart_item_id != $cart_item['id']) {
					continue;
				}
				
				// we're editing an existing cart option
				$selected_upgrades = array();
				foreach ($cart_item['CartItemUpgrade'] as $cart_item_upgrade_id => $cart_item_upgrade) {
					$selected_upgrades[$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
				}
			}
		}
		
		// only check the selected options if we're not editing
		if (!empty($selected_upgrades)) {
			$this->set('cart_item_id', $this->cart_item_id);
		}
		else {
			$valid_upgrades = array();
			foreach ($service_plan->upgrades as $upgrade_id => $upgrade) {
				$valid_upgrades[$upgrade_id] = array_keys((array) $upgrade->options);
			}
			
			// pre-selected options
			if (!empty($_GET['so'])) {
				foreach ($_GET['so'] as $upgrade_id => $upgrade_option_id) {
					if (preg_match('/[^0-9]/', $upgrade_id) > 0 || preg_match('/[^0-9]/', $upgrade_option_id) > 0 || !in_array($upgrade_option_id, @$valid_upgrades[$upgrade_id])) {
						continue;
					}
					$selected_upgrades[$upgrade_id] = $upgrade_option_id;
				}
			}
		}
		
		foreach ($service_plan->pricing as $service_plan_pricing) {
			if ($service_plan_pricing->api_label != 'monthly') {
				continue;
			}
			$monthly_price = $service_plan_pricing->price;
			$setup_fee = $service_plan_pricing->setup_fee;
		}
		
		$upgrade_groups = array();
		if (!empty($service_plan->upgrade_groups)) {
			foreach ($service_plan->upgrade_groups as $upgrade_group_id => $service_plan_upgrade_group) {
				$upgrade_group = new stdClass;
				$upgrade_group->id = $service_plan_upgrade_group->spug_id;
				$upgrade_group->status = $service_plan_upgrade_group->spug_status;
				$upgrade_group->name = $service_plan_upgrade_group->spug_name;
				$upgrade_group->description = @$service_plan_upgrade_group->spug_desc;
				$upgrade_group->upgrades = array();
				$upgrade_groups[$upgrade_group->id] = $upgrade_group;
			}
		}
		
		$coupons = $this->UbersmithCart->coupons();
		
		$total_savings = array();
		$upgrades = array();
		$monthly_price_one_time_discount_amount = $monthly_price_recurring_discount_amount = $setup_fee_discount_amount = 0;
		foreach ($service_plan->upgrades as $upgrade_id => $upgrade) {
			if (empty($upgrade->options)) {
				continue;
			}
			$upgrades[$upgrade_id] = new stdClass;
			$upgrades[$upgrade_id]->id = $upgrade_id;
			$upgrades[$upgrade_id]->group_id = $upgrade->spug_id;
			$upgrades[$upgrade_id]->name = $upgrade->spg_name;
			$upgrades[$upgrade_id]->description = $upgrade->spg_desc;
			$upgrades[$upgrade_id]->options = array();
			
			$upgrades[$upgrade_id]->option_groups = array();
			foreach ($upgrade->option_groups as $k => $service_plan_option_group) {
				$option_group = new stdClass;
				$option_group->id = $service_plan_option_group->spog_id;
				$option_group->name = $service_plan_option_group->spog_name;
				foreach ($service_plan_option_group->spog_data as $key => $value) {
					if ($key == 'image_url') {
						$option_group->image_url = $value;
					}
				}
				$upgrades[$upgrade_id]->option_groups[$service_plan_option_group->spog_id] = $option_group;
			}
			
			foreach ($upgrade->options as $upgrade_option_id => $upgrade_option) {
				if (!empty($upgrade_option->spo_data)) {
					foreach ($upgrade_option->spo_data as $key => $value) {
						if ($key == 'hide_option' && preg_match('/(yes|true|1)/i', $value) > 0) {
							continue 2;
						}
					}
				}
				$upgrades[$upgrade_id]->options[$upgrade_option_id] = new stdClass;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->option_group = $upgrades[$upgrade_id]->option_groups[$upgrade_option->spog_id];
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->id = $upgrade_option_id;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->name = $upgrade_option->spo_description;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->description = $upgrade_option->spo_desc;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee = $upgrade_option->spo_setup_fee;
				foreach ($upgrade_option->pricing as $upgrade_pricing) {
					if ($upgrade_pricing->api_label == 'monthly') {
						$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price = $upgrade_pricing->price;
					}
				}
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_discount_amount = 0;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_one_time_discount_amount = 0;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_recurring_discount_amount = 0;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_amount = 0;
				$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupon_id = 0;
				if (!empty($coupons)) {
					foreach ($coupons as $plan_id => $coupon) {
						$coupon_id = $coupon->coupon->coupon_id;
						$coupon_option_array = array();
						if (!empty($coupon->options)) {
							foreach ($coupon->options as $coupon_upgrade_option_id => $coupon_option) {
								$coupon_option_array[$coupon_upgrade_option_id] = $coupon_option;
							}
						}
						if (!array_key_exists($upgrade_option_id, $coupon_option_array)) {
							continue;
						}
						if ($coupon_option_array[$upgrade_option_id]->discount == 0 && $coupon_option_array[$upgrade_option_id]->setup_discount == 0) {
							continue;
						}
						$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupons[$coupon_option_array[$upgrade_option_id]->coupon_id] = new stdClass;
						$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupons[$coupon_option_array[$upgrade_option_id]->coupon_id]->one_time = 0;
						$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupons[$coupon_option_array[$upgrade_option_id]->coupon_id]->recurring = 0;
						if ($coupon_option_array[$upgrade_option_id]->discount > 0) {
							// 0 = percent, 1 = dollar
							$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_discount_type = ($coupon_option_array[$upgrade_option_id]->discount_type == '1') ? 'dollar' : 'percent';
							if ($upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_discount_type == 'dollar') {
								$discount_amount = $coupon_option_array[$upgrade_option_id]->discount;
								$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_discount_amount += $discount_amount;
							}
							else {
								$discount_amount = ($upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price * $coupon_option_array[$upgrade_option_id]->discount / 100);
								$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_discount_amount += $discount_amount;
								if ($upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_discount_amount < 0) {
									// let's not get crazy with negative numbers
									$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_discount_amount = 0;
								}
							}
							if ($coupon->coupon->recurring == '1') {
								$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_recurring_discount_amount += $discount_amount;
								$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupons[$coupon_option_array[$upgrade_option_id]->coupon_id]->recurring = $discount_amount;
							}
							$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupons[$coupon_option_array[$upgrade_option_id]->coupon_id]->one_time = $discount_amount;
							$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_one_time_discount_amount += $discount_amount;
						}
						if ($coupon_option_array[$upgrade_option_id]->setup_discount > 0) {
							// 0 = percent, 1 = dollar
							$upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_type = ($coupon_option_array[$upgrade_option_id]->setup_discount_type == '1') ? 'dollar' : 'percent';
							if ($upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_type == 'dollar') {
								$upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_amount += $coupon_option_array[$upgrade_option_id]->setup_discount;
							}
							else {
								$upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_amount += ($upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee * $coupon_option_array[$upgrade_option_id]->setup_discount / 100);
								if ($upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_amount < 0) {
									// let's not get crazy with negative numbers
									$upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_amount = 0;
								}
							}
							$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupons[$coupon_option_array[$upgrade_option_id]->coupon_id]->one_time += $upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee_discount_amount;
						}
					}
				}
				// pre-set through the URL
				if ((!empty($selected_upgrades[$upgrade_id]) && $selected_upgrades[$upgrade_id] == $upgrade_option_id)) {
					$upgrades[$upgrade_id]->default_upgrade_option_id = $upgrade_option_id;
					@$total_savings[$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupon_id]->one_time += $upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_one_time_discount_amount;
					@$total_savings[$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupon_id]->monthly += $upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_monthly_discount_amount;
				}
				// find it normally
				if (
					$upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price == 0 &&
					$upgrades[$upgrade_id]->options[$upgrade_option_id]->setup_fee == 0 && 
					empty($upgrades[$upgrade_id]->default_upgrade_option_id)
				)
				{
					$upgrades[$upgrade_id]->default_upgrade_option_id = $upgrade_option_id;
					@$total_savings[$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupon_id]->one_time += $upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_one_time_discount_amount;
					@$total_savings[$upgrades[$upgrade_id]->options[$upgrade_option_id]->coupon_id]->monthly += $upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price_monthly_discount_amount;
				}
				// if this is the selected option, calculate our costs
				if (!empty($upgrades[$upgrade_id]->default_upgrade_option_id) && $upgrade_option_id == $upgrades[$upgrade_id]->default_upgrade_option_id) {
					$setup_fee += $upgrade_option->spo_setup_fee;
					$monthly_price += $upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price;
				}
			}
			// if the whole category is hidden
			if (empty($upgrades[$upgrade_id]->options)) {
				unset($upgrades[$upgrade_id]);
				continue;
			}
			if (empty($upgrades[$upgrade_id]->default_upgrade_option_id)) {
				$upgrades[$upgrade_id]->default_upgrade_option_id = $upgrade_option_id;
				$monthly_price += $upgrades[$upgrade_id]->options[$upgrade_option_id]->monthly_price;
				$setup_fee += $upgrade_option->spo_setup_fee;
			}
			$monthly_price_one_time_discount_amount += $upgrades[$upgrade_id]->options[$upgrades[$upgrade_id]->default_upgrade_option_id]->monthly_price_one_time_discount_amount;
			$monthly_price_recurring_discount_amount += $upgrades[$upgrade_id]->options[$upgrades[$upgrade_id]->default_upgrade_option_id]->monthly_price_recurring_discount_amount;
			$setup_fee_discount_amount += $upgrades[$upgrade_id]->options[$upgrades[$upgrade_id]->default_upgrade_option_id]->setup_fee_discount_amount;
			$upgrade_groups[$upgrade->spug_id]->upgrades[$upgrade_id] = $upgrades[$upgrade_id];
		}
		
		foreach ($upgrade_groups as $upgrade_group_id => $upgrade_group) {
			if (empty($upgrade_group->upgrades)) {
				unset($upgrade_groups[$upgrade_group_id]);
			}
		}
		
		$monthly_base_price_one_time_discount = $monthly_base_price_recurring_discount = 0;
		if (!empty($coupons)) {
			foreach ($coupons as $plan_id => $coupon) {
				$coupon_id = $coupon->coupon->coupon_id;
				if (empty($coupon->coupon->discount_value) && empty($coupon->coupon->setup_discount_value)) {
					continue;
				}
				if ($coupon->coupon->discount_value > 0) {
					$discount_type = ($coupon->coupon->dollar == '1') ? 'dollar' : 'percent';
					if ($discount_type == 'dollar') {
						$discount_amount = $coupon->coupon->discount_value;
					}
					else {
						$discount_amount = ($service_plan->price * $coupon->coupon->discount_value / 100);
					}
					if ($discount_amount > 0) {
						if ($coupon->coupon->recurring == '1') {
							@$total_savings[$coupon->coupon->coupon_id]->monthly += $discount_amount;
							$monthly_price_recurring_discount_amount += $discount_amount;
							$monthly_base_price_recurring_discount += $discount_amount;
						}
						$monthly_price_one_time_discount_amount += $discount_amount;
						$monthly_base_price_one_time_discount += $discount_amount;
						@$total_savings[$coupon->coupon->coupon_id]->one_time += $discount_amount;
					}
				}
				if ($coupon->coupon->setup_discount_value > 0) {
					$discount_type = ($coupon->coupon->setup_dollar == '1') ? 'dollar' : 'percent';
					if ($discount_type == 'dollar') {
						$discount_amount = $coupon->coupon->setup_discount_value;
					}
					else {
						$discount_amount = ($setup_fee * $coupon->coupon->setup_discount_value / 100);
					}
					if ($discount_amount > 0) {
						$setup_fee_discount_amount += $discount_amount;
					}
					@$total_savings[$coupon->coupon->coupon_id]->one_time += $discount_amount;
				}
			}
		}
		
		if (($setup_fee - $setup_fee_discount_amount) < 0) {
			$setup_fee_discount_amount = $service_plan->setup;
		}
		
		if (($monthly_price - $monthly_price_one_time_discount_amount) < 0) {
			$monthly_price_one_time_discount_amount = $service_plan->price;
		}
		
		if (($monthly_price - $monthly_price_recurring_discount_amount) < 0) {
			$monthly_price_recurring_discount_amount = $service_plan->price;
		}
		
		$this->set(array(
			'navigation_summary' => $this->UbersmithCart->navigation_summary(),
			'service_plan'       => $service_plan,
			'upgrade_groups'     => $upgrade_groups,
			'service_plan_id'    => $service_plan->plan_id,
			'monthly_price_base' => $service_plan->price,
			'monthly_price'      => $monthly_price,
			'setup_fee'          => $setup_fee,
			'total_savings'      => $total_savings,
			'upgrades'           => $upgrades,
			'coupons'            => $this->UbersmithCart->coupons(),
			'title_for_layout'   => __('Customize Your Server'),
			'logged_in'          => (empty($uber_client)) ? false : true,
			// these have a bit longer keys
			'setup_fee_discount_amount'               => $setup_fee_discount_amount,
			'monthly_base_price_one_time_discount'    => $monthly_base_price_one_time_discount,
			'monthly_base_price_recurring_discount'   => $monthly_base_price_recurring_discount,
			'monthly_price_one_time_discount_amount'  => $monthly_price_one_time_discount_amount,
			'monthly_price_recurring_discount_amount' => $monthly_price_recurring_discount_amount,
		));
	}
	
	public function step_2()
	{
		$session_cart = $this->Session->read('Cart');
		
		if (!empty($_POST)) {
			if (!in_array($this->service_plan_id, $this->UbersmithCart->service_plans())) {
				$this->Session->setFlash(__('This item is not currently being sold. Please try again.'), 'default', array('class' => 'error'));
				$this->redirect(array('controller' => 'pages', 'action' => 'index'));
			}
			
			$service_plan_api_array = array(
				'method' => 'uber.service_plan_get',
				'plan_id' => $this->service_plan_id,
			);
			$uber_service_plan = $this->UberApi->call_api($service_plan_api_array);
			if (empty($uber_service_plan->error_code)) {
				$service_plan = $uber_service_plan->data;
			} else {
				$this->Session->setFlash(__('This item is not currently being sold. Please try again.'), 'default', array('class' => 'error'));
				$this->redirect(array('controller' => 'pages', 'action' => 'index'));
			}
				
			if (empty($session_cart)) {
				$this->Cart->create();
				$this->Cart->set('cake_session_id', $this->Session->id());
				$this->Cart->save();
				$cart = $this->Cart->read(null, $this->Cart->id);
				$this->Session->write('Cart', $cart);
			} else {
				$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
			}
			
			$errors = array();
			
			$selected_upgrades = array();
			foreach ($_POST['upgrades'] as $post_upgrade_id => $post_upgrade_option) {
				foreach ($service_plan->upgrades as $upgrade_id => $upgrade) {
					if (empty($upgrade->options) || !empty($upgrade->spg_data->hide_option)) {
						continue;
					}
					if (empty($_POST['upgrades'][$upgrade_id])) {
						$errors[] = __('You did not specify an upgrade for %s', htmlentities($upgrade->spg_name));
						continue 2;
					}
					else {
						if (preg_match('/^upgrades\[' . preg_quote($upgrade_id, '/') . '\]\[([0-9]+)\]/', $post_upgrade_option, $match) == 0) {
							continue;
						}
						foreach ($upgrade->options as $upgrade_option_id => $upgrade_option) {
							if ($upgrade_id != $post_upgrade_id) {
								continue;
							}
							if ($match[1] == $upgrade_option_id) {
								$selected_upgrades[$upgrade_id] = $upgrade_option_id;
							}
						}
					}
					if (empty($selected_upgrades[$upgrade_id])) {
						$errors[] = __('You did not specify a valid upgrade for %s', htmlentities($upgrade->spg_name));
					}
				}
			}
		}
		
		$coupons = $this->UbersmithCart->coupons();
		
		if (!empty($errors)) {
			$this->Session->setFlash(implode('<br />', $errors), 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 1, $this->service_plan_id));
		}
		else if (!empty($_POST)) {
			if (!empty($this->cart_item_id)) {
				$cart_item = $this->CartItem->find('first', array('conditions' => array('CartItem.id' => $this->cart_item_id, 'CartItem.cart_id' => $cart['Cart']['id'])));
				if (!empty($cart_item['CartItem']['id'])) {
					$cart_item_id = $cart_item['CartItem']['id'];
					foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
						if (!empty($selected_upgrades[$cart_item_upgrade['upgrade']])) {
							$this->CartItemUpgrade->save(array(
								'id' => $cart_item_upgrade['id'],
								'upgrade_option' => $selected_upgrades[$cart_item_upgrade['upgrade']],
							));
						}
					}
				}
			}
			
			// adding, or they specified an invalid cart item to edit
			if (empty($cart_item_id)) {
				$this->CartItem->create();
				$this->CartItem->set('service_plan_id', $this->service_plan_id);
				$this->CartItem->set('cart_id', $cart['Cart']['id']);
				$this->CartItem->set('quantity', 1);
				$this->CartItem->save();
				$cart_item_id = $this->CartItem->id;
				foreach ($selected_upgrades as $upgrade_id => $upgrade_option_id) {
					$this->CartItemUpgrade->create();
					$this->CartItemUpgrade->set('cart_item_id', $cart_item_id);
					$this->CartItemUpgrade->set('upgrade', $upgrade_id);
					$this->CartItemUpgrade->set('upgrade_option', $upgrade_option_id);
					$this->CartItemUpgrade->save();
				}
			}
			
			// because when you hit refresh and the browser asks if you want to resubmit the form, it's ANNOYING
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'cart'));
		} else {
			if (empty($session_cart)) {
				if (empty($this->service_plan_id)) {
					// redirect home
					$this->redirect(array('controller' => 'pages', 'action' => 'index'));
				}
				else {
					$this->Session->setFlash(__('Please add an item to your cart'), 'default', array('class' => 'error'));
					$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 1, $this->service_plan_id));
				}
			}
		}
		
		$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
		
		if (count($cart['CartItem']) == 0) {
			$this->redirect(array('controller' => 'pages', 'action' => 'index'));
		}
		
		$uber_client = $this->Session->read('uber_client');
		
		$highlight_error_inputs = $this->Session->read('highlight_error_inputs');
		
		if (!empty($highlight_error_inputs)) {
			$this->Session->delete('highlight_error_inputs');
		} else {
			$highlight_error_inputs = array();
		}
		
		$quantities = $this->Session->read('quantities');
		if (empty($quantities)) {
			$quantities = array();
		}
		foreach ($cart['CartItem'] as $cart_item) {
			$quantities[$cart_item['id']] = (!empty($quantities[$cart_item['id']]) ? $quantities[$cart_item['id']] : $cart_item['quantity']);
		}
		$this->Session->write('quantities', $quantities);
		
		$hostnames = $this->Session->read('hostnames');
		if (empty($hostnames)) {
			$hostnames = array();
		}
		foreach ($cart['CartItem'] as $cart_item) {
			for ($i = 0; $i < $cart_item['quantity']; $i++) {
				if (!isset($hostnames[$cart_item['id']][$i])) {
					$hostnames[$cart_item['id']][] = '';
				}
			}
		}
		
		$this->Session->write('hostnames', $hostnames);
		
		$this->create_cart_review($cart);
		
		$this->set(array(
			'coupons'                => $coupons,
			'navigation_summary'     => $this->UbersmithCart->navigation_summary(),
			'quantities'             => $quantities,
			'hostnames'              => $hostnames,
			'highlight_error_inputs' => $highlight_error_inputs,
			'title_for_layout'       => __('Cart'),
			'logged_in'              => (empty($uber_client)) ? false : true,
		));
	}
	
	public function step_3()
	{
		$session_cart = $this->Session->read('Cart');
		$uber_client = $this->Session->read('uber_client');
		
		$highlight_error_inputs = array();
		
		if (empty($session_cart)) {
			if (empty($this->service_plan_id)) {
				// redirect home
				$this->redirect(array('controller' => 'pages', 'action' => 'index'));
			} else {
				$this->Session->setFlash(__('Please add an item to your cart'), 'default', array('class' => 'error'));
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 1, $this->service_plan_id));
			}
		} else {
			$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
			if (empty($cart)) {
				if (empty($this->service_plan_id)) {
					// redirect home
					$this->redirect(array('controller' => 'pages', 'action' => 'index'));
				} else {
					$this->Session->setFlash(__('Please add an item to your cart'), 'default', array('class' => 'error'));
					$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 1, $this->service_plan_id));
				}
			} else if (empty($_POST)) {
				// make sure we have proper hostnames
				foreach ($cart['CartItem'] as $cart_item) {
					if (empty($cart_item['CartItemHostname']) || count($cart_item['CartItemHostname']) != $cart_item['quantity']) {
						$this->Session->setFlash(__('Please specify proper hostnames for your items'), 'default', array('class' => 'error'));
						$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'cart'));
					}
				}
			}
		}
		
		if (!empty($_POST)) {
			if (!is_array($_POST['quantity'])) {
				$errors[] = __('Please specify a valid quantity');
			} else {
				foreach ($_POST['quantity'] as $cart_item_id => $quantity) {
					if ($quantity <= 0 || (Configure::read('Ubersmith.max_item_quantity') > 0 && $quantity > Configure::read('Ubersmith.max_item_quantity'))) {
						$errors[] = __('Please specify a valid quantity, greater than 0 and less than %s', Configure::read('Ubersmith.max_item_quantity'));
						$highlight_error_inputs[] = 'quantity-' . $cart_item_id;
					} else {
						$this->CartItem->save(array(
							'id' => $cart_item_id,
							'quantity' => $quantity,
						));
						$quantities[$cart_item_id] = $quantity;
						$hostnames[$cart_item_id] = array();
						if (empty($_POST['hostnames'][$cart_item_id])) {
							$errors[] = __('Please specify valid hostname(s)');
							$highlight_error_inputs[] = 'hostnames-' . $cart_item_id . '-1';
							$hostnames[$cart_item_id][] = '';
						} else {
							// because real sequences start at zero
							$quantity_computerified = ($quantity - 1);
							$label = '[\\w][\\w\\.\\-]{0,61}[\\w]';
							$tld = '[\\w]+';
							$this->CartItemHostname->deleteAll(array(
								'cart_item_id' => $cart_item_id,
							));
							for ($i = 0; $i <= $quantity_computerified; $i++) {
								if (empty($_POST['hostnames'][$cart_item_id][$i]) || preg_match("/^($label)\\.($tld)$/", $_POST['hostnames'][$cart_item_id][$i]) == 0) {
									$errors[] = __('Invalid hostname specified for server') . ' ' . ($i + 1);
									$highlight_error_inputs[] = 'hostnames-' . $cart_item_id . '-' . ($i + 1);
								}
								else {
									$this->CartItemHostname->create();
									$this->CartItemHostname->set('cart_item_id', $cart_item_id);
									$this->CartItemHostname->set('hostname', $_POST['hostnames'][$cart_item_id][$i]);
									$this->CartItemHostname->save();
								}
								$hostnames[$cart_item_id][] = $_POST['hostnames'][$cart_item_id][$i];
							}
						}
					}
				}
			}
			
			$this->Session->write('quantities', $quantities);
			$this->Session->write('hostnames', $hostnames);
		}
		
		if (!empty($errors)) {
			$this->Session->setFlash(__('Please fill in proper hostnames and quantities before continuing') . ': ' . implode(', ', $errors), 'default', array('class' => 'error'));
			$this->Session->write('highlight_error_inputs', $highlight_error_inputs);
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'cart'));
		}
		
		if (!empty($uber_client)) {
			// already logged in, redirect to payment methods page
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'payment-method'));
		}
		
		if (!empty($_POST) && empty($errors)) {
			// a fresh redirect
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
		}
		
		$highlight_error_inputs = $this->Session->read('highlight_error_inputs');
		
		if (!empty($highlight_error_inputs)) {
			$this->Session->delete('highlight_error_inputs');
		} else {
			$highlight_error_inputs = array();
		}
		
		$this->set(array(
			'navigation_summary'     => $this->UbersmithCart->navigation_summary(),
			'highlight_error_inputs' => $highlight_error_inputs,
			'us_states'              => $this->Utilities->us_states,
			'ca_provinces'           => $this->Utilities->ca_provinces,
			'countries'              => $this->Utilities->countries,
			'new_client_info'        => $this->Session->read('new_client_info'),
			'title_for_layout'       => __('Account Info'),
		));
	}
	
	public function step_4()
	{
		$uber_client = $this->Session->read('uber_client');
		$session_cart = $this->Session->read('Cart');
		$new_client_info = $this->Session->read('new_client_info');
		$new_card_info = $this->Session->read('new_card_info');
		
		$highlight_error_inputs = array();
		
		$coupons = $this->UbersmithCart->coupons();
		
		if (isset($_POST['logging_in'])) {
			$this->Session->delete('new_client_info');
			
			// try to login the client
			$uber_client = $this->UberApi->call_api(array('method' => 'uber.check_login', 'login' => $_POST['login'], 'pass' => $_POST['password']));
			
			if (empty($uber_client) || !empty($uber_client->error_code)) {
				$this->Session->setFlash(__('Invalid login, please try again'), 'default', array('class' => 'error'));
				$this->Session->delete('uber_client');
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
			}
			
			$uber_client = $uber_client->data;
			
			if ($uber_client->type == 'contact' && (empty($uber_client->access->view_orders) || $uber_client->access->view_orders != 'edit')) {
				$this->Session->setFlash(__('Please login as a contact with permission to create orders'), 'default', array('class' => 'error'));
				$this->Session->delete('uber_client');
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
			}
			
			if ($uber_client->type == 'admin') {
				$this->Session->setFlash(__('Must login as a client, please try again'), 'default', array('class' => 'error'));
				$this->Session->delete('uber_client');
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
			}
			
			$uber_client_info = $this->UberApi->call_api(array('method' => 'client.get', 'client_id' => $uber_client->client_id));
			
			$client_info = array();
			if (!empty($uber_client_info->data->full_name)) {
				$client_info['full_name'] = $uber_client_info->data->full_name;
			}
			if (!empty($uber_client_info->data->address)) {
				$client_info['address'] = $uber_client_info->data->address;
			}
			if (!empty($uber_client_info->data->address)) {
				$client_info['city'] = $uber_client_info->data->city;
			}
			if (!empty($uber_client_info->data->state)) {
				$client_info['state'] = $uber_client_info->data->state;
			}
			if (!empty($uber_client_info->data->zip)) {
				$client_info['postal_code'] = $uber_client_info->data->zip;
			}
			if (!empty($uber_client_info->data->country)) {
				$client_info['country'] = $uber_client_info->data->country;
			}
			if (!empty($uber_client_info->data->phone)) {
				$client_info['phone_number'] = $uber_client_info->data->phone;
			}
			if (!empty($uber_client_info->data->listed_company)) {
				$client_info['company'] = $uber_client_info->data->listed_company;
			}
			
			$this->Session->write('client_info', $client_info);
			$this->Session->write('uber_client', $uber_client);
			
			// freshly redirect
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'payment-method'));
		} elseif (isset($_POST['new_client'])) {
			// new client
			$new_client_info = array();
			$new_client_info['is_new_client'] = true;
			
			// first name
			if (empty($_POST['first_name']) || trim($_POST['first_name']) == '') {
				$errors[] = __('Please enter your first name');
				$highlight_error_inputs[] = 'first_name';
			} else {
				$new_client_info['first_name'] = $_POST['first_name'];
			}
			
			// last name
			if (empty($_POST['last_name']) || trim($_POST['last_name']) == '') {
				$errors[] = __('Please enter your last name');
				$highlight_error_inputs[] = 'last_name';
			} else {
				$new_client_info['last_name'] = $_POST['last_name'];
			}
			
			// email
			if (empty($_POST['email']) || trim($_POST['email']) == '' || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				$errors[] = __('Please enter a valid email address');
				$highlight_error_inputs[] = 'email';
			} else {
				$new_client_info['email'] = $_POST['email'];
			}
			
			// password
			if (empty($_POST['password']) || trim($_POST['password']) == '' || strlen($_POST['password']) < 6 || strlen($_POST['password']) > 255) {
				$errors[] = __('Please enter a valid password between 6 and 255 characters long');
				$highlight_error_inputs[] = 'password';
			} else {
				$new_client_info['password'] = $_POST['password'];
			}
			
			// phone number
			if (empty($_POST['phone_number']) || trim($_POST['phone_number']) == '' || preg_match('/[^0-9\(\)\s\-\.\+]/', $_POST['phone_number']) > 0 || strlen($_POST['phone_number']) < 10) {
				$errors[] = __('Please enter a valid phone number');
				$highlight_error_inputs[] = 'phone_number';
			} else {
				$new_client_info['phone_number'] = $_POST['phone_number'];
			}
			
			// address
			if (empty($_POST['address']) || trim($_POST['address']) == '') {
				$errors[] = __('Please enter a valid address');
				$highlight_error_inputs[] = 'address';
			} else {
				$new_client_info['address'] = $_POST['address'];
			}
			
			// city
			if (empty($_POST['city']) || trim($_POST['city']) == '') {
				$errors[] = __('Please enter a valid city');
				$highlight_error_inputs[] = 'city';
			} else {
				$new_client_info['city'] = $_POST['city'];
			}
			
			// country/state
			if (empty($_POST['country']) || trim($_POST['country']) == '' || !in_array($_POST['country'], array_keys($this->Utilities->countries))) {
				$errors[] = __('Please enter a valid country');
				$highlight_error_inputs[] = 'country';
			} else {
				$new_client_info['country'] = $_POST['country'];
				switch ($_POST['country']) {
					case 'US':
						if (empty($_POST['state']) || trim($_POST['state']) == '' || !in_array($_POST['state'], array_keys($this->Utilities->us_states))) {
							$errors[] = __('Please enter a valid US state');
							$highlight_error_inputs[] = 'state';
						}
						else {
							$new_client_info['state'] = $_POST['state'];
						}
						break;
					case 'CA':
						if (empty($_POST['province']) || trim($_POST['province']) == '' || !in_array($_POST['province'], array_keys($this->Utilities->ca_provinces))) {
							$errors[] = __('Please enter a valid Canadian province');
							$highlight_error_inputs[] = 'province';
						}
						else {
							$new_client_info['province'] = $new_client_info['state'] = $_POST['province'];
						}
						break;
					default:
						if (empty($_POST['other_state']) || trim($_POST['other_state']) == '') {
							$errors[] = __('Please enter a valid state');
							$highlight_error_inputs[] = 'other_state';
						}
						else {
							$new_client_info['other_state'] = $new_client_info['state'] = $_POST['other_state'];
						}
						break;
				}
			}
			
			// postal code
			if (empty($_POST['postal_code']) || trim($_POST['postal_code']) == '') {
				$errors[] = __('Please enter a valid postal code');
				$highlight_error_inputs[] = 'postal_code';
			}
			else {
				$new_client_info['postal_code'] = $_POST['postal_code'];
			}
			
			// company name
			if (!empty($_POST['company_name'])) {
				$new_client_info['company_name'] = $_POST['company_name'];
			}
			
			$this->Session->write('new_client_info', $new_client_info);
			
			if (!empty($errors)) {
				if (count($errors) > 1) {
					$this->Session->setFlash('Please correct the errors below before continuing.', 'default', array('class' => 'error'));
				}
				else {
					$this->Session->setFlash($errors[0], 'default', array('class' => 'error'));
				}
				$this->Session->write('highlight_error_inputs', $highlight_error_inputs);
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
			}
			
			$args = array();
			
			$new_client_info['full_name'] = $new_client_info['first_name'] . ' ' . $new_client_info['last_name'];
			
			$this->Session->write('client_info', $new_client_info);
			
			$args = array();
			
			$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
			$uber_client = $this->Session->read('uber_client');
			
			$i = 1;
			foreach ($cart['CartItem'] as $key => $cart_item) {
				if (!empty($cart_item['CartItemHostname'])) {
					foreach ($cart_item['CartItemHostname'] as $hostname_array) {
						if (!empty($coupons)) {
							foreach ($coupons as $plan_id => $coupon) {
								$coupon_id = $coupon->coupon->coupon_id;
								if (!empty($coupon->coupon->plan_id) && $cart_item['service_plan_id'] == $coupon->coupon->plan_id) {
									$args['info']['pack' . $i]['coupon'] = $coupon->coupon->coupon_code;
								}
							}
						}
						$args['info']['pack' . $i]['desserv']     = $hostname_array['hostname'];
						$args['info']['pack' . $i]['status']      = 2; // pending
						$args['info']['pack' . $i]['auto_bill']   = 1; // yes, auto bill them
						$args['info']['pack' . $i]['start']       = mktime(0, 0, 0);
						$args['info']['pack' . $i]['lastrenewed'] = mktime(0, 0, 0);
						$args['info']['pack' . $i]['renewdate']   = mktime(0, 0, 0, (date('n') + 1), date('j'), date('Y'));
						if (!empty($uber_client)) {
							$uber_client_info = $this->UberApi->call_api(array('method' => 'client.get', 'client_id' => $uber_client->client_id));
							$default_renew = (empty($uber_client_info->data->default_renew)) ? $uber_client_info->data->invoice_delivery : $uber_client_info->data->default_renew;
							if ($default_renew > date('j')) {
								// this month
								$args['info']['pack' . $i]['prorate_date'] = mktime(0, 0, 0, date('n'), $default_renew, date('Y'));
							}
							else {
								// next month
								$args['info']['pack' . $i]['prorate_date'] = mktime(0, 0, 0, (date('n') + 1), $default_renew, date('Y'));
							}
						}
						$args['info']['pack' . $i]['plan_id'] = $cart_item['service_plan_id'];
						foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
							$args['info']['pack' . $i]['options'][$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
						}
						$i++;
					}
				}
				else {
					$args['info']['pack' . $i]['plan_id'] = $cart_item['service_plan_id'];
					$args['info']['pack' . $i]['auto_bill'] = 1; // yes, auto bill them
					foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
						$args['info']['pack' . $i]['options'][$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
					}
					$i++;
				}
			}
			
			$args['info']['first'] = $new_client_info['first_name'];
			$args['info']['last'] = $new_client_info['last_name'];
			$args['info']['email'] = $new_client_info['email'];
			$args['info']['uber_pass'] = $new_client_info['password'];
			$args['info']['ip_address'] = $_SERVER['REMOTE_ADDR'];
			$args['order_queue_id'] = Configure::read('Ubersmith.order_queue_id');
			
			$order_id = $this->Session->read('order_id');
			
			unset($new_client_info['first_name'], $new_client_info['last_name']);
			
			$this->Session->write('client_info', $new_client_info);
			
			$coupons = $this->UbersmithCart->coupons();
			if (!empty($coupons)) {
				foreach ($coupons as $plan_id => $coupon) {
					$coupon_id = $coupon->coupon->coupon_id;
					if (empty($coupon->coupon->plan_id)) {
						$args['info']['coupon'] = $coupon->coupon->coupon_code;
					}
				}
			}
			
			// new customer
			$args['info']['email'] = $new_client_info['email'];
			$args['info']['uber_pass'] = $new_client_info['password'];
			
			$args['info']['ip_address'] = $_SERVER['REMOTE_ADDR'];
			
			$order_id = $this->Session->read('order_id');
			
			if (empty($order_id)) {
				$args['method'] = 'order.create';
			}
			else {
				$args['method'] = 'order.update';
				$args['order_id'] = $order_id;
			}
			
			$response = $this->UberApi->call_api($args);
			if (empty($response->error_code)) {
				if ($args['method'] == 'order.update') {
					$response = $this->UberApi->call_api(array(
						'method'   => 'order.get',
						'order_id' => $order_id,
					));
				}
				
				$order = $response->data;
				
				if (!empty($order->order_id)) {
					$this->Session->write('order_id', $order->order_id);
				}
				
				if (!empty($order->data->hash)) {
					$this->Session->write('hash', $order->data->hash);
				}
				
				if (!empty($order->progress) && empty($order->client_id)) {
					foreach ($order->progress as $order_action_id => $progress) {
						if (!empty($progress->status) && $progress->status == 'failed') {
							$order_process_response = $this->UberApi->call_api(array(
								'method'          => 'order.process',
								'order_id'        => $order_id,
								'order_action_id' => $order_action_id,
							));
						}
					}
					$response = $this->UberApi->call_api(array(
						'method'   => 'order.get',
						'order_id' => $order_id,
					));
					$order = $response->data;
				}
			}
			else {
				// couldn't order.create/update, only should fail due to username already taken
				$this->Session->setFlash(__('There was a problem with your order - please try again.'), 'default', array('class' => 'error'));
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
			}
			
			// freshly redirect
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'payment-method'));
		}
		
		if (empty($uber_client) && empty($session_cart) && empty($new_client_info)) {
			$this->redirect(array('controller' => 'pages', 'action' => 'index'));
		}
		else if (empty($uber_client) && empty($new_client_info) && !empty($session_cart)) {
			$this->Session->setFlash(__('Please login before continuing'), 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
		}
		else if ((!empty($uber_client) || !empty($new_client_info)) && empty($session_cart)) {
			$this->Session->setFlash(__('Please add an item to your cart'), 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'pages', 'action' => 'index'));
		}
		
		$payment_methods = array();
		if ($uber_client) {
			$payment_methods = $this->UberApi->call_api(array('method' => 'client.payment_method_list', 'client_id' => $uber_client->client_id));
			if (empty($payment_methods->error_code) && !empty($payment_methods->data)) {
				$payment_methods = $payment_methods->data;
				foreach ($payment_methods as $key => &$payment_method) {
					$payment_method->full_name = $payment_method->fname . ' ' . $payment_method->lname;
					$payment_method->id = $payment_method->billing_info_id;
					switch($payment_method->payment_type) {
						case "cc":
							$payment_method->text = $payment_method->cc_num;
							$payment_method->full_name = $payment_method->fname . ' ' . $payment_method->lname;
							$payment_method->account_type = $payment_method->cc_type;
							switch($payment_method->account_type) {
								case "amex":
								case "jcb":
								case "visa":
									$payment_method->card_type_full = strtoupper($payment_method->account_type);
									break;
								case "mastercard":
									$payment_method->card_type_full = 'MasterCard';
									break;
								case "dinersclub":
									$payment_method->card_type_full = 'Diners Club';
									break;
								default:
									$payment_method->card_type_full = ucfirst($payment_method->account_type);
									break;
							}
							$payment_method->expiration_month = substr($payment_method->cc_expire,0,2);
							$payment_method->expiration_year = substr($payment_method->cc_expire,2);
							break;
						case "ach":
							$payment_method->account_type = $payment_method->ach_type;
							switch($payment_method->account_type) {
								case "C":
									$payment_method->account_type_full = __('Checking');
									break;
								case "S":
									$payment_method->account_type_full = __('Savings');
									break;
								default:
									$payment_method->account_type_full = '';
									break;
							}
							$payment_method->bank = $payment_method->ach_bank;
							$payment_method->text = $payment_method->ach_acct;
							break;
					}
				}
				unset($payment_method);
			}
		}
		
		$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
		
		$highlight_error_inputs = $this->Session->read('highlight_error_inputs');
		
		if (!empty($highlight_error_inputs)) {
			$this->Session->delete('highlight_error_inputs');
		}
		else {
			$highlight_error_inputs = array();
		}
		
		$this->set(array(
			'navigation_summary'     => $this->UbersmithCart->navigation_summary(),
			'highlight_error_inputs' => $highlight_error_inputs,
			'client_info'            => $this->Session->read('client_info'),
			'new_client_info'        => $this->Session->read('new_client_info'),
			'us_states'              => $this->Utilities->us_states,
			'ca_provinces'           => $this->Utilities->ca_provinces,
			'countries'              => $this->Utilities->countries,
			'new_card_info'          => $new_card_info,
			'payment_methods'        => $payment_methods,
			'title_for_layout'       => __('Payment Method'),
		));
	}
	
	public function step_5()
	{
		$uber_client = $this->Session->read('uber_client');
		$session_cart = $this->Session->read('Cart');
		$payment_method = $this->Session->read('payment_method');
		$payment_method_id = $this->Session->read('payment_method_id');
		$new_client_info = $this->Session->read('new_client_info');
		$client_info = $this->Session->read('client_info');
		$new_card_info = $this->Session->read('new_card_info');
		
		$highlight_error_inputs = array();
		
		if (empty($uber_client) && empty($session_cart) && empty($new_client_info)) {
			$this->redirect(array('controller' => 'pages', 'action' => 'index'));
		} else if (empty($uber_client) && empty($client_info) && !empty($session_cart)) {
			$this->Session->setFlash('Please login before continuing', 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
		} else if ((!empty($uber_client) || !empty($new_client_info)) && empty($session_cart)) {
			$this->Session->setFlash('Please add an item to your cart', 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'pages', 'action' => 'index'));
		} else if (empty($payment_method) && empty($_POST)) {
			$this->Session->setFlash('Please select a valid payment methods', 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'payment-method'));
		}
		
		if (!empty($_POST)) {
			if (empty($_POST['payment_method'])) {
				$errors[] = 'Please specify a valid payment method';
			} else if ($_POST['payment_method'] != 'new_credit_card') {
				$this->Session->delete('new_card_info');
				$payment_methods = $this->UberApi->call_api(array('method' => 'client.payment_method_list', 'client_id' => $uber_client->client_id));
				$valid_payment_method = false;
				if (empty($payment_methods->error_code) && !empty($payment_methods->data)) {
					$payment_methods = $payment_methods->data;
					foreach ($payment_methods as $key => &$payment_method) {
						if ($payment_method->billing_info_id != $_POST['payment_method']) {
							continue;
						}
						$payment_method->full_name = $payment_method->fname . ' ' . $payment_method->lname;
						$payment_method->id = $payment_method->billing_info_id;
						switch($payment_method->payment_type) {
							case "cc":
								$payment_method->text = $payment_method->cc_num;
								$payment_method->full_name = $payment_method->fname . ' ' . $payment_method->lname;
								$payment_method->account_type = $payment_method->cc_type;
								switch($payment_method->account_type) {
									case "amex":
									case "jcb":
									case "visa":
										$payment_method->card_type_full = strtoupper($payment_method->account_type);
										break;
									case "mastercard":
										$payment_method->card_type_full = 'MasterCard';
										break;
									case "dinersclub":
										$payment_method->card_type_full = 'Diners Club';
										break;
									default:
										$payment_method->card_type_full = ucfirst($payment_method->account_type);
										break;
								}
								$payment_method->expiration_month = substr($payment_method->cc_expire,0,2);
								$payment_method->expiration_year = substr($payment_method->cc_expire,2);
								break;
							case "ach":
								$payment_method->account_type = $payment_method->ach_type;
								switch($payment_method->account_type) {
									case "C":
										$payment_method->account_type_full = __('Checking');
										break;
									case "S":
										$payment_method->account_type_full = __('Savings');
										break;
									default:
										$payment_method->account_type_full = '';
										break;
								}
								$payment_method->bank = $payment_method->ach_bank;
								$payment_method->text = $payment_method->ach_acct;
								break;
						}
						$valid_payment_method = true;
						$this->Session->write('payment_method', $payment_method);
						break;
					}
					unset($payment_method);
				}
				if (empty($valid_payment_method)) {
					$this->Session->delete('payment_method');
					$errors[] = __('Invalid payment method specified');
				}
			} else if ($_POST['payment_method'] == 'new_credit_card') {
				$cc_fields = array(
					'cc_num'     => __('Credit Card Number'),
					'cc_exp_mo'  => __('Expiration Month'),
					'cc_exp_yr'  => __('Expiration Year'),
					'cc_cvv2'    => __('Credit Card %s Code', 'CVV2'),
				);
				foreach ($cc_fields as $k => $v) {
					if (empty($_POST[$k]) || trim($_POST[$k]) == '') {
						$errors[] = __('%s is a required field', $v);
						$highlight_error_inputs[] = $k;
					}
					else {
						$new_card_info[$k] = $_POST[$k];
					}
				}
				
				$cc_billing_fields = array(
					'cc_first'   => __('First Name'),
					'cc_last'    => __('Last Name'),
					'cc_address' => __('Address'),
					'cc_city'    => __('City'),
					'cc_zip'     => __('Postal Code'),
					'cc_phone'   => __('Phone Number'),
				);
				if (empty($_POST['use_client_info'])) {
					foreach ($cc_billing_fields as $k => $v) {
						if (empty($_POST[$k]) || trim($_POST[$k]) == '') {
							$errors[] = __('%s is a required field', $v);
							$highlight_error_inputs[] = $k;
						} else {
							$new_card_info[$k] = $_POST[$k];
						}
					}				
					// special handling of country/state
					if (empty($_POST['cc_country']) || trim($_POST['cc_country']) == '' || !in_array($_POST['cc_country'], array_keys($this->Utilities->countries))) {
						$errors[] = 'Please enter a valid country';
					} else {
						$new_card_info['cc_country'] = $_POST['cc_country'];
						switch ($_POST['cc_country']) {
							case 'US':
								if (empty($_POST['cc_state']) || trim($_POST['cc_state']) == '' || !in_array($_POST['cc_state'], array_keys($this->Utilities->us_states))) {
									$errors[] = 'Please enter a valid US state';
									$highlight_error_inputs[] = 'cc_state';
								}
								else {
									$new_card_info['cc_state'] = $_POST['cc_state'];
								}
								break;
							case 'CA':
								if (empty($_POST['cc_province']) || trim($_POST['cc_province']) == '' || !in_array($_POST['cc_province'], array_keys($this->Utilities->ca_provinces))) {
									$errors[] = 'Please enter a valid Canadian province';
									$highlight_error_inputs[] = 'cc_province';
								}
								else {
									$new_card_info['cc_state'] = $new_card_info['cc_province'] = $_POST['cc_province'];
								}
								break;
							default:
								if (empty($_POST['cc_other_state']) || trim($_POST['cc_other_state']) == '') {
									$errors[] = 'Please enter a valid state';
									$highlight_error_inputs[] = 'cc_other_state';
								}
								else {
									$new_card_info['cc_state'] = $new_card_info['cc_other_state'] = $_POST['cc_other_state'];
								}
								break;
						}
					}
					$new_card_info['use_new_info'] = true;
				}
				else {
					// using client info
					$name_split = explode(' ', $client_info['full_name']);
					$new_card_info['cc_first'] = @$name_split[0];
					$new_card_info['cc_last'] = @$name_split[1];
					$new_card_info['cc_address'] = @$client_info['address'];
					$new_card_info['cc_city'] = @$client_info['city'];
					$new_card_info['cc_state'] = @$client_info['state'];
					$new_card_info['cc_country'] = @$client_info['country'];
					$new_card_info['cc_zip'] = @$client_info['postal_code'];
					$new_card_info['cc_phone'] = @$client_info['phone_number'];
					$new_card_info['use_client_info'] = true;
				}
				
				// perform some extra validation on the card
				if (!$this->validate_credit_card_luhn($_POST['cc_num'])) {
					$errors[] = 'Invalid credit card number';
					$highlight_error_inputs[] = 'cc_num';
				}
				else if (!$this->validate_credit_card_length($_POST['cc_num'])) {
					$errors[] = 'Invalid credit card length for card type';
					$highlight_error_inputs[] = 'cc_num';
				}
				
				// perform some extra validation on the expiration date
				if ((int) $new_card_info['cc_exp_yr'] < date('Y')) {
					$errors[] = 'Invalid expiration date';
					$highlight_error_inputs[] = 'cc_exp_yr';
				}
				else if ((int) $new_card_info['cc_exp_yr'] == date('Y') && $new_card_info['cc_exp_mo'] < date('n')) {
					$errors[] = 'Card has expired';
					$highlight_error_inputs[] = 'cc_exp_yr';
					$highlight_error_inputs[] = 'cc_exp_mo';
				}
				else {
					$new_card_info['cc_exp'] = str_pad($new_card_info['cc_exp_mo'], 2, '00', STR_PAD_LEFT) . substr($new_card_info['cc_exp_yr'], 2);
				}
				
				if (empty($errors)) {
					$payment_method = new stdClass;
					$payment_method->payment_type = 'cc';
					$payment_method->text = substr($new_card_info['cc_num'], -4);
					$payment_method->full_name = $new_card_info['cc_first'] . ' ' . $new_card_info['cc_last'];
					$payment_method->account_type = $this->get_credit_card_type($new_card_info['cc_num']);
					switch($payment_method->account_type) {
						case "amex":
						case "jcb":
						case "visa":
							$payment_method->card_type_full = strtoupper($payment_method->account_type);
							break;
						case "mastercard":
							$payment_method->card_type_full = 'MasterCard';
							break;
						case "dinersclub":
							$payment_method->card_type_full = 'Diners Club';
							break;
						default:
							$payment_method->card_type_full = ucfirst($payment_method->account_type);
							break;
					}
					$payment_method->expiration_month = $new_card_info['cc_exp_mo'];
					$payment_method->expiration_year = $new_card_info['cc_exp_yr'];
					$this->Session->write('payment_method', $payment_method);
					$valid_payment_method = true;
				}
			}
			
			if (empty($uber_client)) {
				$args = array(
					'method' => 'uber.msa_get',
					'format' => 'json',
				);
				$msa_response = $this->UberApi->call_api($args);
				$msa = $msa_response->data;
			}
			else {
				$args = array(
					'method'     => 'client.msa_get',
					'client_id'  => @$uber_client->client_id,
					'format'     => 'json',
				);
				$msa_response = $this->UberApi->call_api($args);
				if (!empty($msa_response->error_code)) {
					$args = array(
						'method' => 'uber.msa_get',
						'format' => 'json',
					);
					$msa_response = $this->UberApi->call_api($args);
				}
				$msa = $msa_response->data;
			}
			
			$this->Session->write('new_card_info', @$new_card_info);
			
			if (!empty($errors)) {
				if (count($errors) > 1) {
					$this->Session->setFlash(__('Please correct the errors below before continuing.'), 'default', array('class' => 'error'));
				}
				else {
					$this->Session->setFlash($errors[0], 'default', array('class' => 'error'));
				}
				$this->Session->write('highlight_error_inputs', $highlight_error_inputs);
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'payment-method'));
			}
			
			// cleanly redirect
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'order-review'));
		}
		
		$highlight_error_inputs = $this->Session->read('highlight_error_inputs');
		
		if (!empty($highlight_error_inputs)) {
			$this->Session->delete('highlight_error_inputs');
		}
		else {
			$highlight_error_inputs = array();
		}
		
		if (!empty($uber_client)) {
			$args = array(
				'method'     => 'client.msa_get',
				'client_id'  => $uber_client->client_id,
				'format'     => 'json',
			);
			$pdf_data = $this->UberApi->call_api($args);
		}
		
		if (!empty($pdf_data->data->active)) {
			$this->set(array(
				'has_active_client_msa' => true,
				'uber_has_msa'          => true,
			));
			$this->Session->write('has_active_client_msa', true);
		}
		else {
			$args = array(
				'method' => 'uber.msa_get',
				'format' => 'json',
			);
			$pdf_data = $this->UberApi->call_api($args);
			
			// ubersmith hasn't been configured with MSA at all
			if (empty($pdf_data->data->active)) {
				$this->set('uber_has_msa', false);
				$this->Session->write('uber_msa_id', false);
			} else {
				$this->set('uber_has_msa', true);
				$this->Session->write('uber_msa_id', $pdf_data->data->msa_id);
			}
		}
		
		$this->create_cart_review();
		
		$this->set(array(
			'navigation_summary'     => $this->UbersmithCart->navigation_summary(),
			'inline_msa_view'        => SWFToolComponent::configured(),
			'highlight_error_inputs' => $highlight_error_inputs,
			'client_info'            => $client_info,
			'payment_method'         => $payment_method,
			'title_for_layout'       => __('Order Review'),
		));
	}
	
	public function step_6()
	{
		$uber_client = $this->Session->read('uber_client');
		$payment_method = $this->Session->read('payment_method');
		$payment_method_id = $this->Session->read('payment_method_id');
		$new_client_info = $this->Session->read('new_client_info');
		$client_info = $this->Session->read('client_info');
		$new_card_info = $this->Session->read('new_card_info');
		$uber_msa_id = $this->Session->read('uber_msa_id');
		$agreed_to_msa = $this->Session->read('agreed_to_msa');
		
		if (empty($uber_client) && empty($session_cart) && empty($client_info)) {
			$this->redirect(array('controller' => 'pages', 'action' => 'index'));
		}
		else if (empty($uber_client) && empty($client_info) && !empty($session_cart)) {
			$this->Session->setFlash('Please login before continuing step_six', 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));
		}
		else if (empty($payment_method)) {
			$this->Session->setFlash('Please select a valid payment method', 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'payment-method'));
		}
		else if (empty($agreed_to_msa) && $uber_msa_id != false && empty($_POST)) {
			$this->Session->setFlash('Please accept the Master Services Agreement', 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'order-review'));
		}
		
		if (!empty($_POST)) {
			$session_cart = $this->Session->read('Cart');
			
			$args = array();
			
			if (empty($session_cart)) {
				$this->Session->setFlash('Please add an item to your cart', 'default', array('class' => 'error'));
				$this->redirect(array('controller' => 'pages', 'action' => 'index'));
			}
			
			if (empty($_POST['agreed_to_msa'])){
				$errors[] = __('Please accept the Master Services Agreement');
			}
			else {
				$active_msa = $this->Session->read('has_active_client_msa');
				$uber_msa_id = $this->Session->read('uber_msa_id');
				if (empty($active_msa) && $uber_msa_id) {
					$args['info']['msa']['msa_id'] = $uber_msa_id;
				}
			}
			
			if (empty($_POST['signing_name']) || trim($_POST['signing_name']) == '') {
				$errors[] = 'Please sign your name';
			}
			else {
				$args['info']['msa']['signer'] = $_POST['signing_name'];
			}
			
			if (!empty($errors)) {
				$this->Session->setFlash(implode('<br />', $errors), 'default', array('class' => 'error'));
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'order-review'));
			}
			
			$this->Session->write('agreed_to_msa', true);
			$this->Session->write('signing_name', $_POST['signing_name']);
			
			$cart = $this->Cart->read(null, $session_cart['Cart']['id']);
			
			$coupons = $this->UbersmithCart->coupons();
			if (!empty($coupons)) {
				foreach ($coupons as $plan_id => $coupon) {
					$coupon_id = $coupon->coupon->coupon_id;
					if (empty($coupon->coupon->plan_id)) {
						$args['info']['coupon'] = $coupon->coupon->coupon_code;
					}
				}
			}
			
			if (!empty($new_client_info)) {
				// new customer
				$args['info']['first'] = $new_client_info['first_name'];
				$args['info']['last'] = $new_client_info['last_name'];
				
				$args['info']['address'] = $new_client_info['address'];
				$args['info']['city'] = $new_client_info['city'];
				$args['info']['state'] = $new_client_info['state'];
				$args['info']['zip'] = $new_client_info['postal_code'];
				$args['info']['country'] = $new_client_info['country'];
				
				$args['info']['email'] = $new_client_info['email'];
				$args['info']['uber_pass'] = $new_client_info['password'];
				$args['info']['phone'] = $new_client_info['phone_number'];
				$args['info']['company'] = @$new_client_info['company'];
				// always has a new card
				foreach ($new_card_info as $k => $v) {
					$args['info'][$k] = $v;
				}
				
				$args['info']['pack1']['plan_id'] = 258;
				$args['info']['pack1']['auto_bill'] = 1;
				
				$args['info']['pack2']['plan_id'] = 247;
				$args['info']['pack2']['auto_bill'] = 1;
				
				$args['info']['pack3']['plan_id'] = 274;
				$args['info']['pack3']['auto_bill'] = 1;
				
				if (!empty($uber_client)) {
					$uber_client_info = $this->UberApi->call_api(array('method' => 'client.get', 'client_id' => $uber_client->client_id));
					$default_renew = (empty($uber_client_info->data->default_renew)) ? $uber_client_info->data->invoice_delivery : $uber_client_info->data->default_renew;
					if ($default_renew > date('j')) {
						// this month
						$args['info']['pack1']['prorate_date'] = mktime(0, 0, 0, date('n'), $default_renew, date('Y'));
						$args['info']['pack2']['prorate_date'] = mktime(0, 0, 0, date('n'), $default_renew, date('Y'));
						$args['info']['pack3']['prorate_date'] = mktime(0, 0, 0, date('n'), $default_renew, date('Y'));
					}
					else {
						// next month
						$args['info']['pack1']['prorate_date'] = mktime(0, 0, 0, (date('n') + 1), $default_renew, date('Y'));
						$args['info']['pack2']['prorate_date'] = mktime(0, 0, 0, (date('n') + 1), $default_renew, date('Y'));
						$args['info']['pack3']['prorate_date'] = mktime(0, 0, 0, (date('n') + 1), $default_renew, date('Y'));
					}
				}
				$i = 3;
			}
			else {
				$args['client_id'] = $uber_client->client_id;
				if (!empty($payment_method_id)) {
					$args['info']['billing_info_id'] = $payment_method_id;
				}
				else if (!empty($new_card_info)) {
					foreach ($new_card_info as $k => $v) {
						$args['info'][$k] = $v;
					}
				}
				$i = 1;
			}
			
			foreach ($cart['CartItem'] as $key => $cart_item) {
				if (!empty($cart_item['CartItemHostname'])) {
					foreach ($cart_item['CartItemHostname'] as $hostname_array) {
						$coupons = $this->UbersmithCart->coupons();
						if (!empty($coupons)) {
							foreach ($coupons as $plan_id => $coupon) {
								$coupon_id = $coupon->coupon->coupon_id;
								if (!empty($coupon->coupon->plan_id) && $cart_item['service_plan_id'] == $coupon->coupon->plan_id) {
									$args['info']['pack' . $i]['coupon'] = $coupon->coupon->coupon_code;
								}
							}
						}
						$args['info']['pack' . $i]['desserv']     = $hostname_array['hostname'];
						$args['info']['pack' . $i]['status']      = 2; // pending
						$args['info']['pack' . $i]['auto_bill']   = 1; // yes, auto bill them
						$args['info']['pack' . $i]['start']       = mktime(0, 0, 0);
						$args['info']['pack' . $i]['lastrenewed'] = mktime(0, 0, 0);
						$args['info']['pack' . $i]['renewdate']   = mktime(0, 0, 0, (date('n') + 1), date('j'), date('Y'));
						if (!empty($uber_client)) {
							$uber_client_info = $this->UberApi->call_api(array('method' => 'client.get', 'client_id' => $uber_client->client_id));
							$default_renew = (empty($uber_client_info->data->default_renew)) ? $uber_client_info->data->invoice_delivery : $uber_client_info->data->default_renew;
							if ($default_renew > date('j')) {
								// this month
								$args['info']['pack' . $i]['prorate_date'] = mktime(0, 0, 0, date('n'), $default_renew, date('Y'));
							}
							else {
								// next month
								$args['info']['pack' . $i]['prorate_date'] = mktime(0, 0, 0, (date('n') + 1), $default_renew, date('Y'));
							}
						}
						$args['info']['pack' . $i]['plan_id'] = $cart_item['service_plan_id'];
						foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
							$args['info']['pack' . $i]['options'][$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
						}
						$i++;
					}
				}
				else {
					$args['info']['pack' . $i]['plan_id'] = $cart_item['service_plan_id'];
					$args['info']['pack' . $i]['auto_bill'] = 1; // yes, auto bill them
					foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
						$args['info']['pack' . $i]['options'][$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
					}
					$i++;
				}
			}
			
			$args['info']['ip_address'] = $_SERVER['REMOTE_ADDR'];
			$args['info']['payment_type'] = 'charge_prior_auth';
			$args['info']['preauth_fail_error'] = 1;
			
			$order_id = $this->Session->read('order_id');
			
			if (empty($new_client_info)) {
				$args['info']['default_renew']    = (date('j') > 28 ? 28 : date('j'));
				$args['info']['invoice_delivery'] = $args['info']['default_renew'];
			}
			
			if (empty($order_id)) {
				$args['method'] = 'order.create';
				$args['order_queue_id'] = Configure::read('Ubersmith.order_queue_id');
			}
			else {
				$args['method'] = 'order.update';
				$args['order_id'] = $order_id;
			}
			
			$response = $this->UberApi->call_api($args);
			if (empty($response->error_code)) {
				
				$order = $response->data;
				if (!empty($order->data)) {
					$order_data = $order->data;
					$order_id = $order->order_id;
					
					$this->Session->write('order_id', $order_id);
					$this->Session->write('hash', $order_data->hash);
				}
				
				$args = array(
					'order_id' => $order_id,
					'method'   => 'order.submit',
				);
				
				$order_submit_response = $this->UberApi->call_api($args);
				
				if (empty($order_submit_response->error_code)) {
					// clear out the new client info
					
					$this->Session->delete('order_id');
					$this->Session->write('successful_order_id', $order_id);
					
					$this->Session->write('verification_phone_number', $new_client_info['phone_number']);
					$this->Session->write('verification_first_name', $new_client_info['first_name']);
					
					$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'order-complete'));
					
					if (!empty($new_client_info)) {
						$this->Session->delete('new_client_info');
						$this->Session->delete('new_card_info');
					}
				}
				else {
					$this->Session->setFlash(__('The credit card was declined, please try again.'), 'default', array('class' => 'error'));
					$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'payment-method'));
				}
			}
			else {
				// couldn't order.create/update, which should never happen
				$this->Session->setFlash(__('There was an error submitting your order. Please try again.'), 'default', array('class' => 'error'));
				$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'order-review'));
			}
		}
		
		$order_id = $this->Session->read('successful_order_id');
		$hash = $this->Session->read('hash');
		
		if (empty($order_id) || empty($hash)) {
			$this->Session->setFlash(__('There was an error submitting your order, please try again'), 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'order-review'));
		}
		
		$google_analytics_extras = array();
		
		$cart = $this->Session->read('Cart');
		if (!empty($cart)) {
			$cart = $this->Cart->read(null, $cart['Cart']['id']);
			
			$google_analytics_info = $this->create_cart_review_google_analytics($cart);
			
			$this->set('conversion_total_value', $google_analytics_info['monthly_total']);
			
			$google_analytics_extras[] = "_gaq.push(['_addTrans',
				'{$order_id}',
				'Configurator',
				'{$google_analytics_info['monthly_total']}',
				'0',        // tax
				'0',        // shipping cost
				'',         // city for shipping
				'',         // state or province for shipping
				''          // country for shipping
			]);\n";
			
			
			foreach ($google_analytics_info['items'] as $item) {
				$google_analytics_extras[] = "_gaq.push(['_addItem',
					'{$order_id}',
					'{$item->service_plan_id}',
					'{$item->name}',
					'',   // category or variation
					'{$item->monthly_price_average}',
					'{$item->quantity}'
				]);\n";
			}
			$google_analytics_extras[] = "_gaq.push(['_trackTrans']);\n";
			$this->Session->delete('Cart');
		}
		
		if (!empty($uber_client)) {
			$this->set('is_existing_client', true);
			$this->set('client_id', $uber_client->client_id);
		}
		else {
			$order = $this->UberApi->call_api(array(
				'method' => 'order.get',
				'order_id' => $order_id,
			));
			
			$this->set('is_existing_client', false);
			$this->set('client_id', $order->data->client_id);
		}
		
		$this->set(array(
			'navigation_summary'      => $this->UbersmithCart->navigation_summary(),
			'google_analytics_extras' => $google_analytics_extras,
			'order_id'                => $order_id,
			'hash'                    => $hash,
			'title_for_layout'        => __('Order Complete'),
		));
	}
	
	public function us_to_international_number($number = '')
	{
		$number = preg_replace('/[^0-9]/', '', $number);
		if (strlen($number) == 11 && substr($number, 0, 1) == '1') {
			$number = '+' . $number;
		}
		if (strlen($number) == 10) {
			$number = '+1' . $number;
		}
		return $number;
	}
	
	public function twilio_callback($number = '', $order_id = '', $verification_phone_number = '', $first_name = '')
	{
		$number = $this->us_to_international_number($number);
		$verification_phone_number = $this->us_to_international_number($verification_phone_number);
		
		header('Content-type: text/xml');
		
		$this->set('number', htmlspecialchars($number, ENT_QUOTES));
		$this->set('order_id', htmlspecialchars($order_id, ENT_QUOTES));
		$this->set('verification_phone_number', htmlspecialchars($verification_phone_number, ENT_QUOTES));
		$this->set('first_name', htmlspecialchars($first_name, ENT_QUOTES));
		
		$this->layout = 'plain';
	}
	
	public function twilio_call_status()
	{
		$call_sid = $this->Session->read('twilio_call_sid');
		
		if (empty($call_sid)) {
			echo 'No call in progress';
			exit;
		}
		
		App::import('Vendor', 'Services/Twilio/Twilio', array('file' => 'Services/Twilio/Twilio.php'));
		
		$sid = Configure::read('Twilio.sid');
		
		$token = Configure::read('Twilio.token');
		
		$client = new Services_Twilio($sid, $token);
		
		$call = $client->account->calls->get($call_sid);
		
		// queued, ringing, in-progress, completed, failed, busy, no-answer
		switch ($call->status) {
			case 'queued':
				echo __('Getting ready', ' . . .');
				break;
			case 'ringing':
				echo __('Calling support', ' . . .');
				break;
			case 'in-progress':
				echo __('Call in progress', ' . . .');
				break;
			case 'completed':
				echo __('Completed');
				break;
			case 'failed';
				echo __('Failed');
				break;
			case 'busy';
				echo __('Busy');
				break;
			case 'no-answer';
				echo __('No answer');
				break;
			default:
				echo __('Unable to determine call status');
				break;
		}
		
		if (in_array($call->status, array('failed','busy','no-answer'))) {
			$this->Session->delete('twilio_call_sid');
			echo '<script type="text/javascript">$(\'#click_to_call_again\').show(); clearInterval(twilio_interval);</script>';
		}
		
		exit;
	}
	
	public function twilio()
	{
		$call_sid = $this->Session->read('twilio_call_sid');
		
		if (!empty($call_sid)) {
			echo __('Call already in progress');
			exit;
		}
		
		$order_id = $this->Session->read('successful_order_id');
		
		// the number of the customer to be verified
		$verification_phone_number = $this->Session->read('verification_phone_number');
		
		$first_name = $this->Session->read('verification_first_name');
		
		if (empty($order_id) || empty($verification_phone_number)) {
			$this->Session->setFlash('Invalid page', 'default', array('class' => 'error'));
			$this->redirect(array('controller' => 'configurator', 'action' => 'configure', 'index'));
		}
		
		App::import('Vendor', 'Services/Twilio/Twilio', array('file' =>'Services/Twilio/Twilio.php'));
		
		$sid = Configure::read('Twilio.sid');
		
		$token = Configure::read('Twilio.token');
		
		// both the support representative and the user will see this number as the caller ID
		$caller_id = Configure::read('Twilio.caller_id');
		
		$number = Configure::read('Twilio.number');
		
		$client = new Services_Twilio($sid, $token);
		
		$call = $client->account->calls->create($caller_id, $number, Configure::read('twilio_callback_host') . '/twilio-callback/' . urlencode($number) . '/' . urlencode($order_id) . '/' . urlencode($verification_phone_number) . '/' . urlencode($first_name));
		
		if (!empty($call->sid)) {
			$this->Session->write('twilio_call_sid', $call->sid);
			echo __('Getting ready', ' . . .');
		} else {
			echo __('There was a problem dialing; please check the phone number');
		}
		exit;
	}
}
