<?php


namespace Moneycollect\Payment\Model\Method;

use Magento\Framework\Exception\LocalizedException;

class Card extends \Moneycollect\Payment\Model\PaymentMethod
{

    protected $_paymenthod = 'card';

    public function initConfig(){

        if( $this->_request->getModuleName() !== 'checkout' || $this->_request->getControllerName() == 'cart' ){
            return [];
        }

        $payment_method = [];


        $mc_customer_id = $this->_mccustomer->getId();

        if( !empty($mc_customer_id) ){
            $result = $this->_api->request('/customers/'.$mc_customer_id,'','GET');
            if( empty($result['error']) && $result['body']['code'] !== 'success' ){
                $mc_customer_id = '';
                $this->_mccustomer->deleteId();
            }
        }


        if( $mc_customer_id && $this->getConfigData('checkout_model') == '0' ){
            $result = $this->_api->request('/payment_methods/list/'.$mc_customer_id,'','GET');

            if( empty($result['error']) ){
                $body = $result['body'];
                if( $body['code'] == 'success' && !empty($body['data']) ){

                    foreach ($body['data'] as $item){
                        if( $item['type'] == 'card' ){
                            $payment_method[] = [
                                'id' => $item['id'],
                                'card' => [
                                    'brand' => $item['card']['brand'],
                                    'expire' => '(expires '. $item['card']['expMonth'] .' / '.substr($item['card']['expYear'],2,2).' )',
                                    'last4' => $item['card']['last4'],
                                ]
                            ];
                        }

                    }
                }
            }

        }

        return [
            'api_key' => $this->_helper->getPuKey(),
            'checkout_model' => $this->getConfigData('checkout_model'),
            'save_card' => $this->getConfigData('save_card'),
            'style' => $this->getConfigData('elements_style'),
            'is_login' => $this->_customer->isLoggedIn(),
            'payment_method' => $payment_method
        ];
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if( $this->getConfigData('checkout_model') === '1' ){
            return $this;
        }

        $info = $this->getInfoInstance();

        // From Magento 2.0.7 onwards, the data is passed in a different property
        $additionalData = $data->getAdditionalData();
        if ( is_array($additionalData) ){
            $data->setData(array_merge($data->getData(), $additionalData));
        }

        $info->setAdditionalInformation('mc_method_id', $data['mc_method_id']);
        $info->setAdditionalInformation('mc_pay_type', $data['mc_pay_type']);
        $info->setAdditionalInformation('mc_save', $data['mc_save']);

        return $this;
    }

    public function initialize($paymentAction, $stateObject)
    {

        if( $this->getConfigData('checkout_model') == 1 ){
            return parent::initialize($paymentAction, $stateObject);
        }

        $info = $this->getInfoInstance();
        $this->_order = $info->getOrder();
        $amount = $this->_order->getGrandTotal();

        if( $this->_order && $amount > 0 ){

            try{

                $pm_id = $info->getAdditionalInformation('mc_method_id');

                if( empty($pm_id) ){
                    throw new \Exception(__('Payment method is null.'));
                }

                $base_data = $this->baseParams();
                $order_data = $this->orderParams();

                if( !empty($this->_mccustomer->customer_id) && empty( $this->_mccustomer->mc_id ) ){
                    // 创建cm customer
                    $result = $this->_api->request('/customers/create',$order_data['billingDetails']);
                    $this->_logger->addLog('customers create', $result);

                    if( empty($result['error']) ){
                        $body = $result['body'];
                        if( $body['code'] === 'success' ){
                            $this->_mccustomer->createId($body['data']['id']);
                        }else{
                            $this->_logger->addBug($body['msg']);
                        }
                    }

                }

                if( $info->getAdditionalInformation('mc_pay_type') == 'id' ){

                    $this->_logger->addLog('payment method ['.$pm_id.'] update', $order_data['billingDetails']);
                    $result = $this->_api->request('/payment_methods/'.$pm_id.'/update',['billingDetails' => $order_data['billingDetails']]);
                    $this->_logger->addLog('payment method update result', $result);

                    if( empty($result['error']) ){
                        $body = $result['body'];
                        if( $body['code'] != 'success' ){
                            $this->_logger->addBug($body['msg']);
                        }
                    }

                }

                $params = [
                    'orderNo' => $order_data['orderNo'],
                    'amount' => $order_data['amountTotal'],
                    'currency' => $order_data['currency'],
                    'confirmationMethod' => 'manual',
                    'lineItems' => $order_data['lineItems'],
                    'paymentMethod' => $pm_id,
                    'customerId' => $this->_mccustomer->getId(),
                    'ip' => $this->_mccustomer->getIp(),
                    'notifyUrl' => $base_data['notifyUrl'],
                    'returnUrl' => $base_data['returnUrl'],
                    'preAuth' => $base_data['preAuth'],
                    'setupFutureUsage' => $info->getAdditionalInformation('mc_save') == '1' ? 'on' : 'off',
                    'statementDescriptor' => $base_data['statementDescriptor'],
                    'website' => $base_data['website']
                ];

                if( isset($order_data['shipping']) ){
                    $params['shipping'] = $order_data['shipping'];
                }

                $this->_logger->addLog('payment create',$params);

                $result = $this->_api->request('/payment/create',$params);

                if( empty($result['error']) ){

                    $data = $result['body'];

                    $this->_logger->addLog('payment create result',$data);

                    if( $data['code'] === 'success' ){
                        $info->setAdditionalInformation('mcPaymentData', $data['data']);
                    }else{
                        throw new \Exception($data['msg']);
                    }

                }else{
                    $this->_logger->addBug($result['error']);
                    throw new \Exception(__($result['error']));
                }

            }
            catch (\Exception $e){
                $message = $e->getMessage();
                $this->_logger->addBug($message);
                throw new \Exception(__($message));
            }
        }
        else{
            throw new \Exception(__('Sorry, unable to process this payment, please try again or use alternative method.'));
        }

        return $this;
    }


}


