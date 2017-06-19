<?php
/**
 *
 * Schema definition for 'mcommerce_store_payment_method_arsenalpay'
 *
 * Last update: 2017-05-07
 *
 */
$schemas = (!isset($schemas)) ? array() : $schemas;
$schemas['mcommerce_store_payment_method_arsenalpay'] = array(
    'arsenalpay_id' => array(
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ),
    'store_id' => array(
        'type' => 'int(11) unsigned',
        'foreign_key' => array(
            'table' => 'mcommerce_store',
            'column' => 'store_id',
            'name' => 'mcommerce_store_payment_method_arsenalpay_ibfk_1',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ),
        'index' => array(
            'key_name' => 'KEY_STORE_ID',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ),
    ),
    'widget_id' => array(
        'type' => 'int(11) unsigned',
    ),
    'secret' => array(
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ),
    'widget_key' => array(
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