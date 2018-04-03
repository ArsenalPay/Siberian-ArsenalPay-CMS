<?php

class Arsenalpay_Model_PaymentSession extends Core_Model_Default {

    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_db_table = 'Arsenalpay_Model_Db_Table_PaymentSession';
        return $this;
    }

}