
App.factory('ArsenalpayFactory', function($sbhttp, Url) {

    var factory = {};
    factory.value_id = null;
    factory.getWidgetData = function() {
        if(!this.value_id) return false;
        
        return $sbhttp({
            method: 'POST',
            url: Url.get("arsenalpay/mobile_widget/getoptions"),
            cache: false,
            responseType:'json'
        });
    };
    return factory;
});
