<?php

class Payment_Model_Payment extends Core_Model_Default {

    protected $_type_payment;
    protected static $_types = array(
        "arsenalpays" => "Arsenalpay",
        "paypal" => "Paypal",
        "2checkout" => "2Checkout"
    );

    public function __construct($params = array()) {
        parent::__construct($params);
    }

    public static function getTypes() {
        return self::$_types;
    }

    public static function addPaymentType($payment_code, $payment_type) {
    	self::$_types[$payment_code] = $payment_type;
    }

    public function getAvailableMethods() {

        $methods = array();

        foreach(self::getTypes() as $payment_code => $payment_type) {
            if (Payment_Model_Payment::isSetup($payment_code)) {
                $methods[$payment_code] = $payment_type;
            }
        }

        return $methods;

    }

    public static function isSetup($method) {

        $provider_name = new Api_Model_Provider();
        $provider_name->find($method, "code");
        $keys = $provider_name->getKeys();

        foreach ($keys as $key) {
            if(!$key->getValue()) {
                return false;
            }
        }

        return true;
    }

    protected function getType($type_customer)
    {
        if (!$this->_type_payment) {
            if (!empty(self::$_types[$type_customer])) {
                $class = 'Payment_Model_' . self::$_types[$type_customer];
                $this->_type_payment = new $class();
                $this->_type_payment->addData($this->getData());
            }
        }

        return !empty($this->_type_payment) ? $this->_type_payment : null;

    }

    public function getCode() {
        if($this->getPaymentMethod()) {
            return $this->getType($this->getPaymentMethod())->getCode();
        }
        return "";
    }

    public function getPaymentData($params) {
        if($this->getType($params["payment_method"])) {
            return $this->getType($params["payment_method"])->getPaymentData($params["order"]);
        }
        return array();
    }

    public function cancel() {
        if($this->getPaymentMethod()) {
            return $this->getType($this->getPaymentMethod())->cancel();
        }
        return array();
    }

    public function success() {
        if($this->getPaymentMethod()) {
            return $this->getType($this->getPaymentMethod())
                ->setData($this->getData())
                ->setOrder($this->getOrder())
                ->success()
            ;
        }
        return array();
    }

    public function manageRecurring() {

        if($this->getPaymentMethod()) {
            return $this->getType($this->getPaymentMethod())
                ->setData($this->getData())
                ->manageRecurring()
            ;
        }
        return array();
    }

}

