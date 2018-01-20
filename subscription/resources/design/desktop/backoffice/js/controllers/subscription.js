App.config(function($routeProvider) {

    $routeProvider.when(BASE_URL+"/subscription/backoffice_application_list", {
        controller: 'SubscriptionListController',
        templateUrl: BASE_URL+"/subscription/backoffice_application_list/template"
    }).when(BASE_URL+"/subscription/backoffice_application_edit", {
        controller: 'SubscriptionnEditController',
        templateUrl: BASE_URL+"/subscription/backoffice_application_edit/template"
    }).when(BASE_URL+"/subscription/backoffice_application_edit/subscription_app_id/:subscription_app_id", {
        controller: 'SubscriptionEditController',
        templateUrl: BASE_URL+"/subscription/backoffice_application_edit/template"
    });

}).controller("SubscriptionListController", function($scope, $location, Header, Subscription, SectionButton, $queue) {

    $scope.header = new Header();
    $scope.header.button.left.is_visible = false;
    $scope.content_loader_is_visible = true;
    $scope.filter_active = false;

    $scope.button = new SectionButton(function() {
        $location.path("subscription/backoffice_application_edit");
    });

    Subscription.loadListData().success(function(data) {
        $scope.header.title = data.title;
        $scope.header.icon = data.icon;
        $scope.label_activate = data.label_activate;
        $scope.label_deactivate = data.label_deactivate;
        $scope.message_confirm_delete = data.message_confirm_delete;
    });

    Subscription.findAll().success(function(subscriptions) {
        $scope.subscriptions = subscriptions;
        for (var i = 0; i < $scope.subscriptions.length; i++) {
            var sub = $scope.subscriptions[i];
            sub.payment_api_loading = false;
            sub.payment_td_status = "";
            switch(sub.is_active) {
                case "No":
                    sub.tr_status = "active";
                    break;
                case "Yes":
                    sub.tr_status = "";
                    break;
                default:
                    sub.tr_status = "danger";
            }
            switch(sub.payment_method) {
                case "stripe":
                    sub.paymentUrl = "https://dashboard.stripe.com/test/subscriptions/"+sub.payment_code;
                    sub.image_url = "/images/backoffice/" + sub.payment_method + ".png";
                    break;
                case "paypal":
                    sub.paymentUrl = "https://www.paypal.com/fr/cgi-bin/webscr?cmd=_profile-recurring-payments&encrypted_profile_id="+sub.payment_code;
                    sub.image_url = "/images/backoffice/" + sub.payment_method + ".png";
                    break;
                case "2checkout":
                    sub.paymentUrl = null;
                    sub.image_url = "/images/backoffice/" + sub.payment_method + ".png";
                    break;
                case "arsenalpays":
                    sub.paymentUrl = null;
                    sub.image_url = "/app/local/modules/ArsenalpayS/resources/design/desktop/backoffice/images/payment/arsenalpays.png";
                    break;
                default:
                    sub.paymentUrl = null;
                    sub.image_url = null;
            }
        }

        var maxParallelsProcesses = 2;
        var currentParallelsProcesses = 0;
        var currentArrayEntryKey = 0;
        var queue = $queue.queue(function(sub){
            var key = currentArrayEntryKey;
            currentArrayEntryKey++;

            if(sub.is_active !== "Yes") {
                return;
            }

            if(sub.payment_method == "2checkout") {
                return;
            }

            //Free plan
            if(sub.payment_method == "") {
                return;
            }

            if(sub.payment_method == "offline") {
                return;
            }

            currentParallelsProcesses++;
            if(currentParallelsProcesses == maxParallelsProcesses) {
                queue.pause();
            }

            $scope.subscriptions[key].payment_api_loading = true;

            Subscription.getsubscriptioninfo(sub)
                .success(function(sub_api_info){
                    if(!sub_api_info || !sub_api_info.status) {
                        $scope.subscriptions[key].payment_td_status = "danger";
                    } else {
                        switch(sub_api_info.status) {
                            case "ok":
                                $scope.subscriptions[key].payment_td_status = "success";
                                break;
                            default:
                                $scope.subscriptions[key].payment_td_status = "danger";
                        }
                    }
                })
                .error(function(sub_api_info){
                    $scope.subscriptions[key].payment_td_status = "danger";
                })
                .finally(function(){
                    currentParallelsProcesses--;
                    if(currentParallelsProcesses < maxParallelsProcesses) {
                        queue.start();
                    }
                    $scope.subscriptions[key].payment_api_loading = false;
                })
        });
        queue.addEach(subscriptions);
    }).finally(function() {
        $scope.content_loader_is_visible = false;
    });

    $scope.deleteSubscription = function(id, index) {
        if(confirm($scope.message_confirm_delete)) {
            $scope.content_loader_is_visible = true;
            Subscription.delete(id).success(function(data) {
                $scope.message.setText(data.message)
                    .isError(false)
                    .show()
                ;
                $scope.subscriptions.splice(index, 1);
            }).finally(function() {
                $scope.content_loader_is_visible = false;
            });
        }
    };

    $scope.changeSubscriptionStatus = function(id, index) {
        $scope.content_loader_is_visible = true;
        var new_status = $scope.subscriptions[index].is_active == "No" ? 1 : 0;
        Subscription.changeStatus(id, new_status).success(function(data) {
            $scope.message.setText(data.message)
                .isError(false)
                .show()
            ;
            $scope.subscriptions[index].is_active = new_status == 1 ? "Yes" : "No";
            $scope.subscriptions[index].tr_status = new_status == 1 ? "" : "active";
        }).finally(function() {
            $scope.content_loader_is_visible = false;
        });
    };

}).controller("SubscriptionEditController", function($scope, $location, $routeParams, Header, Label, Subscription) {

    $scope.header = new Header();
    $scope.header.button.left.is_visible = false;
    $scope.header.button.left.action = function() {
        $location.path(Url.get("admin/backoffice_list"));
    };
    $scope.content_loader_is_visible = true;

    $scope.datepicker_visible = false;

    Subscription.loadEditData().success(function(data) {
        $scope.header.title = data.title;
        $scope.header.icon = data.icon;
        $scope.none_label = data.none_label;
    });

    Subscription.find($routeParams.subscription_app_id).success(function(data) {
        $scope.subscription = data.subscription ? data.subscription : {};

        $scope.is_active_checkbox_disabled = ($scope.subscription.payment_method == '2checkout' || $scope.subscription.payment_method == 'stripe') && $scope.subscription.is_active == '0';
        $scope.section_title = data.section_title;

        if($scope.subscription.payment_method == "offline") {
            $scope.datepicker_payment_visible = false;
            $scope.datepicker_creation_visible = false;
            $scope.invoice = {
                "payment_date": data.today_date,
                "creation_date": data.today_date,
                "subscription_id": $scope.subscription.subscription_id,
                "app_id": $scope.subscription.app_id
            };
            $scope.section_invoice_title = data.section_invoice_title;
            $scope.form_invoice_loader_is_visible = false;
        }


    }).finally(function() {
        $scope.content_loader_is_visible = false;
    });

    $scope.saveSubscription = function() {

        $scope.form_loader_is_visible = true;

        Subscription.save($scope.subscription).success(function(data) {
            $location.path("subscription/backoffice_application_list");
            $scope.message.setText(data.message)
                .isError(false)
                .show()
            ;
        }).error(function(data) {
            var message = Label.save.error;
            if(angular.isObject(data) && angular.isDefined(data.message)) {
                message = data.message;
            }

            $scope.message.setText(message)
                .isError(true)
                .show()
            ;
        }).finally(function() {
            $scope.form_loader_is_visible = false;
        });
    };

    $scope.onSetTime = function (newDate, oldDate) {}

    $scope.editInvoice = function() {
        $scope.form_invoice_loader_is_visible = true;
        Subscription.editInvoice($scope.invoice).success(function(data) {
            $scope.message.setText(data.message)
                .isError(false)
                .show()
            ;
        }).error(function(data) {
            $scope.message.setText(data.message)
                .isError(true)
                .show()
            ;
        }).finally(function(data) {
            $scope.form_invoice_loader_is_visible = false;
        });
    }

});
