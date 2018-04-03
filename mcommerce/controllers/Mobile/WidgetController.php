<?php

class Arsenalpay_Mobile_WidgetController extends Mcommerce_Controller_Mobile_Default {

	public function getoptionsAction() {
		$cart   = $this->getCart();
		$amount = round($cart->getTotal(), 2);

		$payment                = $this->getCart()->getPaymentMethod()->getInstance();
		$destination            = $cart->getId();
		$customer_id            = $cart->getCustomerId();
		$widget                 = $payment->getWidgetId();
		$widget_key             = $payment->getWidgetKey();
		$nonce                  = md5(microtime(true) . mt_rand(100000, 999999));
		$sign_data              = "$customer_id;$destination;$amount;$widget;$nonce";
		$widget_sign            = hash_hmac('sha256', $sign_data, $widget_key);
		$open_widget_in_browser = $payment->getOpenWidgetInBrowser() ? 1 : 0;
		$html                   = array(
			'amount'      => $amount,
			'destination' => $destination,
			'widget'      => $widget,
			'userId'      => $customer_id,
			'widgetSign'  => $widget_sign,
			'nonce'       => $nonce,
		);
		$request_params         = http_build_query($html);
		$arsenalpay_url         = "https://arsenalpay.ru/widget.html?" . $request_params;

		$html['openWidgetInBrowser'] = $open_widget_in_browser;
		$html['url']                 = $arsenalpay_url;

		//Для совместимости с v4.2.*
		$html['total']     = $amount;
		$html['widget_id'] = $widget;

		try {
			$payment_session = new Arsenalpay_Model_PaymentSession();
			$payment_session->find($destination, 'cart_id');
			if (!$payment_session->getCartId()) {
				$payment_session->setCartId($destination);
			}
			$customer_uuid = $this->getRequest()->getParam('customer_uuid', null);
			$payment_session->setData('customer_uuid', $customer_uuid);
			$payment_session->save();
		}
		catch (Exception $e) {
			$logger = Zend_Registry::get("logger");
			$logger->log("Arsenalpay widget: Error during save payment session: " . $e->getMessage(), Zend_Log::DEBUG);
		}

		$this->getSession()->unsetCart();

		$this->_sendJson($html);

	}
}