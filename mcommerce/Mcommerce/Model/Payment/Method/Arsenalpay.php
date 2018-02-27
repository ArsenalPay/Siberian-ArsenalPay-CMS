<?php

class Mcommerce_Model_Payment_Method_Arsenalpay extends Mcommerce_Model_Payment_Method_Abstract {
	private $_supported_currency_codes = array("RUB");
//	protected $_code = 'arsenalpay';
	protected $__arsenalpay_url = "https://arsenalpay.ru/widget.html";


	public function __construct($params = array()) {
		parent::__construct($params);
		$this->_db_table = 'Mcommerce_Model_Db_Table_Payment_Method_Arsenalpay';

		return $this;
	}


	public function getUrl($value_id) {
		if (!$this->_isValid()) {
			return false;
		}
		$cart = $this->getMethod()->getCart();

		$total       = round($cart->getTotal(), 2);
		$destination = $cart->getId();
		$customer_id = $cart->getCustomerId();
		$widget_id   = $this->getWidgetId();
		$widget_key  = $this->getWidgetKey();
		$nonce       = md5(microtime(true) . mt_rand(100000, 999999));
		$sign_data   = "$customer_id;$destination;$total;$widget_id;$nonce";
		$widget_sign = hash_hmac('sha256', $sign_data, $widget_key);
		$params      = array(
			'amount'      => $total,
			'destination' => $destination,
			'widget'      => $widget_id,
			'userId'      => $customer_id,
			'widgetSign'  => $widget_sign,
			'nonce'       => $nonce
		);
		$params = http_build_query($params);
		$url = $this->__arsenalpay_url . "?" . $params;

		return $url;
	}

	public function getFormUrl($value_id) {
		if (!$this->_isValid()) {
			return false;
		}
		$url = parent::getPath('/arsenalpay/mobile_widget', array('value_id' => $value_id));

		return $url;
	}


	public function getFormUris($valueId) {
		if ($this->getOpenWidgetInBrowser()) {
			return [
				'url'      => $this->getUrl($valueId),
				'form_url' => null
			];
		}
		else {
			return [
				'url'      => null,
				'form_url' => $this->getFormUrl($valueId)
			];

		}

	}

	public function isOnline() {
		return true;
	}

	public function pay($id = null) {
		if (!$this->_isValid()) {
			return false;
		}
		$data      = $this->getMethod()->getData();
		$validator = new Arsenalpay_Model_Validator();
		$isValid   = $validator->validate($data);

		return $isValid;
	}

	public function setMethod($method) {

		if ($method->getStoreId()) {
			$this->find($method->getStoreId(), 'store_id');
		}

		$this->setData('method', $method);

		return $this;
	}

	public function isCurrencySupported() {
		$currency = Core_Model_Language::getCurrentCurrency();

		return in_array($currency->getShortName(), $this->_supported_currency_codes);
	}

	protected function _isValid() {
		return (!empty($this->getWidgetId()) && !empty($this->getSecret()) && !empty($this->getWidgetKey()));
	}

}
