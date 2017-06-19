App.config(function($stateProvider, HomepageLayoutProvider) {

    $stateProvider.state('arsenalpay', {
        url: BASE_PATH+"/arsenalpay/mobile_widget/index/value_id/:value_id",
        controller: 'ArsenalpayWidgetController',
        templateUrl: "modules/arsenalpay/templates/l1/widget.html"
    });

}).controller('ArsenalpayWidgetController', function($ionicLoading, $scope, $state, $stateParams, $timeout, $translate, ArsenalpayFactory) {
    this.value_id = 20;
    $scope.is_loading = true;
    $scope.page_title = 'Arsenalpay';
    $scope.value_id = ArsenalpayFactory.value_id = $stateParams.value_id;
    $scope.loadContent = function () {
        $scope.is_loading = false;
        $scope.renderWidget();
    };
    $scope.renderWidget = function () {
        ArsenalpayFactory.findtotal().success(function ($data) {
            $ionicLoading.hide();
            var APWidget = new ArsenalpayWidget({
                element: 'app-widget',
                destination: $data.destination,
                widget: $data.widget_id,
                amount: $data.total,
                userId: $data.userId,
                nonce: $data.nonce,
                widgetSign: $data.widgetSign,
            });
            APWidget.render();

        });
        return true;
    };
    $ionicLoading.show({
        template: "<ion-spinner class=\"spinner-custom\"></ion-spinner>"
    });

    if(typeof ArsenalpayWidget == "undefined") {
        var APwidgetSrc = document.createElement('script');
        APwidgetSrc.type = "text/javascript";
        APwidgetSrc.src = "https://arsenalpay.ru/widget/script.js";
        APwidgetSrc.onload = function() {
             
            $scope.loadContent();
        };
        document.body.appendChild(APwidgetSrc);
    } else {
        $scope.loadContent();
    }
    function goHome(){
        $state.go('home');
    }
    
    $scope.right_button = {
        action: goHome,
        label: $translate.instant("Home")
    };


});
