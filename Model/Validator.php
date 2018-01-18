<?php

class Arsenalpay_Model_Validator extends Core_Model_Default {

        /**
     * @var array  Ответ платёжной системы
     */
    protected $callback_response = array(
        'ID', /* Идентификатор ТСП/ merchant identifier */
        'FUNCTION', /* Тип запроса/ type of request to which the response is received */
        'RRN' , /* Идентификатор транзакции/ transaction identifier */
        'PAYER', /* Идентификатор плательщика/ payer(customer) identifier */
        'AMOUNT', /* Сумма платежа/ payment amount */
        'ACCOUNT' , /* Номер получателя платежа (номер заказа, номер ЛС) на стороне ТСП/ order number */
        'STATUS', /* Статус платежа - check - запрос на проверку номера получателя : payment - запрос на передачу статуса платежа
          /* Payment status. When 'check' - response for the order number checking, when 'payment' - response for status change. */
        'DATETIME', /* Дата и время в формате ISO-8601 (YYYY-MM-DDThh:mm:ss±hh:mm), УРЛ-кодированное */
        /* Date and time in ISO-8601 format, urlencoded. */
        'SIGN', /* Подпись запроса/ response sign.
              /* = md5(md5(ID).md(FUNCTION).md5(RRN).md5(PAYER).md5(AMOUNT).md5(ACCOUNT).md(STATUS).md5(PASSWORD)) */
    );

    public function __construct($params = array()) {
        parent::__construct($params);
        $this->logger = Zend_Registry::get("logger");
        return $this;
    }
    

    
    public function validate($request_array = null) {
        $error = false;
        
        $this->post_log("Arsenalpay params:");
        foreach ($this->callback_response as $key) {
            if( !array_key_exists( $key, $request_array ) || empty( $request_array[$key] )){
                $error = $this->add_error("Missing ".$key. " in response");
            }else{
                $this->post_log("{$key}={$request_array[$key]}");
            }
        }
        if($error){
            return !$error;
        }
        $cart = new Mcommerce_Model_Cart();
        $cart = $cart->find($request_array['ACCOUNT']);
        if( !$cart->getCartId() ){
            $cart = null;
        }
        
        if(is_null($cart)) {
            $error = $this->add_error("Cart is not founded in db. ACCOUNT=".$request_array['ACCOUNT']);
        }else {
            $total = round($cart->getTotal(), 2);
            $lessAmount = false;
            if( ! $this->check_sign($request_array, $cart) ) {
                $error = $this->add_error("Invalid signature");
            }else if($request_array['MERCH_TYPE'] == 0 &&  $total ==  $request_array['AMOUNT']){
                $lessAmount = false;
            }else if($request_array['MERCH_TYPE'] == 1 && $total >= $request_array['AMOUNT'] && 
                $total ==  $request_array['AMOUNT_FULL']) {
                $lessAmount = true;
            }else {
                $error = $this->add_error("Wrong callback sum");
            }
            
            if($lessAmount){
                $this->post_log("Callback response with less amount {$request_array['AMOUNT']}");
            }
            
        }
        return !$error;
    }
 
    private function check_sign($request_array, $cart){
        $secret_key = $cart->getPaymentMethod()->getInstance()->getSecret();
        $validSign = ( $request_array['SIGN'] === md5(md5($request_array['ID']). 
                md5($request_array['FUNCTION']).md5($request_array['RRN']). 
                md5($request_array['PAYER']).md5($request_array['AMOUNT']).md5($request_array['ACCOUNT']). 
                md5($request_array['STATUS']).md5($secret_key) ) )? true : false;
        return $validSign;
    }

    private function add_error($str) {
        $this->logger->log($str, Zend_Log::ERR);
        return $str;
    }

    private function post_log($str) {
        $this->logger->log($str, Zend_Log::DEBUG);
        return $str;
    }    


}