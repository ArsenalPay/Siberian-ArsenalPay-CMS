<?php
/**
 *
 * Schema definition for 'arsenalpay_payment_session'
 *
 */
$schemas = (!isset($schemas)) ? array() : $schemas;
$schemas['arsenalpay_payment_session'] = array(
	'id'            => array(
		'type'           => 'int(11) unsigned',
		'auto_increment' => true,
		'primary'        => true,
	),
	'cart_id'       => array(
		'type'        => 'int(11) unsigned',
		'foreign_key' => array(
			'table'     => 'mcommerce_cart',
			'column'    => 'cart_id',
			'name'      => 'mcommerce_cart_arsenalpay_payment_session_ibfk_1',
			'on_update' => 'CASCADE',
			'on_delete' => 'CASCADE',
		),
		'index'       => array(
			'key_name'   => 'KEY_CART_ID',
			'index_type' => 'BTREE',
			'is_null'    => false,
			'is_unique'  => false,
		),
	),
	'customer_uuid' => array(
		'type'      => 'varchar(255)',
		'charset'   => 'utf8',
		'collation' => 'utf8_unicode_ci',
		'is_null'   => true,
	),
);