define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
    ],
    function ( ko, $, Component ) {
        'use strict';

        return Component.extend({
            initObservable: function () {
                this._super();
                this.saveCard = ko.pureComputed(function() {
                    return false;
                }, this);

                return this;
            }

        });
    }
);
