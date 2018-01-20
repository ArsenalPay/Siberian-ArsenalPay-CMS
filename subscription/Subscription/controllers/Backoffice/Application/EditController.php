<?php

class Subscription_Backoffice_Application_EditController extends Backoffice_Controller_Default
{

    public function loadAction() {

        $html = array(
            "title" => __("Subscription"),
            "icon" => "fa-list-ul"
        );

        $this->_sendHtml($html);

    }

    public function findAction() {


        $subscription = new Subscription_Model_Subscription_Application();
        $subscription->find($this->getRequest()->getParam("subscription_app_id"));

        $data = array();
        if($subscription->getSubscriptionAppId()) {

            $data["subscription"] = $subscription->getData();

            if($data["subscription"]['expire_at']) {
                $date = new Zend_Date($data["subscription"]['expire_at'], Zend_Date::ISO_8601);
                $data["subscription"]['expire_at'] = $date->toString("MM/dd/yyyy");
            }

            $data["section_title"] = __("Edit the subscription %s", $subscription->getSubscriptionAppId() );
        } else {
            $data["section_title"] = __("Create a new subscription");
        }

        if($subscription->getPaymentMethod() == "offline") {
            $data["section_invoice_title"] = __("Edit an invoice for this plan");
            $today = new Zend_Date(null, Zend_Date::ISO_8601);
            $data["today_date"] = $today->toString("MM/dd/yyyy");
        }

        $this->_sendHtml($data);

    }

    public function saveAction() {

        if($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                $subscription = new Subscription_Model_Subscription_Application();

                $is_active = !empty($data["is_active"]);

                if(!empty($data["subscription_app_id"])) {
                    $subscription->find($data["subscription_app_id"]);
                    $data = array_merge($data, $subscription->getDetails());
                }

                $objsubscription = $subscription->getSubscription();

                if(empty($data["expire_at"])) {
                    $data["expire_at"] = null;
                } else {
                    $data["expire_at"] = new Zend_Date($data["expire_at"], "MM/dd/yyyy");
                    $data["expire_at"] = $data["expire_at"]->toString('yyyy-MM-dd HH:mm:ss');
                }
//                $arrayDate = explode('/',$data["expire_at"]);
//                $data["expire_at"] = $arrayDate[2].'-'.$arrayDate[0].'-'.$arrayDate[1];

                $payment_response = true;

                if(!$objsubscription->isFree()) {
                    if ($is_active != $subscription->getIsActive()) {
                    	if($data['payment_method'] == 'arsenalpays'){
		                    $payment = new Payment_Model_Arsenalpay();
	                    } else {
		                    $payment = new Payment_Model_Payment();
	                    }
                        $payment_response = $payment->setData($data)->manageRecurring();
                    }
                } else {
                    unset($data["payment_method"]);
                    unset($data["expire_at"]);
                }

                $subscription->addData($data);
                $subscription->save();

                $message = __("Subscription successfully saved.");

                if(!$payment_response) {
                    $message .= " ";
                    $message .= __("We were unable to deactivate their subscription on %s. Please, do it manually.", ucfirst($subscription->getPaymentMethod()));
                }

                $data = array(
                    "success" => 1,
                    "message" => $message
                );

            } catch(Exception $e) {
                $data = array(
                    "error" => 1,
                    "message" => $e->getMessage()
                );
            }

            $this->_sendHtml($data);
        }

    }

    public function editinvoiceAction() {

        if($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                $order = new Sales_Model_Order();
                //BE CAREFULL : we have to use a find last
                //Because clients can "spam" the order creation
                $order = $order->findLast(array("subscription_id" => $data["subscription_id"], "app_id" => $data["app_id"]));

                if($order->getId()) {

                    $order->pay($data["payment_date"], $data["creation_date"]);

                    $message = __("Your invoice has been edited successfully.");

                    $data = array(
                        "success" => 1,
                        "message" => $message
                    );
                } else {
                    throw new Siberian_Exception(__("An error occurred while editing your invoice : can't find order"));
                }

            } catch(Exception $e) {
                $data = array(
                    "error" => 1,
                    "message" => $e->getMessage()
                );
            }

            $this->_sendHtml($data);
        }

    }

}
