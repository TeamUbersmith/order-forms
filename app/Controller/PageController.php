<?php

class PageController extends Controller
{
	
	public $uses = array('UberApi', 'UberServicePlan', 'Cart', 'CartItem', 'CartItemUpgrade', 'CartItemHostname', 'Utilities', 'Msas', 'SavedRack', 'SavedCartItem', 'SavedCartItemHostname', 'SavedCartItemUpgrade', 'SavedCart');
	
	public $components = array('UbersmithCart');
	
	public $name = 'Page';
	
	public function index()
	{
		$this->set(array(
			'navigation_summary' => $this->UbersmithCart->navigation_summary(),
			'title_for_layout'   => 'Sample Configurator',
			'service_plans'      => Configure::read('Ubersmith.service_plans'),
		));
		
		$this->layout = 'default';
	}
}