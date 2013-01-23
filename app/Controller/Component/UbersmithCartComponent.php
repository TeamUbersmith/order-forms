<?php
/**
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

class UbersmithCartComponent extends Component
{
	
	public $components = array('Session');
	
	public $name = 'UbersmithCart';
	
	public $cart = array();
	
	public static $service_plans = null;
	
	private $coupons = null;
	
	public function __construct(ComponentCollection $collection, $settings = array())
	{
		parent::__construct($collection, $settings);
	}
	
	public static function service_plans()
	{
		if (is_null(self::$service_plans)) {
			self::$service_plans = Configure::read('Ubersmith.service_plans');
		}
		
		return self::$service_plans;
	}
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller)
	{
		$this->controller = $controller;
		
		$session_cart = $this->Session->read('Cart');
		if (!empty($session_cart)) {
			$this->cart = $this->controller->Cart->read(null, $session_cart['Cart']['id']);
		}
	}
	
	public function navigation_summary()
	{
		if (empty($this->cart['CartItem'])) {
			return array(
				'summary_monthly_price' => 0,
				'summary_setup_fee'     => 0,
				'summary_items'         => 0,
			);
		}
		
		$monthly_price_subtotal = 0;
		$setup_fee_subtotal = 0;
		
		$monthly_total = 0;
		$setup_fee_total = 0;
		
		$coupons = $this->coupons();
		
		foreach ($this->cart['CartItem'] as $key => $cart_item) {
			foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
				$selected_upgrades[$cart_item_upgrade['upgrade']] = $cart_item_upgrade['upgrade_option'];
			}
			
			$item = new stdClass;
			
			$service_plan_id = $cart_item['service_plan_id'];
			
			if (!in_array($service_plan_id, self::service_plans())) {
				continue;
			}
			
			$service_plan_api_array = array(
				'method' => 'uber.service_plan_get',
				'plan_id' => $service_plan_id,
			);
			$uber_service_plan = $this->controller->UberApi->call_api($service_plan_api_array);
			if (empty($uber_service_plan->error_code)) {
				$service_plan = $uber_service_plan->data;
			}
			else {
				continue;
			}
			
			foreach ($service_plan->pricing as $service_plan_pricing) {
				if ($service_plan_pricing->api_label != 'monthly') {
					continue;
				}
				$item->monthly_price = $service_plan_pricing->price;
				$item->setup_fee = $service_plan_pricing->setup_fee;
			}
			
			$item->quantity = $cart_item['quantity'];
			
			$monthly_price_subtotal += $item->monthly_price;
			$setup_fee_subtotal += $item->setup_fee;
			
			foreach ($service_plan->upgrades as $upgrade_id => $upgrade) {
				if (empty($upgrade->options) || !empty($upgrade->spg_data->hide_option)) {
					continue;
				}
				foreach ($upgrade->options as $upgrade_option_id => $upgrade_option) {
					if (@$selected_upgrades[$upgrade_id] != $upgrade_option_id) {
						continue;
					}
					$setup_fee_subtotal += $upgrade_option->spo_setup_fee;
					$item->setup_fee += $upgrade_option->spo_setup_fee;
					$upgrade_option_setup_fee = $upgrade_option->spo_setup_fee;
					foreach ($upgrade_option->pricing as $upgrade_pricing) {
						if ($upgrade_pricing->api_label == 'monthly') {
							$monthly_price_subtotal += $upgrade_pricing->price;
							$item->monthly_price += $upgrade_pricing->price;
							$upgrade_option_monthly_price = $upgrade_pricing->price;
						}
					}
					if (empty($coupons)) {
						continue;
					}
					foreach ($coupons as $plan_id => $coupon) {
						$coupon_id = $coupon->coupon->coupon_id;
						$monthly_price_discount_amount = $setup_fee_discount_amount = 0;
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
						// we only care about recurring discounts here
						if ($coupon_option_array[$upgrade_option_id]->discount > 0 && $coupon->coupon->recurring == '1') {
							// 0 = percent, 1 = dollar
							if ($coupon_option_array[$upgrade_option_id]->discount_type == '1') {
								$monthly_price_discount_amount = $coupon_option_array[$upgrade_option_id]->discount;
							}
							else {
								$monthly_price_discount_amount = ($upgrade_option_monthly_price * $coupon_option_array[$upgrade_option_id]->discount / 100);
								if ($monthly_price_discount_amount < 0) {
									$monthly_price_discount_amount = 0;
								}
							}
						}
						if ($coupon_option_array[$upgrade_option_id]->setup_discount > 0) {
							// 0 = percent, 1 = dollar
							if ($coupon_option_array[$upgrade_option_id]->setup_discount_type == '1') {
								$setup_fee_discount_amount = $coupon_option_array[$upgrade_option_id]->setup_discount;
							}
							else {
								$setup_fee_discount_amount = ($upgrade_option_setup_fee * $coupon_option_array[$upgrade_option_id]->setup_discount / 100);
								if ($setup_fee_discount_amount < 0) {
									$setup_fee_discount_amount = 0;
								}
							}
						}
						$item->setup_fee -= $setup_fee_discount_amount;
						$item->monthly_price -= $monthly_price_discount_amount;
					}
				}
			}
			$item->monthly_price_subtotal = ($item->monthly_price * $item->quantity);
			$item->setup_fee_subtotal = ($item->setup_fee * $item->quantity);
			
			$monthly_total += $item->monthly_price_subtotal;
			$setup_fee_total += $item->setup_fee_subtotal;
		}
		
		if (!empty($coupons)) {
			foreach ($coupons as $plan_id => $coupon) {
				$coupon_id = $coupon->coupon->coupon_id;
				if (empty($coupon->coupon->discount_value) && empty($coupon->coupon->setup_discount_value)) {
					continue;
				}
				if ($coupon->coupon->discount_value > 0 && $coupon->coupon->recurring == '1') {
					$discount_type = ($coupon->coupon->dollar == '1') ? 'dollar' : 'percent';
					if ($discount_type == 'dollar') {
						$discount_amount = $coupon->coupon->discount_value;
					}
					else {
						$discount_amount = ($monthly_total * $coupon->coupon->discount_value / 100);
					}
					if ($discount_amount > 0) {
						$monthly_total -= $discount_amount;
					}
				}
				if ($coupon->coupon->setup_discount_value > 0) {
					$discount_type = ($coupon->coupon->setup_dollar == '1') ? 'dollar' : 'percent';
					if ($discount_type == 'dollar') {
						$discount_amount = $coupon->coupon->setup_discount_value;
					}
					else {
						$discount_amount = ($setup_fee_total * $coupon->coupon->setup_discount_value / 100);
					}
					if ($discount_amount > 0) {
						$setup_fee_total -= $discount_amount;
					}
				}
			}
		}
		
		$summary_navigation = array(
			'summary_monthly_price' => $monthly_total,
			'summary_setup_fee'     => $setup_fee_total,
			'summary_items'         => count($this->cart['CartItem']),
		);
		
		return $summary_navigation;
	}
	
	public function is_in_cart($request = array())
	{
		if (empty($request['item_id'])) {
			return false;
		}
		
		if (empty($this->cart['CartItem'])) {
			return false;
		}
		
		foreach ($this->cart['CartItem'] as $cart_item) {
			if ($cart_item['id'] == $request['item_id']) {
				return true;
			}
		}
		return false;
	}
	
	public function remove_item($request = array())
	{
		if (empty($request['item_id'])) {
			throw new UberException(__('Invalid item id specified'), 1);
		}
		
		if (!$this->is_in_cart($request)) {
			throw new UberException(__('Item is not in cart'), 2);
		}
		
		$deleted = false;
		foreach ($this->cart['CartItem'] as $cart_item_id => $cart_item) {
			if ($cart_item['id'] != $request['item_id']) {
				continue;
			}
			if ($this->controller->CartItem->delete($request['item_id'])) {
				$deleted = true;
			}
		}
		
		if (!$deleted) {
			throw new UberException(__('There was an issue deleting the item from the cart'), 3);
		}
		
		return true;
	}
	
	public function update_item($request = array())
	{
		if (empty($request['item_id']) || empty($request['upgrades'])) {
			throw new UberException(__('Invalid item id or upgrades specified'), 1);
		}
		
		if (!$this->is_in_cart($request)) {
			throw new UberException(__('Item is not in cart'), 2);
		}
		
		$updated = false;
		foreach ($this->cart['CartItem'] as $cart_item) {
			if ($cart_item['id'] != $request['item_id']) {
				continue;
			}
			foreach ($cart_item['CartItemUpgrade'] as $k => $cart_item_upgrade) {
				if (empty($_POST['upgrades'][$cart_item_upgrade['upgrade']])) {
					continue;
				}
				$updated = true;
				$this->controller->CartItemUpgrade->save(array(
					'id'             => $cart_item_upgrade['id'],
					'upgrade_option' => $_POST['upgrades'][$cart_item_upgrade['upgrade']],
				));
			}
		}
		
		if (!$updated) {
			throw new UberException(__('There was a problem updating the item'), 3);
		}
		
		return true;
	}
	
	public function update_hostnames($request = array())
	{
		if (empty($request['item_id'])) {
			return false;
		}
		
		$hostnames = $this->Session->read('hostnames');
		if (empty($hostnames)) {
			$hostnames = array();
		}
		
		$quantities = $this->Session->read('quantities');
		
		$hostnames_count = count($hostnames[$request['item_id']]);
		if ($hostnames_count == $quantities[$request['item_id']]) {
			return false;
		} elseif ($hostnames_count > $quantities[$request['item_id']]) {
			// truncate the hostnames array
			$hostnames[$request['item_id']] = array_slice($hostnames[$request['item_id']], 0, $quantities[$request['item_id']]);
		} else {
			// add more elements to the hostnames array
			$hostnames[$request['item_id']] = array_merge($hostnames[$request['item_id']], array_fill(0, ($quantities[$request['item_id']] - $hostnames_count), ''));
		}
		
		$this->Session->write('hostnames', $hostnames);
		
		return true;
	}
	
	public function update_hostname($request = array())
	{
		if (empty($this->cart)) {
			return false;
		}
		
		if (empty($request['item_id']) || !isset($request['hostname_id']) || empty($request['hostname'])) {
			return false;
		}
		
		if (!$this->is_in_cart($request)) {
			return false;
		}
		
		$hostnames = $this->Session->read('hostnames');
		
		$hostnames[$request['item_id']][$request['hostname_id']] = $request['hostname'];
		
		$this->Session->write('hostnames', $hostnames);
		
		return true;
	}
	
	public function update_quantity($request)
	{
		if (empty($this->cart) || empty($request['item_id']) || !isset($request['quantity'])) {
			return false;
		}
		
		if (!$this->is_in_cart($request)) {
			return false;
		}
		
		$this->controller->CartItem->save(array(
			'id'       => $request['item_id'],
			'quantity' => $request['quantity'],
		));
		
		$quantities = $this->Session->read('quantities');
		if (empty($quantities)) {
			$quantities = array();
		}
		$quantities[$request['item_id']] = $request['quantity'];
		
		$this->Session->write('quantities', $quantities);
		
		return $this->update_hostnames(array('item_id' => $request['item_id']));
	}
	
	public function remove_coupon($request = array())
	{
		if (empty($request['coupon_code'])) {
			throw new UberException(__('No coupon code specified'), 1);
		}
		
		if (empty($this->cart)) {
			throw new UberException(__('No cart specified'), 2);
		}
		
		$coupons = $this->Session->read('coupons');
		if (empty($coupons)) {
			throw new UberException(__('No coupons applied to cart'), 3);
		}
		
		$count = count($coupons);
		$unset = false;
		for ($i = 0; $i < $count; $i++) {
			$coupon = $coupons[$i];
			if ($coupon->coupon->coupon_code == $request['coupon_code']) {
				unset($coupons[$i]);
				$unset = true;
				break;
			}
		}
		
		if (!$unset) {
			throw new UberException(__('Coupon not applied to cart'), 4);
		}
		
		$this->Session->write('coupons', $coupons);
		
		return true;
	}
	
	public function apply_coupon($request = array())
	{
		if (empty($request['coupon_code'])) {
			throw new UberException(__('No coupon code specified'), 1);
		}
		
		if (empty($this->cart)) {
			throw new UberException(__('No cart specified'), 2);
		}
		
		$coupon = $this->controller->UberApi->call_api(array(
			'method'       => 'order.coupon_get',
			'coupon_code'  => $request['coupon_code'],
		));
		
		if (!empty($coupon->error_code) && empty($coupon->data)) {
			throw new UberException(__('Invalid coupon code specified'), 3);
		}
		
		$coupons = $this->Session->read('coupons');
		if (empty($coupons)) {
			$coupons = array();
		}
		
		$cart_service_plans = array();
		if (!empty($this->cart['CartItem'])) {
			foreach ($this->cart['CartItem'] as $k => $cart_item) {
				$cart_service_plans[] = $cart_item['service_plan_id'];
			}
		}
		
		// don't add if we already have it
		foreach ($coupons as $applied_coupon) {
			if ($applied_coupon->coupon_id == $coupon->data->coupon->coupon_id) {
				throw new UberException(__('Coupon already applied to cart'), 4);
			}
		}
		
		// make sure it applies to the whole cart, or an item in the cart
		if (!empty($coupon->data->coupon->plan_id) && !in_array($coupon->data->coupon->plan_id, $cart_service_plans)) {
			throw new UberException(__('Coupon does not apply to any items in this cart'), 4);
		}
		
		$coupons[] = $coupon->data;
		
		$this->Session->write('coupons', $coupons);
		
		return true;
	}
	
	/**
	 * Fetch the coupons from Ubersmith that are in the session
	 * This does not check to make sure the user actually has something in their cart
	 * where the coupon could apply. This logic is handled elsewhere.
	 **/
	public function coupons()
	{
		if ($this->coupons !== null) {
			return $this->coupons;
		}
		$applied_coupons = $original_applied_coupons = $this->Session->read('coupons');
		$this->coupons = array();
		
		if (empty($applied_coupons)) {
			return array();
		}
		
		$count = count($applied_coupons);
		for ($i = 0; $i < $count; $i++) {
			$applied_coupon = $applied_coupons[$i];
			$coupon = $this->controller->UberApi->call_api(array(
				'method'     => 'order.coupon_get',
				'coupon_id'  => $applied_coupon->coupon->coupon_id,
			));
			if (!empty($coupon->error_code) || empty($coupon->data)) {
				// it's no longer valid
				unset($applied_coupons[$i]);
				continue;
			}
			$applied_coupons[$i] = $coupon->data;
		}
		
		if ($applied_coupons != $original_applied_coupons) {
			$this->Session->write('coupons', $applied_coupons);
		}
		
		$this->coupons = $applied_coupons;
		
		return $this->coupons;
	}
}