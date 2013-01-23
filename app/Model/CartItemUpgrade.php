<?php
class CartItemUpgrade extends AppModel {
	var $name = 'CartItemUpgrade';

	var $belongsTo = array(
		'CartItem' => array(
			'className' => 'CartItem',
			'foreignKey' => 'cart_item_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
