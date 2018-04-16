App.factory('ArsenalpayFactory', function($http, Url, $session, $window) {

    var factory = {};
    factory.value_id = null;

    factory.getWidgetData = function() {
        if(!this.value_id) return false;

        var urlParams = {
            'customer_uuid': $session.getDeviceUid()
        };
        var postData = {
            'notes': $window.sessionStorage.getItem('mcommerce-notes')
        };

        $window.sessionStorage.removeItem('mcommerce-notes');

        return $http({
            method: 'POST',
            url: Url.get("arsenalpay/mobile_widget/getoptions", urlParams),
            data: postData,
            cache: false,
            responseType:'json'
        });
    };
    factory.findtotal = factory.getWidgetData;

    return factory;
});
