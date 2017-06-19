<?php

class Arsenalpay_CallbackController extends Mcommerce_Mobile_Sales_PaymentController {
    public function init(){
        $this->_current_option_value = new Application_Model_Option_Value();
        $this->_current_option_value->setIsHomepage(true);
        
        ob_clean();
        return $this;
    }
    
    public function paymentAction() {
        $post_data = $this->getRequest()->getPost();
        $validator = new Arsenalpay_Model_Validator();
        $isValid = $validator->validate($post_data);
        $reqest_status = $post_data['STATUS'];
        $remote_address = $_SERVER["REMOTE_ADDR"];
        $log_str = $remote_address . " : answer = ";
        $answer = 'ERR';
        ob_start();
        if($isValid){
            if($reqest_status == 'check'){
                $answer = 'YES';
            }
            else if($reqest_status == 'payment'){
                $answer = 'OK';
                $cart = new Mcommerce_Model_Cart();
                $cart->find($post_data['ACCOUNT']);
                $this->_cart = $cart;
                
                $value_id = $cart->getMcommerce()->getValueId();
                $this->getCurrentOptionValue()->find($value_id);
                
                $this->validatepaymentAction();
            }
        }else{
            if($reqest_status == 'check'){
                $answer = 'NO';
            }else {
                $answer = 'ERR';
            }
        }
        $log_str .= $answer;
        $this->post_log($log_str);
        ob_end_clean();
        echo $answer;

        die();
    }

    protected function _sendHtml($html) {
        return ;
    }
    protected function _redirect($url, array $options = array()) {
        return;
    }


    private function post_log($str) {
        $logger = Zend_Registry::get("logger");
        $logger->log($str, Zend_Log::DEBUG);
        return $str;
    }   
}