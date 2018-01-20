<?php

class Subscription_Model_Db_Table_Subscription_Application extends Core_Model_Db_Table {

    protected $_name = "subscription_application";
    protected $_primary = "subscription_app_id";

    public function getSubscriptionsList() {

        $arrayCol = array('payment_method','subscription_app_id','is_active', 'expire_at','created_at');

        $select = $this->select()
            ->from(array('sa'=>$this->_name), $arrayCol)
            ->joinLeft(array('sad' => 'subscription_application_detail'),'sa.subscription_app_id = sad.subscription_app_id', array("payment_code" => "sad.value"))
            ->joinInner(array('s' => 'subscription'),'s.subscription_id = sa.subscription_id', array("sub_name" => new Zend_Db_Expr("s.name"), "allows_offline") )
            ->joinInner(array('a' => 'application'),'a.app_id = sa.app_id', array("app_id" => new Zend_Db_Expr("a.app_id"), "app_name" => new Zend_Db_Expr("a.name")) )
            ->joinLeft(array('si' => 'sales_invoice'),'si.app_id = a.app_id', array() )
            ->joinLeft(array('ad' => 'admin'),'ad.admin_id = si.admin_id', array() )
            ->joinLeft(array('adm' => 'admin'),'adm.admin_id = a.admin_id', array("admin_id" => new Zend_Db_Expr("a.admin_id"), "admin_name" => new Zend_Db_Expr("IF(si.admin_id IS NOT NULL,CONCAT(ad.firstname,' ',ad.lastname),CONCAT(adm.firstname,' ',adm.lastname))")) )
            ->where("sad.code IS NULL OR (sad.code IN ('profile_id','stripe_subscription_id','order_number','arsenalpay_subscription_id'))")
            ->where("sa.is_subscription_deleted <> 1")
            ->order("sa.created_at")
            ->group(array('subscription_app_id'))
//            ->group(array('sa.is_active', 'sa.expire_at', 'sa.created_at', 'sub_name', 'app_name', 'admin_name'))
            ->setIntegrityCheck(false)
        ;

        return $this->fetchAll($select);

    }

    public function findExpiredSubscriptions($code) {

        $date = new Zend_Date();
        $select = $this->select()
            ->from(array("sa" => $this->_name))
            ->join(array("sad" => $this->_name."_detail"), "sad.subscription_app_id = sa.subscription_app_id", array($code => "value"))
            ->where("sad.code = ?", $code)
            ->where("sa.expire_at <= ?", $date->toString("yyyy-MM-dd HH:mm:ss"))
            ->setIntegrityCheck(false)
        ;

        return $this->fetchAll($select);

    }

    public function findByDetail($code,$value) {

        $select = $this->select()
            ->from(array("sa" => $this->_name))
            ->join(
                    array("sad" => $this->_name."_detail"),
                    "sad.subscription_app_id = sa.subscription_app_id",
                    array($code => "value") // $code brainfuck useless ???
            ) 
            ->where("sad.code = ?", $code)
            ->where("sad.value = ?", $value)
            ->setIntegrityCheck(false)
        ;

        return $this->fetchRow($select);

    }

}