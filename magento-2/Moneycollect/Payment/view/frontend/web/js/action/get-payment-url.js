define(
    [
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (urlBuilder, storage, errorProcessor, fullScreenLoader) {
        'use strict';
        return function (messageContainer) {
            var serviceUrl = '/moneycollect/checkout/redirect';

            fullScreenLoader.startLoader();

            return storage.get(
                serviceUrl
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
