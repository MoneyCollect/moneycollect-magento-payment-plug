<?php 

namespace Moneycollect\Payment\Controller\Checkout;

class Redirect extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;
    protected $_session;
    protected $_orderFactory;
    protected $_method;
    protected $_formFactory;
    protected $resultFactory;
    protected $_helper ;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Moneycollect\Payment\Model\PaymentMethod $method,
        \Moneycollect\Payment\Model\PaymentHelper $helper
    ){
        parent::__construct($context);
        $this->_pageFactory = $pageFactory;
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->_formFactory = $formFactory;
        $this->_method = $method;
        $this->_hepler = $helper;
    }

    /**
     * 支付重定向，获取参数信息，生成from表单提交
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $order = $this->_session->getLastRealOrder();
        $redirect = '';

        if( $order ){
            $payment = $order->getPayment();
            $mcCreateData = $payment->getAdditionalInformation('mcCreateData');
            if( $mcCreateData ){
                $redirect = $mcCreateData['url'];
            }
        }
        echo $redirect;
        exit();
    }

}


