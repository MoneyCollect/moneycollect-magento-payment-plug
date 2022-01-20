<?php

namespace Moneycollect\Payment\Controller\Pymethod;


class Del extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_session;
    protected $_orderFactory;
    protected $_formFactory;
    protected $resultFactory;
    protected $_customerSession;
    protected $_method;
    protected $_helper;
    protected $_api;
    protected $_logger;
    protected $_customer;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Customer\Model\Session $customerSession,
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
        $this->_customerSession = $customerSession;
        $this->_helper = $helper;
        $this->_api = $api;
        $this->_logger = $logger;
        $this->_customer = $customer;
    }

    public function execute()
    {
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);

        $code = false;

        if( isset($data['py_method_id']) ){
            $result = $this->_api->request('/payment_methods/'.$data['py_method_id'].'/detach');

            if( empty( $result['error'] ) ){
                $body = $result['body'];

                if($body['code'] === 'success'){
                    $code = true;
                }

            }

        }

        if( !$code ){
            throw new \Exception('detach error');
        }

        echo 'ok';

        exit();

    }

}