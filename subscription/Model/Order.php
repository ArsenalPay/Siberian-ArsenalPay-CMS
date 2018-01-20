<?php

class Arsenalpays_Model_Order extends Core_Model_Default {

    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_db_table = 'Arsenalpays_Model_Db_Table_Order';
        return $this;
    }

}
