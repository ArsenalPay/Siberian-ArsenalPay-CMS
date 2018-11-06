<?php

class Subscription_Model_Db_Table_Subscription_Application extends Core_Model_Db_Table {

    protected $_name = "subscription_application";
    protected $_primary = "subscription_app_id";


  /**
     * @return Zend_Db_Table_Rowset_Abstract
     *
     * @deprecated use findAllfiltered() instead
     */
    public function getSubscriptionsList()
    {

        $arrayCol = ['payment_method', 'subscription_app_id', 'is_active', 'expire_at', 'created_at'];

        $select = $this->select()
            ->from(['sa' => $this->_name], $arrayCol)
            ->joinLeft(['sad' => 'subscription_application_detail'], 'sa.subscription_app_id = sad.subscription_app_id', ["payment_code" => "sad.value"])
            ->joinInner(['s' => 'subscription'], 's.subscription_id = sa.subscription_id', ["sub_name" => new Zend_Db_Expr("s.name"), "allows_offline"])
            ->joinInner(['a' => 'application'], 'a.app_id = sa.app_id', ["app_id" => new Zend_Db_Expr("a.app_id"), "app_name" => new Zend_Db_Expr("a.name")])
            ->joinLeft(['si' => 'sales_invoice'], 'si.app_id = a.app_id', [])
            ->joinLeft(['ad' => 'admin'], 'ad.admin_id = si.admin_id', [])
            ->joinLeft(['adm' => 'admin'], 'adm.admin_id = a.admin_id', ["admin_id" => new Zend_Db_Expr("a.admin_id"), "admin_name" => new Zend_Db_Expr("IF(si.admin_id IS NOT NULL,CONCAT(ad.firstname,' ',ad.lastname),CONCAT(adm.firstname,' ',adm.lastname))")])
//            ->where("sad.code IS NULL OR (sad.code IN ('profile_id','stripe_subscription_id','order_number','arsenalpay_subscription_id'))")
            ->where("sa.is_subscription_deleted <> 1")
            ->order("sa.created_at")
            ->group(['subscription_app_id'])
            ->setIntegrityCheck(false);

        return $this->fetchAll($select);

    }

    /**
     * @param $code
     * @return Zend_Db_Table_Rowset_Abstract
     * @throws Zend_Date_Exception
     */
    public function findSubscriptionsToLock()
    {
        $date = new Zend_Date();
        $select = $this->select()
            ->from(["sa" => $this->_name])
            ->join(["sad" => $this->_name . "_detail"], "sad.subscription_app_id = sa.subscription_app_id", "*")
            ->where("sa.cancelled_at <= ?", $date->toString("yyyy-MM-dd HH:mm:ss"))
            ->where('sa.is_subscription_deleted = ?', 1)
            ->setIntegrityCheck(false);

        return $this->fetchAll($select);
    }

    /**
     * @param $code
     * @return Zend_Db_Table_Rowset_Abstract
     * @throws Zend_Date_Exception
     */
    public function findExpiredSubscriptions()
    {
        $date = new Zend_Date();
        $select = $this->select()
            ->from(["sa" => $this->_name])
            ->join(["sad" => $this->_name . "_detail"], "sad.subscription_app_id = sa.subscription_app_id", "*")
            ->where('sa.expire_at <= ?', $date->toString("yyyy-MM-dd HH:mm:ss"))
            ->where('sa.cancelled_at IS NULL')
            ->where('sa.is_subscription_deleted = ?', 0)
            ->setIntegrityCheck(false);

        return $this->fetchAll($select);
    }

    public function findByDetail($code, $value)
    {

        $select = $this->select()
            ->from(["sa" => $this->_name])
            ->join(
                ["sad" => $this->_name . "_detail"],
                "sad.subscription_app_id = sa.subscription_app_id",
                [$code => "value"] // $code brainfuck useless ???
            )
            ->where("sad.code = ?", $code)
            ->where("sad.value = ?", $value)
            ->setIntegrityCheck(false);

        return $this->fetchRow($select);
    }

    /**
     * @param Admin_Model_Admin $admin
     * @return Subscription_Model_Subscription_Application[]
     */
    public function findAllForAdmin($admin)
    {
        $select = $this->select()
            ->from(
                [
                    'sub_app' => $this->_name
                ],
                '*'
            )
            ->join(
                [
                    'sub' => 'subscription',
                ],
                'sub_app.subscription_id = sub.subscription_id',
                [
                    'name' => 'name',
                    'description' => 'description',
                    'setup_fee' => 'setup_fee',
                    'payment_frequency' => 'payment_frequency',
                    'regular_payment' => 'regular_payment',
                    'app_quantity' => 'app_quantity',
                ]
            )
            ->join(
                [
                    'app' => 'application',
                ],
                'app.app_id = sub_app.app_id',
                [
                    'app_name' => 'name',
                ]
            );

        $allApplications = $admin->getApplications();
        $appIds = [];
        foreach ($allApplications as $adminApplication) {
            $appIds[] = $adminApplication->getId();
        }

        $select
            ->where('sub_app.app_id IN (?)', $appIds)
            ->order([
                'sub_app.is_subscription_deleted ASC',
                'sub_app.updated_at DESC'
            ])
            ->setIntegrityCheck(false);

        return $this->fetchAll($select);
    }

    /**
     * @param array $values
     * @param null $order
     * @param array $params
     * @return Zend_Db_Table_Rowset_Abstract
     * @throws Zend_Db_Select_Exception
     */
    public function findAllfiltered($values = [], $order = null, $params = [])
    {
        $select = $this->select()
            ->from(
                [
                    'sa' => $this->_name
                ],
                [
                    'last_check_status',
                    'last_check_message',
                    'subscription_id',
                    'app_quantity',
                    'payment_method',
                    'subscription_app_id',
                    'is_subscription_deleted',
                    'parent_id',
                    'is_active',
                    'expire_at',
                    'created_at',
                ]
            )
            ->joinLeft(
                [
                    'sad' => 'subscription_application_detail'
                ],
                'sa.subscription_app_id = sad.subscription_app_id',
                [
                    'payment_code' => 'sad.value',
                ]
            )->joinLeft(
                [
                    'parent' => 'subscription_application'
                ],
                'sa.parent_id = parent.subscription_app_id',
                [
                    'parent_is_subscription_deleted' => 'parent.is_subscription_deleted',
                    'parent_is_active' => 'parent.is_active',
                ]
            )->joinInner(
                [
                    's' => 'subscription'
                ],
                's.subscription_id = sa.subscription_id',
                [
                    'sub_name' => new Zend_Db_Expr('s.name'),
                    'allows_offline',
                ]
            )->joinInner(
                [
                    'a' => 'application'
                ],
                'a.app_id = sa.app_id',
                [
                    'app_id' => new Zend_Db_Expr('a.app_id'),
                    'app_name' => new Zend_Db_Expr('a.name')
                ]
            )->joinLeft(
                [
                    'si' => 'sales_invoice'
                ],
                'si.app_id = a.app_id',
                []
            )->joinLeft(
                [
                    'ad' => 'admin'
                ],
                'ad.admin_id = si.admin_id',
                []
            )->joinLeft(
                [
                    'adm' => 'admin'
                ],
                'adm.admin_id = a.admin_id',
                [
                    'admin_id' => new Zend_Db_Expr('a.admin_id'),
                    'admin_name' => new Zend_Db_Expr('IF(si.admin_id IS NOT NULL,CONCAT(ad.firstname,\' \',ad.lastname),CONCAT(adm.firstname,\' \',adm.lastname))'),
                    'admin_email' => new Zend_Db_Expr('IF(si.admin_id IS NOT NULL,ad.email,adm.email)'),
                ]
            )
            ->order($order)
            ->group(['subscription_app_id']);

        foreach ($values as $filter) {
            $select->where($filter['filter'], $filter['value']);
        }

        $limit = null;
        $offset = null;
        if (!empty($params)) {
            $limit = !empty($params['limit']) ? $params['limit'] : null;
            $offset = !empty($params['offset']) ? $params['offset'] : null;

            $select->limit($limit, $offset);
        }

        $select->order($order);
        $select->setIntegrityCheck(false);

        return $this->fetchAll($select);
    }

    /**
     * @param $method
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getAllActiveSubs($method)
    {
        $code = null;
        switch ($method) {
            case 'stripe':
                $code = 'stripe_subscription_id';
                break;
        }

        $query = "
SELECT sad.value AS subscription_id
FROM {$this->_name} AS sa
JOIN subscription_application_detail AS sad
    ON sa.subscription_app_id = sad.subscription_app_id
    AND sad.code = '{$code}'
WHERE sa.is_active = 1
AND sad.value LIKE 'sub_%'
AND sa.payment_method = '{$method}'
GROUP BY sa.subscription_app_id
ORDER BY sa.subscription_id DESC";

        $result = $this->_db->query($query);

        return $result;
    }



}