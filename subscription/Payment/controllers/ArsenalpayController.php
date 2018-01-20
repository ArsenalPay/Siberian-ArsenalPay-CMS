<?php

class Payment_ArsenalpayController extends Core_Controller_Default {
	public function init() {
		$params = $this->getAllParams();
		if (key_exists('action', $params) && $params['action'] == 'callback') {
			return;
		}
		parent::init();
	}

	public function successAction() {
		$params = $this->getAllParams();
		if (!key_exists('destination', $params)) {
			throw new Exception('Have not oreder number in request');
		}
		$arsenalpay_payment = new Payment_Model_Arsenalpay();
		$redirect_url       = parent::getUrl(
			'subscription/application/success',
			array('order_id' => $params['destination'], 'payment_method' => $arsenalpay_payment->getCode())
		);
		$this->redirect($redirect_url);
	}

	public function callbackAction() {
		$callback_params   = $this->getRequest()->getPost();
		$arsenalpays_model = new Payment_Model_Arsenalpay();
		$arsenalpays_model->processCallback($callback_params);
		die();
	}

	public function checkrecurrencesAction() {
		if (!Cron_Model_Cron::isRunning()) {
			self::checkRecurrencies();
		}
		else {
			die('The core CRON Scheduler is handling this request, exiting.');
		}
		die('Done.');
	}

	public static function checkRecurrencies() {
		$subscription_model = new Subscription_Model_Subscription_Application();
		$subscriptions      = $subscription_model->findExpiredSubscriptions('arsenalpay_subscription_id');

		$arsenalpay_model = new Payment_Model_Arsenalpay();
		$token            = $arsenalpay_model->getToken();
		$response         = $arsenalpay_model->getSubscriptions($token);
		if (key_exists('error', $response) && $response['error']) {
			Zend_Registry::get("logger")->log(
				'Error during getting subscriptions:' . $response['error']
				. ' . Arsenalpays/ArsenalpayController::checkRecurrencies failed!'
				, Zend_Log::ERR
			);

			return false;
		}
		$ap_subscriptions = $response['items'];
		foreach ($subscriptions as $subscription) {
			if ($subscription->getPaymentMethod() != $arsenalpay_model->getCode()) {
				continue;
			}
			$is_active            = false;
			$ap_subscription_info = array();
			foreach ($ap_subscriptions as $a_s) {
				if ($a_s['id'] == $subscription->getArsenalpaySubscriptionId()) {
					$is_active            = true;
					$ap_subscription_info = $a_s;
				}
			}

			/**
			 *  endDate: "2022-09-30 00:00:00",
			 *  lastDate: "2017-12-30 02:28:47.741755",
			 *  nextDate: "2018-01-30 02:28:47.741755"
			 *  pattern for Zend_Date'yyyy-MM-dd HH:mm:ss'
			 */
			$date_pattern = 'yyyy-MM-dd HH:mm:ss';
			if ($is_active) {
				$ap_last_payment_date = new Zend_Date(
					$ap_subscription_info['lastDate'],
					$date_pattern
				);
				$ap_next_payment_date = new Zend_Date(
					$ap_subscription_info['nextDate'],
					$date_pattern
				);
				//hours not save in db
				$ap_next_payment_date->addDay(1);
				$expires_at = new Zend_Date($subscription->getExpireAt());

				if ($ap_next_payment_date->compare($expires_at, Zend_Date::DATES) >= 0) {

					$subscription
						->setIsActive(1)
						->update($ap_next_payment_date)
						->invoice($ap_last_payment_date)
						->save();

				}
			}
			else {
				$subscription
					->setIsActive(0)
					->save();
			}
		}
	}
}
