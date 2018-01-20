<?php
$schemas = (!isset($schemas)) ? array() : $schemas;
$schemas['arsenalpay_subscription_order'] = array(
    'id' => array(
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ),
    'order_id' => array(
	    'type' => 'int(11) unsigned',
	    'foreign_key' => array(
		    'table' => 'sales_order',
		    'column' => 'order_id',
		    'name' => 'FK_ARSENALPAYS_ORDER_ID_SALES_ORDER_ORDER_ID',
		    'on_update' => 'CASCADE',
		    'on_delete' => 'CASCADE',
	    ),
    ),
    'status' => array(
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ),
    'created_at' => array(
        'type' => 'datetime',
    ),
    'updated_at' => array(
        'type' => 'datetime',
    ),
);