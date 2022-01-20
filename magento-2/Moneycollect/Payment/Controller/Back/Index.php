<?php 

namespace Moneycollect\Payment\Controller\Back;

class Index extends \Magento\Framework\App\Action\Action
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

        $paymentId = isset($_GET['payment_id'])?$_GET['payment_id']:false;

        if( $paymentId ){

            $redirect = 'checkout/cart';

            $this->_logger->addLog('back payment id',$paymentId);

            $result = $this->_api->request('/payment/'.$paymentId,'','GET');

            if( empty($result['error']) ){

                $data = $result['body'];

                $this->_logger->addLog('payment result',$data);

                if( $data['code'] == 'success' ){

                    $data = $data['data'];

                    if( in_array($data['status'] , $this->_method->complete_status)  ){
                        $this->messageManager->addSuccessMessage($data['displayStatus']);
                        $redirect ='checkout/onepage/success';
                    }else{
                        if( $data['errorMessage'] ){
                            $this->messageManager->addErrorMessage( $data['displayStatus'].': '.$data['errorMessage'] );
                        }else{
                            $this->messageManager->addErrorMessage( $data['displayStatus'] );
                        }

                    }

                    // 创建mc customer id
                    if( $this->_customer->customer_id && !empty($data['customerId']) ){
                        $this->_customer->createId($data['customerId']);
                    }

                    // 更新状态
                    // $order = $this->_session->getLastRealOrder();
                    // $this->_method->addStatus($order,$data);

                }
                else{
                    $this->messageManager->addErrorMessage( $data['code'] );
                }

            }else{
                $this->_logger->debug($result['error']);
                $this->messageManager->addErrorMessage( $result['error'] );
            }

        }

        return $this->redirect($redirect);

    }


    function redirect($redirect = ''){
        $resultRedirect = $this->resultFactory->create($this->resultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($redirect);
        return $resultRedirect;
    }


}


