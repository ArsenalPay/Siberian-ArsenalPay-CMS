<?php

$data = array('code' => 'arsenalpay', 'name' => 'Arsenalpay', 'online_payment' => 1);


$method = new Mcommerce_Model_Payment_Method();
$method
    ->setData($data)
    ->insertOnce(array("code"));

# Copy assets at install time

Siberian_Assets::copyAssets("/app/local/modules/Arsenalpay/resources/var/apps/");

