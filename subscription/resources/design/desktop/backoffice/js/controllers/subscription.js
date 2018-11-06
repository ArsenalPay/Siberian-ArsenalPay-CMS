App.config(function($routeProvider) {

    $routeProvider.when(BASE_URL+"/subscription/backoffice_application_list", {
        cache: false,
        controller: 'SubscriptionListController',
        templateUrl: BASE_URL+"/subscription/backoffice_application_list/template"
    }).when(BASE_URL+"/subscription/backoffice_application_edit", {
        controller: 'SubscriptionnEditController',
        templateUrl: BASE_URL+"/subscription/backoffice_application_edit/template"
    }).when(BASE_URL+"/subscription/backoffice_application_edit/subscription_app_id/:subscription_app_id", {
        controller: 'SubscriptionEditController',
        templateUrl: BASE_URL+"/subscription/backoffice_application_edit/template"
    });

}).controller("SubscriptionListController", function($scope, $location, $timeout, $queue, Header, Subscription, SectionButton, Url) {

    document.querySelector('.wrapper.inner_content').style.width = "calc(100% - 80px)";

    $scope.header = new Header();
    $scope.header.button.left.is_visible = false;
    $scope.content_loader_is_visible = false;

    $scope.subscriptions = [];
    $scope.perPage = 10;
    $scope.page = 0;
    $scope.clientLimit = 250;
    $scope.stripeSubscriptions = [];
    $scope.stripe_search_filter = '';

    $scope.urlParams = {
        filter: '',
        order: 'sa.subscription_app_id',
        by: false,
        isCancelled: false,
        isActive: false,
        isOffline: false
    };

    $scope.lastUrl = false;
    $scope.lastParams = false;

    $scope.downloadCsv = function () {
        let params = angular.copy($scope.lastParams);
        params.csv = 1;
        let url = $scope.lastUrl + '?' + Url.buildQuery(params);
        window.open(url, '_blank');
    };

    $scope.$on('pagination:loadPage', function (event, status, config) {
        document.querySelector('.wrapper.inner_content').style.width = "calc(100% - 80px)";
        $scope.lastUrl = config.url;
        $scope.lastParams = config.params;
    });

    $scope.showEdit = function (subscription) {
        return (subscription.payment_method === 'offline' && !subscription.is_subscription_deleted);
    };

    $scope.showCancel = function (subscription) {
        return !subscription.is_subscription_deleted;
    };

    $scope.showToggle = function (subscription) {
        return !subscription.is_subscription_deleted && subscription.payment_method === 'offline';
    };

    Subscription
        .loadListData()
        .success(function(data) {
            $scope.header.title = data.title;
            $scope.header.icon = data.icon;
            $scope.label_activate = data.label_activate;
            $scope.label_deactivate = data.label_deactivate;
            $scope.message_confirm_delete = data.message_confirm_delete;

            $scope.words = data.words;
        });

    $scope.parseCollection = function (collection) {
        $scope.collection = collection;

        $scope.trStyles();

        return collection;
    };

    $scope.trStyles = function () {
        for (let i = 0; i < $scope.collection.length; i++) {
            let sub = $scope.collection[i];
            sub.payment_api_loading = false;
            sub.payment_td_status = '';

            if (sub.is_active && sub.is_subscription_deleted) {
                sub.trClass = 'warning';
            }
            if (!sub.is_active && sub.is_subscription_deleted) {
                sub.trClass = 'danger';
            }
            if (sub.is_active && !sub.is_subscription_deleted) {
                sub.trClass = 'success';
            }

            // It's a child, check parent status
            if (sub.parent_id !== null &&
                sub.parent_is_subscription_deleted &&
                sub.parent_is_active) {
                sub.trClass = 'warning';
            }
        }
    };

    $scope.testPayments = function () {
        $scope.checkPayments();
    };

    $scope.checkPayments = function () {
        // Queue getStatus
        let queue = $queue.queue(function (subscription) {
            let method = subscription.payment_method;
            if (method === 'offline' ||
                method === 'child' ||
                method === '' ||
                subscription.is_subscription_deleted === true) {
                return;
            }

            this.pause();

            subscription.payment_api_loading = true;

            Subscription
                .getStatus(subscription.id)
                .success(function (data) {
                    if (data.isActive === true) {
                        subscription.payment_td_status = 'success';
                        subscription.status_ok = true;
                        subscription.status_error = false;
                        subscription.status_message = '';
                    } else {
                        subscription.payment_td_status = 'danger';
                        subscription.status_error = true;
                        subscription.status_ok = false;
                        subscription.status_message = data.message;
                    }
                })
                .error(function (data) {
                    $scope.message
                        .setText(data.message)
                        .isError(true)
                        .show();
                    subscription.payment_td_status = 'danger';
                    subscription.status_error = true;
                    subscription.status_ok = false;
                    subscription.status_message = data.message;
                })
                .finally(function () {
                    subscription.payment_api_loading = false;
                    queue.start();
                });
        });
        queue.addEach($scope.collection);
    };

    /**
     * We load lastest strip subs, to display a list!
     * @param subscription
     */
    $scope.fixStripe = function (subscription) {
        $scope.sub_id = null;
        $scope.stripeEditModal = true;
        $scope.currentSub = subscription;
    };

    $scope.showStripeFix = function (subscription) {
        return (subscription.status_error && subscription.payment_method === 'stripe') ||
            (subscription.payment_method === 'stripe' && subscription.payment_code.indexOf('sub_') !== 0 && subscription.payment_code.indexOf('ch_'));
    };

    $scope.searchStripe = function () {
        $scope.content_loader_is_visible = true;

        Subscription
            .getStripeSubscriptions($scope.stripe_search_filter)
            .success(function (data) {
                $scope.stripeSubscriptions = data.subscriptions;
                $scope.stripeEditModal = true;
            }).finally(function () {
                $scope.content_loader_is_visible = false;
            });
    };

    $scope.setStripeSub = function (stripeSubscription) {
        $scope.content_loader_is_visible = true;

        Subscription
            .fixStripeSubscriptions(
                $scope.currentSub.subscription_app_id,
                stripeSubscription.subscription.id,
                stripeSubscription.customer.id)
            .success(function (data) {
                $scope.stripeEditModal = false;
                $scope.currentSub = null;
                $scope.stripeSubscriptions = [];
                $scope.stripe_search_filter = '';
            })
            .finally(function () {
                $scope.content_loader_is_visible = false;
                $scope.$broadcast('pagination:reload');
            });
    };


    // PayPal fixes
    $scope.showPaypalSync = function (subscription) {
        let isPaypal = subscription.payment_method === 'paypal';
        let expired = subscription.expire_at_timestamp < Math.floor(Date.now()/1000);
        let isOutOfSync = isPaypal && expired && subscription.is_active;

        if (isOutOfSync) {
            subscription.tdExpireClass = "text-danger bold";
        }

        return isOutOfSync;
    };

    $scope.syncPaypal = function (subscription) {
        $scope.content_loader_is_visible = true;

        Subscription
            .syncPaypal(subscription.id)
            .success(function (data) {
                $scope.message
                    .setText(data.message)
                    .isError(false)
                    .show();
            })
            .error(function (data) {
                $scope.message
                    .setText(data.message)
                    .isError(true)
                    .show();
            })
            .finally(function () {
                $scope.content_loader_is_visible = false;
                $scope.$broadcast('pagination:reload');
            });
    };

    $scope.momentum = function (timestamp, format) {
        return window.moment(timestamp * 1000).format(format);
    };

    $scope.closeStripe = function () {
        $scope.stripeEditModal = false;
    };

    $scope.cancel = function(subscription) {
        swal({
            html: true,
            title: $scope.words.deleteTitle,
            type: 'prompt',
            text: $scope.words.deleteMessage
                .replace(/#APP_NAME#/g, subscription.app_name)
                .replace(/#APP_ID#/g, subscription.app_id),
            showCancelButton: true,
            closeOnConfirm: false,
            closeOnCancel: true,
            confirmButtonColor: '#ff3a2e',
            confirmButtonText: $scope.words.confirmDelete,
            cancelButtonText: $scope.words.cancelDelete,
            buttons: {
                confirm: {
                    value: ''
                }
            }
        }, function (value) {
            if (value === false) {
                return;
            }
            $scope.content_loader_is_visible = true;
            Subscription
                .cancel(subscription.id, value)
                .success(function(data) {
                    $scope.message
                        .setText(data.message)
                        .isError(false)
                        .show();
                }).error(function(data) {
                    $scope.message
                        .setText(data.message)
                        .isError(true)
                        .show();

                }).finally(function() {
                    $scope.content_loader_is_visible = false;
                    $scope.$broadcast('pagination:reload');
                });

            swal.close();
            return true;
        });
    };

    $scope.toggle = function(subscription) {

        let newStatus = subscription.is_active ? 1 : 0;
        let title = newStatus ? $scope.words.deactivateTitle : $scope.words.activateTitle;
        let text = newStatus ? $scope.words.deactivateMessage : $scope.words.activateMessage;
        let textConfirm = newStatus ? $scope.words.confirmDeactivate : $scope.words.confirmActivate;

        swal({
            html: true,
            title: title,
            type: 'prompt',
            text: text
                .replace(/#APP_NAME#/g, subscription.app_name)
                .replace(/#APP_ID#/g, subscription.app_id),
            showCancelButton: true,
            closeOnConfirm: false,
            closeOnCancel: true,
            confirmButtonColor: '#ff3a2e',
            confirmButtonText: textConfirm,
            cancelButtonText: $scope.words.cancelDelete,
            buttons: {
                confirm: {
                    value: ''
                }
            }
        }, function (value) {
            if (value === false) {
                return;
            }
            $scope.content_loader_is_visible = true;
            if (!newStatus) {
                Subscription
                    .activate(subscription.id, value)
                    .success(function(data) {
                        $scope.message
                        .setText(data.message)
                        .isError(false)
                        .show();
                    }).finally(function() {
                        $scope.content_loader_is_visible = false;
                        $scope.$broadcast('pagination:reload');
                    });
            } else {
                Subscription
                .deactivate(subscription.id, value)
                .success(function(data) {
                    $scope.message
                    .setText(data.message)
                    .isError(false)
                    .show();
                }).finally(function() {
                    $scope.content_loader_is_visible = false;
                    $scope.$broadcast('pagination:reload');
                });
            }



            swal.close();
            return true;
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

    Subscription
        .find($routeParams.subscription_app_id)
        .success(function(data) {
            $scope.subscription = data.subscription ? data.subscription : {};

            $scope.is_active_checkbox_disabled = (
                $scope.subscription.payment_method == '2checkout' ||
                $scope.subscription.payment_method == 'stripe') &&
                $scope.subscription.is_active == '0';
            $scope.section_title = data.section_title;

            if ($scope.subscription.payment_method == "offline") {
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
        }).error(function (data) {
            $location.path("subscription/backoffice_application_list");
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
