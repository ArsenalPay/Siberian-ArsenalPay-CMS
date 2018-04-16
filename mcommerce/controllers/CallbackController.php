<?php

class Arsenalpay_CallbackController extends Mcommerce_Controller_Mobile_Default {
	public function init() {
		$this->_current_option_value = new Application_Model_Option_Value();
		$this->_current_option_value->setIsHomepage(true);

		return $this;
	}

	public function paymentAction() {
		$post_data = $this->getRequest()->getPost();
		ob_start();
		$validator      = new Arsenalpay_Model_Validator();
		$isValid        = $validator->validate($post_data);
		$function       = $post_data['STATUS'];
		$remote_address = $_SERVER["REMOTE_ADDR"];
		$log_str        = $remote_address . " : answer = ";
		$answer         = 'ERR';
		if ($isValid) {
			if ($function == 'check') {
				$answer = 'YES';
			}
			else if ($function == 'payment') {
				$cart = new Mcommerce_Model_Cart();
				$cart->find($post_data['ACCOUNT']);
				$this->_cart = $cart; //to get Promo
				$notes = '';
				try {
					$payment_session = new Arsenalpay_Model_PaymentSession();
					$payment_session->find($post_data['ACCOUNT'], 'cart_id');
					$cart->setCustomerUUID($payment_session->getCustomerUuid());
					$notes = $payment_session->getNotes();
				}
				catch (Exception $e) {
					$this->post_log('Error during get PaymentSession: ' . $e->getMessage());
				}

				$value_id = $cart->getMcommerce()->getValueId();
				$this->getCurrentOptionValue()->find($value_id); //to correct work

				$errors = $cart->check();
				if (empty($errors)) {
					// Promo!
					if ($promo = $this->getPromo()) {
						$log = Mcommerce_Model_Promo_Log::createInstance($promo, $cart);
						$log->save();

						// Use points if needed!
						if ($promo->getPoints() && $cart->getCustomerId()) {
							$points   = $promo->getPoints();
							$customer = (new Customer_Model_Customer())
								->find($cart->getCustomerId());
							// Decrease points!
							if ($customer->getId()) {
								$customerPoints = $customer->getMetaData('fidelity_points', 'points') * 1;
								$customerPoints = $customerPoints - $points;
								$customer->setMetadata('fidelity_points', 'points', $customerPoints)->save();
							}
						}
					} //if ($promo)

					$order = new Mcommerce_Model_Order();
					$order
						->fromCart($cart)
						->setStatusId(Mcommerce_Model_Order::PAID_STATUS);

					$order->setNotes($notes);

					$order->save();
					$order->setHidePaidAmount(true);
					try {
						$order->sendToCustomer();
					}
					catch (Exception $e) {
						$this->post_log("Error in send message: " . $e->getMessage());
					}
					try {
						$order->sendToStore();
					}
					catch (Exception $e) {
						$this->post_log("Error in send message: " . $e->getMessage());
					}

					$answer = 'OK';
				}
			}
			else if ($function == 'cancel' || $function == 'cancelinit') {
				$cart = new Mcommerce_Model_Cart();
				$cart->find($post_data['ACCOUNT']);
				$this->_cart = $cart;

				$value_id = $cart->getMcommerce()->getValueId();
				$this->getCurrentOptionValue()->find($value_id); //to correct work
				$order = new Mcommerce_Model_Order();
				$order
					->fromCart($cart)
					->setStatusId(Mcommerce_Model_Order::CANCEL_STATUS);
				$order->save();
				$answer = 'OK';
			}
		}
		else { //if not isValid
			if ($function == 'check') {
				$answer = 'NO';
			}
			else {
				$answer = 'ERR';
			}
		}
		$log_str .= $answer;
		ob_end_clean();
		$this->post_log($log_str);
		echo $answer;

		die();
	}

	protected function _sendHtml($html) {
		return;
	}

	public function _sendJson($data, $send = false) {
		return;
	}

	protected function _redirect($url, array $options = array()) {
		return;
	}


	private function post_log($str) {
		$logger = Zend_Registry::get("logger");
		$logger->log("Arsenalpay callback: " . $str, Zend_Log::DEBUG);

		return $str;
	}
}
