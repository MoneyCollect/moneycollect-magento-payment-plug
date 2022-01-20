<?php 

namespace Moneycollect\Payment\Controller\Checkout;

class Confirm extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;
    protected $_session;
    protected $_orderFactory;
    protected $_method;
    protected $_formFactory;
    protected $resultFactory;
    protected $_helper ;
    protected $_api ;
    protected $_logger ;
    protected $_mccustomer;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Moneycollect\Payment\Model\PaymentMethod $method,
        \Moneycollect\Payment\Model\PaymentHelper $helper,
        \Moneycollect\Payment\Model\PaymentApi $api,
        \Moneycollect\Payment\Model\PaymentLogger $logger,
        \Moneycollect\Payment\Model\PaymentCustomer $customer
    ){
        parent::__construct($context);
        $this->_pageFactory = $pageFactory;
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->_formFactory = $formFactory;
        $this->_method = $method;
        $this->_helper = $helper;
        $this->_api = $api;
        $this->_logger = $logger;
        $this->_mccustomer = $customer;
    }

    /**
     * 支付重定向，获取参数信息，生成from表单提交
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $order = $this->_session->getLastRealOrder();
        $redirect = $this->_url->getUrl('checkout/cart',['_secure' => true]);;
        $msg = '';

        if( $order ){


            $payment = $order->getPayment();
            $mcPaymentData = $payment->getAdditionalInformation('mcPaymentData');

            if( $mcPaymentData ){
                // 确认扣款

                    $result = $this->_api->request('/payment/'. $mcPaymentData['id'] .'/confirm', '');

                if( empty($result['error']) ){
                    $body = $result['body'];

                    $this->_logger->addLog('payment confirm result', $body);

                    if( $body['code'] === 'success' ){

                        $redirect = $this->_url->getUrl('moneycollect/back',['_secure' => true]).'?payment_id='.$body['data']['id'];

                        if( isset($body['data']['nextAction']) && $body['data']['nextAction']['type'] == 'redirect' ){
                            $redirect = $body['data']['nextAction']['redirectToUrl'];
                        }

                    }else{
                        $msg = $body['msg'];
                    }
                }else{
                    $this->_logger->debug($result['error']);
                    $msg = $result['error'];
                }

            }

        }
        else{
            $msg = 'Get order instance based on last order ID is null';
        }

        echo json_encode([
            'msg' => $msg,
            'redirect' => $redirect
        ]);

        exit();
    }

}


