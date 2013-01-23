<?php

class AjaxController extends AppController
{
	
	public $uses = array('UberApi', 'UberServicePlan', 'Cart', 'CartItem', 'CartItemUpgrade', 'CartItemHostname', 'Utilities', 'Msas', 'SavedRack', 'SavedCartItem', 'SavedCartItemHostname', 'SavedCartItemUpgrade', 'SavedCart');
	
	public $components = array('Session', 'UbersmithCart');
	
	public $helpers = array('Session');
	
	public $name = 'Ajax';
	
	public function beforeFilter()
	{
		$this->layout = 'ajax';
		
		$this->view = 'plain';
	}
	
	public function remove($what)
	{
		switch ($what) {
			case 'coupon':
				if (empty($_REQUEST['coupon_code'])) {
					echo __('No coupon code specified');
					exit;
				}
				
				try {
					$this->UbersmithCart->remove_coupon($_REQUEST);
				} catch (UberException $e) {
					echo $e->getMessage();
					exit;
				}
				echo 'ok';
				exit;
				break;
		}
	}
	
	public function update($what)
	{
		switch ($what) {
			case 'quantity':
				if (empty($_REQUEST['quantity']) || empty($_REQUEST['item_id'])) {
					return;
				}
				
				// this is done behind the scenes with no user interaction, so silently apply
				$this->UbersmithCart->update_quantity($_REQUEST);
				break;
			case 'hostname':
				if (empty($_REQUEST['hostname']) || empty($_REQUEST['item_id']) || !isset($_REQUEST['hostname_id'])) {
					return;
				}
				
				// this is done behind the scenes with no user interaction, so silently apply
				$this->UbersmithCart->update_hostname($_REQUEST);
				break;
			case 'coupon':
				if (empty($_REQUEST['coupon_code'])) {
					echo __('No coupon code specified');
					exit;
				}
				
				try {
					$this->UbersmithCart->apply_coupon($_REQUEST);
				} catch (UberException $e) {
					echo $e->getMessage();
					exit;
				}
				echo 'ok';
				exit;
				break;
		}
	}
	
}