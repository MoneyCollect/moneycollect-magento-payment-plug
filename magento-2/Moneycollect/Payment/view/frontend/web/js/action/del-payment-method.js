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

            var serviceUrl = 'moneycollect/pymethod/del';

            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl,
                JSON.stringify( {"py_method_id" : messageContainer.pyMethodid} ),
                undefined,
                'application/json'
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
