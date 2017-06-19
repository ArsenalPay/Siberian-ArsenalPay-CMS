<?php
class Arsenalpay_Bootstrap {

   public static function init($bootstrap) {

        //Перезаписать дизайн
        Siberian_Cache_Design::overrideCoreDesign("Arsenalpay");
        ///** Get the base path to your module */
        $base = Core_Model_Directory::getBasePathTo("/app/local/modules/Arsenalpay/");
        require_once "{$base}/Mcommerce/controllers/Mobile/Sales/PaymentController.php";
        require_once "{$base}/Mcommerce/Model/Db/Table/Payment/Method/Arsenalpay.php";
        require_once "{$base}/Mcommerce/Model/Payment/Method/Arsenalpay.php";

        
        # Register assets
        Siberian_Assets::registerAssets("Arsenalpay", "/app/local/modules/Arsenalpay/resources/var/apps/");
        Siberian_Assets::addJavascripts(array(
            "modules/arsenalpay/controllers/arsenalpay.js",
            "modules/arsenalpay/factories/arsenalpay.js",
        ));

        Siberian_Assets::addStylesheets(array(
            "modules/arsenalpay/css/styles.css",
        ));


    }

}
