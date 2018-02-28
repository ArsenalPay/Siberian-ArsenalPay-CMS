App.config(function ($stateProvider, HomepageLayoutProvider) {

    $stateProvider.state('arsenalpay', {
        url: BASE_PATH + "/arsenalpay/mobile_widget/index/value_id/:value_id",
        controller: 'ArsenalpayWidgetController',
        templateUrl: "modules/arsenalpay/templates/l1/widget.html"
    });

}).controller('ArsenalpayWidgetController', function ($ionicLoading, $window, $scope, $state, $stateParams, $timeout, $translate, ArsenalpayFactory) {
    this.value_id = $stateParams.value_id;
    $scope.value_id = $stateParams.value_id;
    ArsenalpayFactory.value_id = $stateParams.value_id;
    $scope.is_loading = true;
    $scope.page_title = 'Arsenalpay';


    $ionicLoading.show({
        template: "<ion-spinner class=\"spinner-custom\"></ion-spinner>"
    });

    $scope.showWidget = function () {
        if (typeof ArsenalpayWidget == "undefined") {
            var APwidgetSrc = document.createElement('script');
            APwidgetSrc.type = "text/javascript";
            APwidgetSrc.src = "https://arsenalpay.ru/widget/script.js";
            APwidgetSrc.onload = function () {
                $scope.startWidget();
            };
            document.body.appendChild(APwidgetSrc);
        } else {
            $scope.startWidget();
        }
    };
    $scope.startWidget = function () {
        var APWidget = new ArsenalpayWidget({
            element: 'app-widget',
            destination: $scope.widgetData.destination,
            widget: $scope.widgetData.widget,
            amount: $scope.widgetData.amount,
            userId: $scope.widgetData.userId,
            nonce: $scope.widgetData.nonce,
            widgetSign: $scope.widgetData.widgetSign
        });
        $scope.is_loading = false;
        $ionicLoading.hide();
        APWidget.render();
    };

    function goHome() {
        $state.go('home');
    }

    $scope.right_button = {
        action: goHome,
        label: $translate.instant("Home")
    };

    ArsenalpayFactory.getWidgetData().success(function ($data) {
        $scope.widgetData = $data;
        if ($data.amount == 0) { // если пользователь вернулся из браузера
            $scope.is_loading = false;
            $ionicLoading.hide();
            goHome();
        } else if ($data.openWidgetInBrowser) {
            $window.open($data.url);
            $scope.is_loading = false;
            $ionicLoading.hide();
        } else {
            $scope.showWidget()
        }
    });

});
