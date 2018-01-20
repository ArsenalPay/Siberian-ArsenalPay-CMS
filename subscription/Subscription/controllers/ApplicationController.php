<?php

class Subscription_ApplicationController extends Application_Controller_Default
{

    protected $_order;

    public function createAction() {
        $admin_can_publish = $this->getSession()->getAdmin()->canPublishThemself();

        if($errors = $this->getApplication()->isAvailableForPublishing($admin_can_publish) AND !$this->getApplication()->isFreeTrialExpired()) {
            array_unshift($errors, __('In order to publish your application, we need:'));
            $message = join('<br />- ', $errors);
            $this->getSession()->addError($message);
            $this->redirect("application/customization_publication_infos");
            return $this;
        } else if(($this->getApplication()->getSubscription()->isActive() AND !$this->getApplication()->subscriptionIsDeleted()) OR ($this->getApplication()->subscriptionIsOffline() AND (!$this->getApplication()->subscriptionIsDeleted()) AND !$this->getApplication()->getSubscription()->isActive())) {
            $this->getSession()->addWarning($this->_("You already have a subscription for this application"));
            $this->redirect("application/customization_publication_infos");
            return $this;
        }

        if($order_id = $this->getSession()->order_id) {
            $order = new Sales_Model_Order();
            $order->find($order_id);
            if($order->getId()) {
                $order->cancel();
                $this->getSession()->order_id = null;
            }
        }

        $this->loadPartials();

    }

    public function saveaddressAction() {

        $html = array();

        if($data = $this->getRequest()->getPost()) {

            try {

                $admin = $this->getSession()->getAdmin();

                if(empty($data["company"])) {
                    throw new Exception($this->_("Please, enter a company"));
                }
                if(empty($data["address"])) {
                    throw new Exception($this->_("Please, enter an address"));
                }
                if(empty($data["country_code"])) {
                    throw new Exception($this->_("Please, enter a country"));
                }
                if(!empty($data["vat_number"])) {

                    if(!$this->__checkVatNumber($data["vat_number"], $data["country_code"])) {
                        throw new Exception($this->_("Your VAT Number is not valid"));
                    }
                }
                $regions = Siberian_Locale::getRegions();
                if(!empty($data["region_code"])) {
                    if($regions[$data["country_code"]]) {
                        $region = $regions[$data["country_code"]][$data["region_code"]];
                        $region_code = $data["region_code"];
                    }else{
                        $region = null;
                        $region_code = null;
                    }
                }else{
                    $region = null;
                    $region_code = null;
                }
                $admin->setCompany($data["company"])
                    ->setFirstname($data["firstname"])
                    ->setLastname($data["lastname"])
                    ->setAddress($data["address"])
                    ->setData("address2", $data["address2"])
                    ->setZipCode($data["zip_code"])
                    ->setCity($data["city"])
                    ->setRegionCode($region_code)
                    ->setRegion($region)
                    ->setCountryCode($data["country_code"])
                    ->setVatNumber($data["vat_number"])
                    ->setPhone($data["phone"])
                    ->save()
                ;

                $html = array("success" => 1);

                if($this->getSession()->subscription_id) {
                    $subscription = new Subscription_Model_Subscription();
                    $subscription->find($this->getSession()->subscription_id);

                    $order_details = $this->_getOrderDetailsHtml($subscription);

                    $html["order_details_html"] = $order_details;

                }

            } catch (Exception $e) {
                $html = array(
                    'error' => 1,
                    'message' => $e->getMessage()
                );
            }

            $this->_sendHtml($html);
        }
    }

    public function setAction() {

        if($data = $this->getRequest()->getPost()) {

            try {

                if(empty($data["subscription_id"])) {
                    throw new Exception($this->_("An error occurred while saving. Please try again later."));
                }

                $subscription = new Subscription_Model_Subscription();
                $subscription->find($data["subscription_id"]);

                if(!$subscription->getId()) {
                    throw new Exception($this->_("An error occurred while saving. Please try again later."));
                }

                $order_details = $this->_getOrderDetailsHtml($subscription);

                $this->getSession()->subscription_id = $subscription->getId();

                $payment_methods = Payment_Model_Payment::getAvailableMethods();

                $show_no_payment_method = (empty($payment_methods) AND !$subscription->getAllowsOffline()) ? true : false;

                $html = array(
                    "success" => 1,
                    "order_details_html" => $order_details,
                    "has_to_be_paid" => $this->_subscriptionToOrder($subscription)->hasToBePaid(),
                    "allows_offline" => !!$subscription->getAllowsOffline(),
                    "show_no_payment_method" => $show_no_payment_method
                );

            } catch(Exception $e) {
                $html = array(
                    "error" => 1,
                    "message" => $e->getMessage()
                );
            }

            $this->_sendHtml($html);

        }

    }

    public function processAction() {

        if($subscription_id = $this->getSession()->subscription_id) {

            try {

                $subscription = new Subscription_Model_Subscription();
                $subscription->find($subscription_id);

                if (!$subscription->getId()) {
                    throw new Exception($this->_("An error occurred while saving. The selected subscription is not valid."));
                }

                $admin = $this->getAdmin();
                $subscription->setCurrentApplication($this->getApplication())
                    ->setAdmin($admin)
                ;

                $order = $subscription->toOrder();
                $order->setStatus(Sales_Model_Order::DEFAULT_STATUS)
                    ->setAppId($this->getApplication()->getId())
                    ->setAppName($this->getApplication()->getName())
                    ->setSubscriptionId($subscription->getId())
                    ->setAdminId($admin->getId())
                    ->setAdminCompany($admin->getCompany())
                    ->setAdminName($admin->getFirstname() . " " . $admin->getLastname())
                    ->setAdminEmail($admin->getEmail())
                    ->setAdminAddress($admin->getAddress())
                    ->setAdminAddress2($admin->getAddress2())
                    ->setAdminCity($admin->getCity())
                    ->setAdminZipCode($admin->getZipCode())
                    ->setAdminRegion($admin->getRegion())
                    ->setAdminCountry($admin->getCountry())
                    ->setAdminPhone($admin->getPhone())
                    ->setAdminVatNumber($admin->getVatNumber());

                if($order->hasToBePaid()) {
                    $order->setPaymentMethod($this->getRequest()->getPost("payment_method"));
                }

                $order->save();

                $this->getSession()->order_id = $order->getId();

                if($order->hasToBePaid() AND $this->getRequest()->getPost("payment_method") != "offline") {

                    if($this->getRequest()->getPost("payment_method")) {
                        $payment = new Payment_Model_Payment();

                        if($this->getRequest()->getPost("stripe_token")) {
                            $order->setToken($this->getRequest()->getPost("stripe_token"));
                        }

                        $params = array(
                            'payment_method' => $this->getRequest()->getPost("payment_method"),
                            'order' => $order
                        );

                        $html = $payment->getPaymentData($params);
                    } else {
                        throw new Siberian_Exception($this->_("Please choose a payment method for this plan."));
                    }

                } else {
                    if($this->getRequest()->getPost("payment_method") == "offline") {
                        $html = array("url" => $this->getUrl("subscription/application/success", array("order_id" => $order->getId(), "payment_method" => "offline")));
                    } else {
                        $html = array("url" => $this->getUrl("subscription/application/success", array("order_id" => $order->getId())));
                    }
                }

            } catch(Exception $e) {
                $html = array(
                    "error" => 1,
                    "message" => $e->getMessage()
                );
            }

            $this->_sendHtml($html);

        }

    }

    public function successAction() {

        $order_id = $this->getRequest()->getParam("order_id") ? $this->getRequest()->getParam("order_id") : $this->getRequest()->getParam("merchant_order_id", null);

        if($order_id) {

            $invoice = new Sales_Model_Invoice();
            $invoice->find(array("order_id" => $order_id));

            if($invoice->getId()) {
                throw new Siberian_Exception(__("Error, order already paid."));
            }

            $order = new Sales_Model_Order();
            $order->find($order_id);

            $subscription = new Subscription_Model_Subscription();
            $subscription = $subscription->find($order->getSubscriptionId());

            $payment = new Payment_Model_Payment();

            $success = array();

            if($this->getRequest()->getParam("payment_method") != "offline") {
                if($order->hasToBePaid()) {
                    $params = array(
                        'payment_method' => $this->getRequest()->getParam("payment_method"),
                        'token' => $this->getRequest()->getParam('token'),
                        'key' => $this->getRequest()->getParam('key'),
                        'order_number' => $this->getRequest()->getParam('order_number'),
                        'stripe_subscription_id' => $this->getRequest()->getParam('stripe_subscription_id',null),
                        'customer_id' => $this->getRequest()->getParam('customer_id',null)
                    );

                    if(!$params["payment_method"]) {

                        $this->getSession()->addWarning($this->_("An error occurred while processing the payment. For more information, please feel free to contact us."));
                        $this->redirect("subscription/application/create");

                    } else {
                        $success = $payment->setData($params)->setOrder($order)->success();
                    }
                }

                $order->pay();

                $subscription = new Subscription_Model_Subscription_Application();
                $subscription->create($order->getAppId(), $order->getSubscriptionId())
                    ->setPaymentData(isset($success["payment_data"]) ? $success["payment_data"] : null)
                    ->setPaymentMethod($payment->getCode())
                    ->setIsSubscriptionDeleted(0)
                    ->save();
            } else {
                $subscription = new Subscription_Model_Subscription_Application();
                $subscription->create($order->getAppId(), $order->getSubscriptionId())
                    ->setPaymentMethod("offline")
                    ->setIsActive(0)
                    ->setIsSubscriptionDeleted(0)
                    ->save();
            }

            $this->getSession()->subscription_id = null;
            $this->getSession()->order_id = null;

            if ($this->getApplication()->getIsLocked() AND $this->getRequest()->getParam("payment_method") != "offline") {
                $this->getApplication()->setIsLocked(false)->save();
            }

            //Here, we are sure subscription is set
            //We can send a mail to owner to warn him he got a new customer
            $mail_to = System_Model_Config::getValueFor("support_email") ? System_Model_Config::getValueFor("support_email") : null;

            if(!$mail_to) {
                $admin = new Admin_Model_Admin();
                $admin = $admin->findOriginalAdmin();

                if($admin->getId()) {
                    $mail_to = $admin->getEmail();
                }
            }
            //We should always have an email, but just in case...
            if($mail_to) {
                $content = "<p>".__("Hello,")."</p>
                            <p>".__("You've made a sale!")."</p>
                            <p>".__("The user %s requests you to publish the app %s.",$order->getAdminName(), $this->getApplication()->getName())."</p>
                            <p><a href='".$this->getUrl("/application/backoffice_view/", array("app_id" => $this->getApplication()->getId()))."' >".__("Click here")."</a> ".__("to view this app if you are connected to your backoffice.")."</p>";

                $mail = new Siberian_Mail();
                $mail->setBodyHtml($content);
                $mail->addTo($mail_to);
                $mail->setSubject(__("New sale on your platform"));
                $mail->send();
            }

            if($this->getRequest()->getParam("payment_method") == "offline") {
                $this->redirect("subscription/application/offlinewait");
            } else {
                $this->loadPartials();
            }

        }
    }

    public function cancelAction() {

        $payment = new Payment_Model_Payment();
        $params = array(
            'payment_method' => $this->getRequest()->getParam("payment_method")
        );
        $cancel = $payment->setData($params)->cancel();
        $this->redirect("subscription/application/create");

    }

    public function getsubscriptioninfoAction() {

        $code = $this->getParam('code',null);
        $payment_method = $this->getParam('payment_method',null);

        switch($payment_method) {
            case "stripe":
                $payment = new Payment_Model_Stripe();
                break;
            case "paypal":
                $payment = new Payment_Model_Paypal();
                break;
            case "2checkout":
                $payment = new Payment_Model_2Checkout();
                break;
	        case "arsenalpays":
		        $payment = new Payment_Model_Arsenalpay();
		        break;
            default:
                $sub_info = false;
        }

        $sub_info = $payment->getSubscriptionInfo($code);
        if(!$sub_info) {
            $html = array(
                "message" => "An error occurred",
                "status" => "nok",
                "error" => true
            );
        } else {
            $html = array(
                "status" => ($sub_info->status === "active") ? "ok" : "nok"
            );
        }

        $this->_sendHtml($html);

    }

    public function offlinewaitAction() {
        $this->loadPartials();
        $message = System_Model_Config::getValueFor("offline_plan_message") ? System_Model_Config::getValueFor("offline_plan_message") : __("You have chosen an offline plan. Once submitted, your subscription will be activated by an admin when your payment is received.");
        $this->getLayout()->getPartial("content")->setMessage($message);
    }

    protected function _getOrderDetailsHtml($subscription) {

        $order = $this->_subscriptionToOrder($subscription);
        return $this->getLayout()->addPartial("order_details", "admin_view_default", "subscription/application/create/order_details.phtml")
            ->setTmpOrder($order)
            ->toHtml()
        ;

    }

    private function __checkVatNumber($vatNumber, $countryCode) {

        // Serialize the VAT Number
        $vatNumber = str_replace(array(' ', '.', '-', ',', ', '), '', $vatNumber);
        // Retrieve the country code
        $countryCodeInVat = substr($vatNumber, 0, 2);
        // Retrieve the VAT Number
        $vatNumber = substr($vatNumber, 2);

        // Check the VAT Number syntax
        if (strlen($countryCode) != 2 || is_numeric(substr($countryCode, 0, 1)) || is_numeric(substr($countryCode, 1, 2))) {
            return false;
        }

        // Check if the country code in the VAT Number is the same than the parameter
        if ($countryCodeInVat != $countryCode) {
            return false;
        }

        // Call the webservice
        if(System_Model_Config::getValueFor("vat_check_activated") === "1") {
            $client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
            $params = array('countryCode' => $countryCode, 'vatNumber' => $vatNumber);
            $result = $client->checkVat($params);
            return $result->valid;
        } else {
            return true;
        }
    }

    protected function _subscriptionToOrder($subscription) {

        if(!$this->_order) {
            $subscription->setCurrentApplication($this->getApplication())
                ->setAdmin($this->getSession()->getAdmin());

            $this->_order = $subscription->toOrder();
        }

        return $this->_order;
    }
}
