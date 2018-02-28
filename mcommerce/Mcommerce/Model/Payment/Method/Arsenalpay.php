<?php

class Mcommerce_Model_Payment_Method_Arsenalpay extends Mcommerce_Model_Payment_Method_Abstract {
	private $_supported_currency_codes = array("RUB");
//	protected $_code = 'arsenalpay';


	public function __construct($params = array()) {
		parent::__construct($params);
		$this->_db_table = 'Mcommerce_Model_Db_Table_Payment_Method_Arsenalpay';

		return $this;
	}


	public function _getUrl($value_id) {
		if (!$this->_isValid()) {
			return false;
		}
		$url = parent::getUrl('/arsenalpay/mobile_widget', array('value_id' => $value_id));

		return $url;
	}

	public function _getFormUrl($value_id) {
		if (!$this->_isValid()) {
			return false;
		}
		$url = parent::getPath('/arsenalpay/mobile_widget', array('value_id' => $value_id));

		return $url;
	}


	public function getFormUris($valueId) {
		return [
			'url'      => null,
			'form_url' => $this->_getFormUrl($valueId)
		];
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
