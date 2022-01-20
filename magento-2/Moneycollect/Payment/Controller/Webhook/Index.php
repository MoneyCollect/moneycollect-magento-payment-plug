<?php 

namespace Moneycollect\Payment\Controller\Webhook;

use Magento\Framework\App\Request\Http as HttpRequest;

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

        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof HttpRequest && $request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }

    }


    public function execute()
    {

        if ( ! isset( $_SERVER['REQUEST_METHOD'] )
            || ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
        ) {
            return;
        }

        try{
            $request_body = file_get_contents( 'php://input' );

            $result = json_decode($request_body,true);

            $this->_logger->addLog('webhook',$request_body);

            if( isset($result['type']) && strpos($result['type'],'payment') !== false ){

                $data = $result['data'];
                $order_id = $data['orderNo'];

                $order = $this->_orderFactory->create()->loadByIncrementId($order_id);

                if( !$order ){
                    throw new \Exception('order is null');
                }

                if( in_array( $order->getStatus(), ['processing','complete','closed'] )){
                    throw new \Exception('the order status cannot modify');
                }

                $this->_method->addStatus($order,$data);

                echo 'success';

            }else{
                throw new \Exception('type is not payment');
            }

        }catch (\Exception $e){
            $message = $e->getMessage();
            $this->_logger->addBug($message);
            echo $message;
        }

        exit();
    }

}


