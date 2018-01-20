<?php
$api_provider = array(
    "code" => "arsenalpays",
    "name" => "Arsenalpay",
    "icon" => "fa-arsenalpay",
    "keys" => array(
        "widget_id",
        "widget_key",
        "callback_key",
	    "client_id",
	    "client_secret",
    ),
);

$data = array(
    "code" => $api_provider["code"],
    "name" => ucfirst($api_provider["code"]),
    "icon" => $api_provider["icon"],
);

$provider = new Api_Model_Provider();
$provider
    ->setData($data)
    ->insertOnce(array("code"));

foreach ($api_provider["keys"] as $key) {
    $data = array(
        'provider_id' => $provider->getId(),
        'key'         => $key,
    );

    $key = new Api_Model_Key();
    $key
        ->setData($data)
        ->insertOnce(array("provider_id", "key"));

}
