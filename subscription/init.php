<?php

$init = function($bootstrap) {

        Siberian_Cache_Design::overrideCoreDesign("ArsenalpayS");
        ///** Get the base path to your module */
        $base = Core_Model_Directory::getBasePathTo("/app/local/modules/ArsenalpayS/");
        //do not remove/comment that
	    require_once "{$base}/Model/Order.php";
	    require_once "{$base}/Model/Db/Table/Order.php";

	    //if you copy Subscription and Payment dir from ArsenalpayS to /app/pe/ - you can comment that
	    require_once "{$base}/Subscription/controllers/ApplicationController.php"; //getSubscriptionInfo
	    require_once "{$base}/Subscription/controllers/Backoffice/Application/EditController.php"; //manageRecurring
	    require_once "{$base}/Subscription/Model/Db/Table/Subscription/Application.php"; //show arsenalpay subscriptions in subscription/backoffice_application_list
        require_once "{$base}/Payment/Model/Payment.php"; //add arsenalpay`s code in module
	    require_once "{$base}/Payment/Model/Arsenalpay.php";
	    require_once "{$base}/Payment/controllers/ArsenalpayController.php";
	    //resources/design/desktop/backoffice/js/controllers/subscription.js  - image in subscription/backoffice_application_list

    };

