define(
    [
        'ko',
        'jquery',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Moneycollect_Payment/js/view/payment/method',
        'Magento_Checkout/js/action/place-order',
        'Moneycollect_Payment/js/mcsdk_init',
        'mage/translate',
        'Moneycollect_Payment/js/action/get-payment-confirm',
        'Moneycollect_Payment/js/action/del-payment-method',
    ],
    function ( ko, $, globalMessageList, quote, customer, fullScreenLoader, Component, placeOrderAction, moneycollect, $t , getPaymentConfirm, delPaymentMethod) {
        'use strict';
        addEventListener("getErrorMessage", e => {
            cardError(e.detail.errorMessage);
        });

        let cardError = (err) => {
            if( err ){
                $('#moneycollect-card-errors').html(err).removeClass('hide');
            }else {
                $('#moneycollect-card-errors').html('').addClass('hide');
            }
        };
        
        return Component.extend({

            defaults: {
                self: this,
                template: 'Moneycollect_Payment/payment/card_form',
                code: "moneycollect"
            },

            initObservable: function () {
                let card_config = this.getConfig();
                let _self = this;
                this._super().observe([
                    'mcPaymentDisable',
                    'mcError',
                    'mcMethodId',
                    'mcPayType',
                    'saveCard',
                    'mcInit'
                ]);

                this.isInpage = ko.pureComputed(function () {
                    return card_config.init.checkout_model === '0'
                });
                this.isRedirected = ko.pureComputed(function () {
                    return card_config.init.checkout_model === '1'
                });
                this.isInit = ko.pureComputed(function () {
                    if (_self.mcInit() === false){
                        return false;
                    }
                    return true;
                });
                this.cardLists = ko.pureComputed(function () {

                    if( card_config.init.payment_method.length === 0 ){
                        return false
                    }

                    return card_config.init.payment_method;
                });
                this.saveCardOn = ko.pureComputed(function() {
                    return customer.isLoggedIn()
                        && card_config.init.checkout_model === '0'
                        && card_config.init.save_card === '1';
                }, this);
                
                return this;
            },

            getConfig: function(key = ''){
                if( key === '' ){
                    return window.checkoutConfig.payment.moneycollect;
                }else {
                    return window.checkoutConfig.payment.moneycollect[key];
                }

            },

            isPlaceOrderEnabled: function () {
                if( this.getConfig().init.api_key === '' ){
                    return false;
                }
                return true;
            },

            cardFormInit: function () {

                if ( !this.isInpage() ){
                    this.mcInit(false);
                    return;
                }

                let _self = this;

                _self.mcPayType('new');
                _self.saveCard(false);

                // 绑定事件，选择paymentMethod
                this.changeMethod();
                this.delMethod();

                var formId = 'moneycollect-card-form';

                let params = this.getConfig().init;

                if( params.api_key === '' ){
                    return;
                }

                moneycollect.formId = formId;
                moneycollect.apiKey = params.api_key;
                moneycollect.layout.pageMode = params.style === '0' ? 'inner' : 'block';
                moneycollect.layout.style.frameMaxHeight = params.style === '0' ? 44 : 100;

                moneycollect.initElement(function (err) {
                    if( err ){
                        _self.mcInit(false);
                        _self.mcError(err);
                    }else {
                        _self.mcInit(true);
                        _self.mcError(null);
                    }
                });


                return true;
            },

            placeCreditcard: function () {

                if( !this.isInpage() ){
                    this.placeOrder();
                    return;
                }

                let _self = this;

                cardError(null);
                this.mcError(null);

                if( _self.mcPayType() === 'id' ){
                    _self.saveCard(false);
                    _self.placeAction();
                    return false
                }

                moneycollect.onSubmit(function (result) {

                    _self.saveCard($('#moneycollect-card-save').is(":checked"));

                    if( result.data.code === "success" ){
                        _self.mcMethodId(result.data.data.id);
                        _self.placeAction();
                    }else {
                        _self.mcError(result.data.msg);
                    }
                });
                return false;
            },

            placeAction: function () {
                let _self = this;
                var data = _self.getData();

                placeOrderAction(data, _self.messageContainer).done(function () {
                    getPaymentConfirm(_self.messageContainer).done(function (response) {
                        let data = JSON.parse(response);
                        if( data.msg !== '' ){
                            _self.mcError(data.msg);
                        }

                        $.mage.redirect(data.redirect);

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
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'mc_method_id': this.mcMethodId(),
                        'mc_pay_type': this.mcPayType(),
                        'mc_save': this.saveCard()
                    }
                };
            },

            changeMethod: function () {
                let _self = this;
                $('input[name=moneycollect_payment_method]').on ('change',function () {


                    if( $(this).val() === 'new' ){

                        _self.mcMethodId(null);
                        _self.mcPayType('new');

                        $('.moneycollect-elements').removeClass('hide');

                    }else {

                        _self.mcMethodId($(this).val());
                        _self.mcPayType('id');
                        _self.saveCard(false);

                        $('.moneycollect-elements').addClass('hide');
                        $('#moneycollect-card-save').attr('checked',false);

                    }
                });
            },

            delMethod: function () {
                let _self = this;

                $('.moneycollect-del-but').on('click',function () {
                    let id = $(this).attr('data');

                    if ( _self.mcMethodId() === id){
                        _self.mcMethodId(null);
                        _self.mcPayType('new');
                        $('#moneycollect-payment-method-new').prop('checked',true);
                        $('.moneycollect-elements').removeClass('hide');
                    }

                    _self.messageContainer.pyMethodid = id;

                    let _this = $(this);

                    delPaymentMethod(_self.messageContainer).always(function () {
                        fullScreenLoader.stopLoader();
                    }).done(function (response) {
                        if( response === 'ok' ){
                            _this.parent().remove();
                        }
                    }).error(function () {
                        globalMessageList.addErrorMessage({
                            message: $t('Something went wrong!')
                        });
                    });



                });
            }
            
        });
    }
);

