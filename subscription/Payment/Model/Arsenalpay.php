<?php

class Payment_Model_Arsenalpay extends Payment_Model_Abstract {
	protected $__widget_url = 'https://arsenalpay.ru/widget.html';
	protected $__api_url = 'https://arsenalpay.ru/api/';
	protected $__ap_subscriptions = false;


	public function __construct(
		$widget_id = false, $widget_key = false, $callback_key = false,
		$client_id = false, $client_secret = false
	) {

		$this->_code = "arsenalpays";

		if ($widget_id AND $widget_key AND $callback_key) {

			$this->__widget_id     = $widget_id;
			$this->__widget_key    = $widget_key;
			$this->__callback_key  = $callback_key;
			$this->__client_id     = $client_id;
			$this->__client_secret = $client_secret;

		}
		else {
			$provider_name = new Api_Model_Provider();
			$provider_name->find($this->_code, "code");
			$keys = $provider_name->getKeys();

			foreach ($keys as $key) {
				switch ($key->getKey()) {
					case "widget_id":
						$this->__widget_id = $key->getValue();
						break;
					case "widget_key":
						$this->__widget_key = $key->getValue();
						break;
					case "callback_key":
						$this->__callback_key = $key->getValue();
						break;
					case "client_id":
						$this->__client_id = $key->getValue();
						break;
					case "client_secret":
						$this->__client_secret = $key->getValue();
						break;
				}
			}

			if (!$this->__widget_id OR !$this->__widget_key OR !$this->__callback_key) {
				throw new Exception("Error, Arsenalpay is not properly set up.");
			}
		}
		$this->logger = Zend_Registry::get("logger");
	}

	public function getPaymentData($order) {
		$return_url = parent::getUrl(
			'subscription/application/success',
			array('order_id' => $order->getId(), 'payment_method' => $this->_code)
		);
		$cancel_url = parent::getUrl('subscription/application/cancel', array('payment_method' => $this->_code));

		$this->setOrder($order)
		     ->setReturnUrl($return_url)
		     ->setCancelUrl($cancel_url);

		$data = array(
			'destination' => $order->getId(),
			'userId'      => $this->getSession()->getAdmin()->getId(),
			'amount'      => number_format($order->getTotal(), 2, ".", ""),
		);

		$arsenalpay_url = $this->_getUrl($data);

		return array("url" => $arsenalpay_url);
	}

	private function _getUrl($params) {
		$user_id     = $params['userId'];
		$destination = $params['destination'];
		$amount      = $params['amount'];
		$widget      = $this->__widget_id;
		$widget_key  = $this->__widget_key;
		$nonce       = md5(microtime(true) . mt_rand(100000, 999999));
		$sign_param  = "$user_id;$destination;$amount;$widget;$nonce";
		$widget_sign = hash_hmac('sha256', $sign_param, $widget_key);

		$params = array_merge(
			$params,
			array(
				'widget'     => $widget,
				'nonce'      => $nonce,
				'widgetSign' => $widget_sign,
			)
		);

		$params_query = http_build_query($params);
		$url          = $this->__widget_url . '?' . $params_query;

		return $url;
	}

	/**
	 * @param array $callback_params
	 */
	public function processCallback($callback_params) {
		if (!$this->checkParams($callback_params)) {
			$this->exitf('ERR', $callback_params);
		}
		$function = $callback_params['FUNCTION'];
		$KEY      = $this->__callback_key;
		if (!($this->checkSign($callback_params, $KEY))) {
			$this->log('Error: invalid sign');
			$this->exitf('ERR', $callback_params);
		}
		$order = new Sales_Model_Order();
		$order->find($callback_params['ACCOUNT']);
		if (!$order->getId()) {
			$this->log('Order #' . $callback_params['ACCOUNT'] . ' not found');
			if ($function == 'check') {
				$this->exitf('NO', $callback_params);
			}
			$this->exitf('ERR', $callback_params);
		}
		$arsenalpay_order = new Arsenalpays_Model_Order();
		$arsenalpay_order->find($order->getId(), 'order_id');
		if (!$arsenalpay_order->getId()) {
			$arsenalpay_order->setData(array(
				'order_id' => $order->getId(),
				'status'   => '',
			));
			$arsenalpay_order->save();
		}

		switch ($function) {
			case 'check':
				$this->callbackCheck($callback_params, $order, $arsenalpay_order);
				break;

			case 'payment':
				$this->callbackPayment($callback_params, $order, $arsenalpay_order);
				break;

			case 'cancel':
				$this->callbackCancel($callback_params, $order, $arsenalpay_order);
				break;

			case 'cancelinit':
				$this->callbackCancel($callback_params, $order, $arsenalpay_order);
				break;

			case 'hold':
				$this->callbackHold($callback_params, $order, $arsenalpay_order);
				break;

			default: {
				$this->log('Not supporting function - ' . $function);
				$this->exitf('ERR', $callback_params);
			}
		}
	}

	/**
	 * @param array                   $callback_params
	 * @param Sales_Model_Order       $order
	 * @param Arsenalpays_Model_Order $arsenalpay_order
	 */
	private function callbackCheck($callback_params, $order, $arsenalpay_order) {
		$rejected_statuses = array(
			'payment',
			'cancel',
			'refund',
			'reverse',
		);

		if (in_array($arsenalpay_order->getStatus(), $rejected_statuses) && $arsenalpay_order->getStatus()) {
			$this->log(
				'Aborting, Order #' . $callback_params['ACCOUNT'] . ' has rejected status(' . $arsenalpay_order->getStatus() . ')'
			);
			$this->exitf('NO', $callback_params);
		}
		$total           = $order->getTotal();
		$isCorrectAmount = ($callback_params['MERCH_TYPE'] == 0 && $total == $callback_params['AMOUNT']) ||
		                   ($callback_params['MERCH_TYPE'] == 1 && $total >= $callback_params['AMOUNT'] && $total == $callback_params['AMOUNT_FULL']);

		if (!$isCorrectAmount) {
			$this->log('Check error: Amounts do not match (request amount ' . $callback_params['AMOUNT'] . ' and ' . $total . ')');
			$this->exitf("NO", $callback_params);
		}

		$answer           = "YES";
		$ofd              = null;
		$isFiscalRequired = (isset($callback_params['OFD']) && $callback_params['OFD'] == '1');
		if ($isFiscalRequired) {
			$ofd = $this->prepareFiscalDocument($order, $callback_params['RRN']);
			if (!$ofd) {
				$this->log("Check error: can`t prepare fiscal document");
				$this->exitf("ERR_FISCAL", $callback_params);
			}
		}

		$arsenalpay_order->setData(array('status' => $callback_params['STATUS']));
		$arsenalpay_order->save();
		$this->exitf($answer, $callback_params, $ofd);
	}

	/**
	 * @param Sales_Model_Order $order
	 * @param string            $transaction_id
	 *
	 * @return string
	 */
	private function prepareFiscalDocument($order, $transaction_id) {
		$tax_rate = $order->getData("tax_rate");

		$fiscal = array(
			"id"      => strval($transaction_id),
			"type"    => "sell",
			"receipt" => array(
				"attributes" => array(
					"email" => $order->getAdminEmail(),
					"phone" => $order->getAdminPhone(),
				),
				"items"      => array(),
			)
		);

		foreach ($order->getLines() as $line) {
			/**
			 * @var Sales_Model_Order_Line $line
			 */
			$total_excl_tax = $line->getTotalPriceExclTax();
			$total          = $total_excl_tax * ($tax_rate / 100) + $total_excl_tax;

			$fiscal['receipt']['items'][] = array(
				"name"     => $line->getName(),
				"price"    => $total,
				"quantity" => $line->getQty(),
				"sum"      => $line->getQty() * $total,
//				"tax"      => "",
			);
		}

		$str = json_encode($fiscal);

		return $str;

	}

	/**
	 * @param array                   $callback_params
	 * @param Sales_Model_Order       $order
	 * @param Arsenalpays_Model_Order $arsenalpay_order
	 */
	private function callbackCancel($callback_params, $order, $arsenalpay_order) {
		$rejected_statuses = array(
			'payment',
			'refund',
			'reverse',
		);

		if (in_array($arsenalpay_order->getStatus(), $rejected_statuses)) {
			$this->log('Aborting, Order #' . $callback_params['ACCOUNT'] . ' has rejected status(' . $arsenalpay_order->getStatus() . ')');
			$this->exitf('ERR', $callback_params);
		}

		$arsenalpay_order->setData(array('status' => 'cancel'));
		$arsenalpay_order->save();
		$this->exitf('OK', $callback_params);
	}

	/**
	 * @param array                   $callback_params
	 * @param Sales_Model_Order       $order
	 * @param Arsenalpays_Model_Order $arsenalpay_order
	 */
	private function callbackPayment($callback_params, $order, $arsenalpay_order) {
		$required_statuses = array(
			'check',
			'hold',
		);

		if (!in_array($arsenalpay_order->getStatus(), $required_statuses)) {
			$this->log('Aborting, Order #' . $callback_params['ACCOUNT'] . ' has rejected status(' . $arsenalpay_order->getStatus() . ')');
			$this->exitf('ERR', $callback_params);
		}
		$total           = $order->getTotal();
		$isCorrectAmount = ($callback_params['MERCH_TYPE'] == 0 && $total == $callback_params['AMOUNT']) ||
		                   ($callback_params['MERCH_TYPE'] == 1 && $total >= $callback_params['AMOUNT'] && $total == $callback_params['AMOUNT_FULL']);

		if (!$isCorrectAmount) {
			$this->log('Payment error: Amounts do not match (request amount ' . $callback_params['AMOUNT'] . ' and ' . $total . ')');
			$this->exitf("ERR", $callback_params);
		}
		$arsenalpay_order->setData(
			array(
				'status' => $callback_params['STATUS'],
			)
		);
		$arsenalpay_order->save();
		$this->exitf('OK', $callback_params);
	}

	private function callbackHold($callback_params, $order, $arsenalpay_order) {
		$required_statuses = array(
			'hold',
			'check'
		);
		if (!in_array($arsenalpay_order->getStatus(), $required_statuses)) {
			$this->log('Aborting, Order #' . $callback_params['ACCOUNT'] . ' has not been checked. Order has status (' . $arsenalpay_order->getStatus() . ')');
			$this->exitf('ERR', $callback_params);
		}
		$total           = $order->getTotal();
		$isCorrectAmount = ($callback_params['MERCH_TYPE'] == 0 && $total == $callback_params['AMOUNT']) ||
		                   ($callback_params['MERCH_TYPE'] == 1 && $total >= $callback_params['AMOUNT'] && $total == $callback_params['AMOUNT_FULL']);

		if (!$isCorrectAmount) {
			$this->log('Hold error: Amounts do not match (request amount ' . $callback_params['AMOUNT'] . ' and ' . $total . ')');
			$this->exitf("ERR", $callback_params);
		}
		$arsenalpay_order->setData(array('status' => $callback_params['STATUS']));
		$arsenalpay_order->save();
		$this->exitf('OK', $callback_params);
	}


	private function checkParams($callback_params) {
		$required_keys = array
		(
			'ID',           /* Merchant identifier */
			'FUNCTION',     /* Type of request to which the response is received*/
			'RRN',          /* Transaction identifier */
			'PAYER',        /* Payer(customer) identifier */
			'AMOUNT',       /* Payment amount */
			'ACCOUNT',      /* Order number */
			'STATUS',       /* When /check/ - response for the order number checking, when
									// payment/ - response for status change.*/
			'DATETIME',     /* Date and time in ISO-8601 format, urlencoded.*/
			'SIGN',         /* Response sign  = md5(md5(ID).md(FUNCTION).md5(RRN).md5(PAYER).md5(request amount).
									// md5(ACCOUNT).md(STATUS).md5(PASSWORD)) */
		);

		/**
		 * Checking the absence of each parameter in the post request.
		 */
		foreach ($required_keys as $key) {
			if (empty($callback_params[$key]) || !array_key_exists($key, $callback_params)) {
				$this->log('Error in callback parameters ERR' . $key);

				return false;
			}
			else {
				$this->log(" $key=$callback_params[$key]");
			}
		}
		if ($callback_params['FUNCTION'] != $callback_params['STATUS']) {
			$this->log(
				"Error: FUNCTION ({$callback_params['FUNCTION']} not equal STATUS ({$callback_params['STATUS']})"
			);

			return false;
		}

		return true;
	}

	private function checkSign($callback, $pass) {

		$validSign = ($callback['SIGN'] === md5(
				md5($callback['ID']) .
				md5($callback['FUNCTION']) . md5($callback['RRN']) .
				md5($callback['PAYER']) . md5($callback['AMOUNT']) . md5($callback['ACCOUNT']) .
				md5($callback['STATUS']) . md5($pass)
			)) ? true : false;

		return $validSign;
	}

	private function log($msg) {
		$this->logger->log($msg, Zend_Log::DEBUG);
	}

	private function exitf($response, $callback_params, $ofd = null) {
		if (isset($callback_params['FORMAT']) && $callback_params['FORMAT'] == 'json') {
			if (isset($ofd) && $ofd != null) {
				$response = json_encode(array(
					"response" => $response,
					"ofd"      => $ofd
				));
			}
			else {
				$response = json_encode(array(
					"response" => $response
				));
			}

		}
		$this->log($response);
		echo $response;
		die();
	}

	public function success() {
		$arsenalpay_order = new Arsenalpays_Model_Order();
		$arsenalpay_order->find($this->getOrder()->getId(), 'order_id');
		if ($arsenalpay_order->getId() && $arsenalpay_order->getStatus() == 'payment') {
			$arsenalpay_subscription_id = '';
			if ($this->getOrder()->isRecurrent()) {
				$token    = $this->getToken();
				$response = $this->getSubscriptions($token);
				if (key_exists('error', $response) && $response['error']) {
					$this->log(
						'Error during getting subscription in Payment_Model_Arsenalpay::success for order #'
						. $this->getOrder()->getId() . ':' . $response['error']
					);

					return false;
				}
				$ap_subscriptions = $response['items'];
				foreach ($ap_subscriptions as $a_s) {
					if ($a_s['destination'] == $this->getOrder()->getId()) {
						$arsenalpay_subscription_id = $a_s['id'];
						break;
					}
				}
			}
			$is_recurrent = $arsenalpay_subscription_id ? 1 : 0;
			//keys will be saved in subscription_application_detail table
			$data = array(
				'payment_data' => array(
					'is_recurrent'               => $is_recurrent,
					'arsenalpay_subscription_id' => $arsenalpay_subscription_id,
				)
			);

			return $data;
		}
		throw new Exception('Order was not paid');
	}

	public function manageRecurring() {
		if (!$this->getData("is_active")) {
			$token = $this->getToken();
			if (!$token) {
				return false;
			}
			$response = $this->deleteSubscription($this->getData('arsenalpay_subscription_id'), $token);
			if (key_exists('error', $response) || $response['code'] != 200) {
				return false;
			}

			return true;
		}
		else {
			$this->log('Arsenalpay: Reactivate subscription is not support');

			return false;
		}
	}

	/**
	 * @param      $arsenalpay_subscription_id
	 * @param bool $token
	 *
	 * @return bool|stdClass
	 */
	public function getSubscriptionInfo($arsenalpay_subscription_id, $token = false) {
		/*
		if(!$arsenalpay_subscription_id) {
			$result = new stdClass();
			$result->status = "active";
			return $result;
		}
		*/
		if ($token == false) {
			$token = $this->getToken();
		}
		if (!$token) {
			return false;
		}
		$subscriptions = $this->getSubscriptions($token);
		if ((key_exists('error', $subscriptions) && $subscriptions['error']) || $subscriptions['total'] == 0) {
			return false;
		}
		$items   = $subscriptions['items'];
		$result  = new stdClass();
		$founded = false;
		foreach ($items as $item) {
			if ($item['id'] == $arsenalpay_subscription_id) {
				foreach ($item as $k => $v) {
					$result->$k = $v;
				}
				$founded = true;
				break;
			}
		}
		if ($founded) {
			$result->status = "active";

			return $result;
		}

		return false;
	}

	public function deleteSubscription($arsenalpay_subscription_id, $token) {
		$curl       = curl_init();
		$url        = $this->__api_url . 'v1/subscriptions/' . $arsenalpay_subscription_id;
		$curlParams = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $token),
			CURLOPT_CUSTOMREQUEST  => "DELETE",
		);

		curl_setopt_array($curl, $curlParams);
		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			$errors = curl_error($curl);
			curl_close($curl);
			$this->log("CURL delete subscription request error n° for subscription #" . $arsenalpay_subscription_id . ': '
			           . print_r($errors, true) . ' - response: ' . print_r($response, true));

			return array('error' => 'curl_error', 'error_description' => '');
		}
		else {
			curl_close($curl);
			$json = json_decode($response, true);
			if (key_exists('error', $json) && $json['error']) {
				$this->log('CURL delete subscription request error for subscription #' . $arsenalpay_subscription_id . ': '
				           . $json['error'] . '. Descripton: ' . $json['error_description']);
			}
			if (key_exists('code', $json) && $json['code'] != 200) {
				$json['error']             = $json['code'];
				$json['error_description'] = $json['message'];
				$this->log('CURL delete subscription request error error for subscription #' . $arsenalpay_subscription_id . ': '
				           . $json['error'] . '. Description: ' . $json['error_description']);
			}

			return $json;
		}

	}

	/**
	 * @param       $token
	 * @param array $params UserId and MaskedPan (see https://arsenalpay.ru/documentation.html#metody)
	 * @param bool  $force
	 *
	 * @return array
	 */
	public function getSubscriptions($token, $params = array(), $force = false) {
		if (!$force && $this->__ap_subscriptions) {
			return $this->__ap_subscriptions;
		}
		$curl       = curl_init();
		$str_params = '';
		if ($params) {
			$str_params = http_build_query($params);
		}
		$url        = $this->__api_url . 'v1/subscriptions?' . $str_params;
		$curlParams = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $token),
		);
		curl_setopt_array($curl, $curlParams);
		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			$errors = curl_error($curl);
			curl_close($curl);
			$this->log("CURL subscription list request error n°: "
			           . print_r($errors, true) . ' - response: ' . print_r($response, true));

			return array('error' => 'curl_error', 'error_description' => '');
		}
		else {
			curl_close($curl);
			$json = json_decode($response, true);
			if (key_exists('error', $json) && $json['error']) {
				$this->log('CURL subscription list request error: '
				           . $json['error'] . '. Description: ' . $json['error_description']);
			}
			if (key_exists('code', $json) && $json['code'] != 200) {
				$json['error']             = $json['code'];
				$json['error_description'] = $json['message'];
				$this->log('CURL subscription list request error: '
				           . $json['error'] . '. Description: ' . $json['error_description']);
			}
			if (key_exists('total', $json) && $json['total'] > 0) {
				$this->__ap_subscriptions = $json;
			}

			return $json;
		}

	}

	public function getToken($client_id = false, $client_secret = false) {
		if (!$client_id || !$client_secret) {
			$client_id     = $this->__client_id;
			$client_secret = $this->__client_secret;
		}
		if (!$client_id || !$client_secret) {
			$this->log('Have not client_id or client_secret');

			return false;
		}
		$params     = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => "client_credentials",
		);
		$curl       = curl_init();
		$curlParams = array(
			CURLOPT_URL            => $this->__api_url . 'oauth2/token',
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => $params,
		);
		curl_setopt_array($curl, $curlParams);
		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			$errors = curl_error($curl);
			curl_close($curl);
			$this->log("CURL token request error n° " . print_r($errors, true) . ' - response: ' . print_r($response, true));

			return false;
		}
		else {
			curl_close($curl);
			$json = json_decode($response, true);
			if (key_exists('error', $json) && $json['error']) {
				$this->log('Error token request: ' . $json['error'] . '. Descripton: ' . $json['error_description']);

				return false;
			}

			if (key_exists('access_token', $json) && $json['access_token']) {
				return $json['access_token'];
			}
			$this->log('CURL subscription list request error: have not access_token in response: ' . $response);

			return false;
		}
	}
}

