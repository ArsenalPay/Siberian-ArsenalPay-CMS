<?php

class Arsenalpay_Mobile_WidgetController extends Mcommerce_Controller_Mobile_Default {

    public function getoptionsAction(){
        $cart = $this->getCart();
        $total = round($cart->getTotal(), 2);
        
        $payment = $this->getCart()->getPaymentMethod()->getInstance();
        $destination = $cart->getId();
        $customer_id = $cart->getCustomerId();
        $widget_id = $payment->getWidgetId();
        $widget_key = $payment->getWidgetKey();
        $nonce = md5(microtime(true) . mt_rand(100000, 999999));
        $sign_data = "$customer_id;$destination;$total;$widget_id;$nonce";
        $widget_sign = hash_hmac('sha256', $sign_data, $widget_key);
        $html = array(
            'total' => $total ,
            'destination' => $destination,
            'widget_id' => $widget_id,
            'userId' => $customer_id,
            'widgetSign' => $widget_sign,
            'nonce' => $nonce
        );

	    $this->getSession()->unsetCart();

        $this->_sendJson($html);
        
    }
}