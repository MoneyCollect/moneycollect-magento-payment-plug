
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Moneycollect_Payment/js/action/get-payment-url',
    ],
    function (ko, $, Component, placeOrderAction, selectPaymentMethodAction, additionalValidators, quote, customerData, fullScreenLoader, globalMessageList, $t, getPaymentUrl
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                self: this,
                template: 'Moneycollect_Payment/payment/redirect_form',
                customRedirect: true
            },

            redirectAfterPlaceOrder: false,

            getConfig: function(){
                return window.checkoutConfig.payment[this.code];
            },

            initObservable: function() {
                this._super();
                this.hasIcons = ko.pureComputed(function() {
                    return this.getConfig().iconsLocation !== 'none';
                }, this);
                this.iconsRight = ko.pureComputed(function() {
                    return this.getConfig().iconsLocation === 'right';
                }, this);
                return this;
            },

            getIcon: function() {
                if (typeof this.code === "undefined"){
                    return "";
                }
                return this.getConfig().icons;
            },

            icons: function() {
                var icons = this.getIcon();
                var icon_path = [];
                for ( var i = 0, len = icons.length; i < len; i++ ){
                    icon_path.push({path: icons[i]});
                }
                return icon_path;
            },

            /** Redirect to Bank */
            placeOrder: function () {
                var self = this;
                var data = self.getData();

                placeOrderAction(data, self.messageContainer).done(function () {
                    getPaymentUrl(self.messageContainer).done(function (response) {
                        console.log(response);
                        $.mage.redirect(response);
                    }).error(function () {
                        fullScreenLoader.stopLoader();
                        globalMessageList.addErrorMessage({
                            message: $t('An error occurred on the server. Please try to place the order again.')
                        });
                    });
                }).error(function (e) {
                    fullScreenLoader.stopLoader();
                    globalMessageList.addErrorMessage({
                        message: $t(e.responseJSON.message)
                    });
                });
                return false;
            }
        });
    }
);
