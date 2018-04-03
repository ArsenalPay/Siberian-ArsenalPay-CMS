App.factory('ArsenalpayFactory', function($http, Url, $session) {

    var factory = {};
    factory.value_id = null;
    factory.getWidgetData = function() {
        if(!this.value_id) return false;

        var data = {
            'customer_uuid': $session.getDeviceUid()
        };

        return $http({
            method: 'POST',
            url: Url.get("arsenalpay/mobile_widget/getoptions", data),
            cache: false,
            responseType:'json'
        });
    };
    factory.findtotal = factory.getWidgetData;

    return factory;
});
