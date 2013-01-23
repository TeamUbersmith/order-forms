<?php

class CartItem extends AppModel {

	var $name = 'CartItem';

	var $recursive = 2;

	var $hasMany = array(
		'CartItemUpgrade' => array(
			'className' => 'CartItemUpgrade',
			'foreignKey' => 'cart_item_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'CartItemHostname' => array(
			'className' => 'CartItemHostname',
			'foreignKey' => 'cart_item_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
	);

	var $belongsTo = array(
		'Cart' => array(
			'className' => 'Cart',
			'foreignKey' => 'cart_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
