define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'MoneyCollectSdk'
    ],
    function ($,quote,customer,mcSdk) {
        'use strict';

        return {
            mcSdk: null,
            formId: null,
            mode: 'test',
            apiKey: null,
            layout: {
                pageMode: 'block',
                style: {
                    frameMaxHeight: 100,
                    input: {
                        FontSize: 14,
                        FontFamily: '',
                        FontWeight: '',
                        Color: '',
                        ContainerBorder: '1px solid #ddd;',
                        ContainerBg: '',
                        ContainerSh: ''
                    }
                }
            },
            paymentMethodId: null,
            paymentMethodErr: null,

            initElement: function (callback) {
                var message = null;
                try {
                    this.mcSdk = window.MoneycollectPay(this.apiKey);

                    this.mcSdk.elementInit("payment_steps", {
                        formId: this.formId, // 页面表单id
                        frameId: 'moneycollect-card-frame', // 生成的IframeId
                        mode: this.mode,
                        customerId: this.customerId,
                        autoValidate:false,
                        layout: this.layout
                    }).then((res) => {
                        console.log("initRES", res);
                    }).catch((err) => {
                        console.log("initERR", err);
                        callback(err);
                    });
                }catch (e) {
                    if (typeof e != "undefined" && typeof e.message != "undefined") {
                        message = 'Could not initialize MoneyCollectSdk: ' + e.message;
                    } else {
                        message = 'Could not initialize MoneyCollectSdk';
                    }
                    callback(message);
                }

            },

            onSubmit: function (callback) {
                let billingAddress = quote.billingAddress();
                let street = billingAddress.street;
                let owner = {
                    "billingDetails": {
                        "address": {
                            "city": billingAddress.city,
                            "country": billingAddress.countryId,
                            "line1": street[0],
                            "line2": street[1],
                            "postalCode": billingAddress.postcode,
                            "state": billingAddress.region
                        },
                        "email": quote.guestEmail? quote.guestEmail: customer.customerData.email,
                        "firstName": billingAddress.firstname,
                        "lastName": billingAddress.lastname ,
                        "phone": billingAddress.telephone
                    },
                };

                let success = false;

                this.mcSdk.confirmPaymentMethod({
                        paymentMethod: owner
                }).then((result) => {
                    callback(result);
                    if( result.data.code === "success" ){
                        success = true
                    }
                }).catch((err) => {
                    callback({'data':{'data':{'msg':err}}});
                });

                return success;

            }

        }
    }
);