<?php 

namespace Moneycollect\Payment\Controller\Back;

class Cancel extends \Magento\Framework\App\Action\Action
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

        $redirect = '';

        $order = $this->_session->getLastRealOrder();

        if( $order ){

            $redirect = 'checkout/cart';

            $status = $this->_helper->getStatusUpdate('canceled');

            $this->_logger->addLog('canceled', 'customer waives payment');

            $order->addStatusToHistory($status, 'customer waives payment');

            $order->save();

        }

        return $this->redirect($redirect);

    }


    function redirect($redirect = ''){
        $resultRedirect = $this->resultFactory->create($this->resultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($redirect);
        return $resultRedirect;
    }


}


