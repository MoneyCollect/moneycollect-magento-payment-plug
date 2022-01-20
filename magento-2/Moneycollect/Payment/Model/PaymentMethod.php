<?php

namespace Moneycollect\Payment\Model;

use Magento\Framework\Exception\LocalizedException;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    public static $name = "Magento2";
    public static $version = "1.0.2";
    public $complete_status = ['processing','requires_capture','succeeded'];

    /**
     * Payment code
     * @var string
     */
    protected $_code = 'moneycollect';
    protected $_paymenthod;

    /**
     * Payment Method feature
     * @var bool
     */
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_isInitializeNeeded = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = false;

    protected $_url;
    protected $_moduleList;
    protected $_session;
    protected $_orderFactory;
    protected $_order;
    protected $_managerInterface;
    protected $_orderSender;
    protected $_priceCurrency;
    protected $_messageManager;
    protected $_quoteManagement;
    protected $_quoteRepository;
    protected $_request;
    protected $_localeResolver;

    protected $_customer;
    protected $_helper;
    protected $_api;
    protected $_logger;
    protected $_mccustomer;

    public function __construct(
        \Magento\Framework\Url $url,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Directory\Model\PriceCurrency $priceCurrency,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Locale\Resolver $localeResolver,
        PaymentHelper $helper,
        PaymentApi $api,
        PaymentLogger $logger,
        PaymentCustomer $customer,

        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {

        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, []);
        $this->_url = $url;
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->_managerInterface = $managerInterface;
        $this->_orderSender = $orderSender;
        $this->_priceCurrency = $priceCurrency;
        $this->_messageManager = $messageManager;
        $this->_quoteManagement = $quoteManagement;
        $this->_quoteRepository = $quoteRepository;
        $this->_request = $request;
        $this->_localeResolver = $localeResolver;

        $this->_customer = $customerSession;
        $this->_helper = $helper;
        $this->_api = $api;
        $this->_logger = $logger;
        $this->_mccustomer = $customer;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (parent::isAvailable($quote) && $quote){
            return true;
        }
        return false;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        return $this;
    }

    public function initialize($paymentAction, $stateObject){

        $info = $this->getInfoInstance();
        $this->_order = $info->getOrder();
        $amount = $this->_order->getGrandTotal();

        if( $this->_order && $amount > 0 ){

            try{

                $params = array_merge($this->baseParams(),$this->orderParams());

                $this->_logger->addLog('checkout session',$params);

                $result = $this->_api->request('/checkout/session/create',$params);

                if( empty($result['error']) ){

                    $data = $result['body'];

                    $this->_logger->addLog('checkout session result', $data);

                    if( $data['code'] === 'success' ){
                        $info->setAdditionalInformation('mcCreateData', $data['data']);
                    }else{
                        throw new \Exception($data['msg']);
                    }

                }else{
                    $this->_logger->addBug($result['error']);
                    throw new \Exception(__($result['error']));
                }

            }catch (\Exception $e){

                $message = $e->getMessage();
                $this->_logger->addBug($message);
                throw new \Exception(__($message));

            }

        }else{
            throw new \Exception(__('Sorry, unable to process this payment, please try again or use alternative method.'));
        }


        return $this;
    }

    public function baseParams(){

        $customerData = $this->_customer->getCustomer();

        if( $this->_customer->getCustomer()->getId() ){
            $customer_email = $customerData->getData('email');
        }else{
            $customer_email = $this->_order->getBillingAddress()->getCustomerEmail();
        }


        if( empty( $this->_mccustomer->mc_id ) ){
            $customer = '';
            $customerEmail = $customer_email;
        }else{
            $customer = $this->_mccustomer->mc_id;
            $customerEmail = '';
        }

        $data = [
            'customer' => $customer,
            'customerEmail' => $customerEmail,
            'cancelUrl' => $this->_helper->cancelUrl(),
            'returnUrl' => $this->_helper->returnUrl(),
            'notifyUrl' => $this->_helper->notifyUrl(),
            'preAuth' => $this->getConfigData('pre_auth')=='1'?'y':'n',
            'statementDescriptor' => $this->getConfigData('statement_descriptor'),
            'website' => $this->_helper->homeUrl(),
            'paymentMethodTypes' => [$this->_paymenthod]
        ];

        if( empty($data['statementDescriptor']) ){
            unset($data['statementDescriptor']);
        }

        return $data;
    }

    public function orderParams(){

        $order = $this->_order;

        $billingAddress = $order->getBillingAddress();

        $data =  [
            'orderNo' =>  $order->getRealOrderId(),
            'currency' => $order->getOrderCurrencyCode(),
            'amountTotal' => $this->_helper->transformAmount($order->getTotalDue(),$order->getOrderCurrencyCode() ),
            'billingDetails' => [
                'firstName' => $billingAddress->getFirstname(),
                'lastName' => $billingAddress->getLastname(),
                'email' => $order->getCustomerEmail(),
                'phone' => $billingAddress->getTelephone(),
                'address' => [
                    'country' => $billingAddress->getCountryId(),
                    'state'=> $billingAddress->getRegionCode(),
                    'city' => $billingAddress->getCity(),
                    'line1' => $billingAddress->getStreetLine(1),
                    'line2' => $billingAddress->getStreetLine(2),
                    'postalCode' => $billingAddress->getPostcode()
                ]
            ],
        ];


        if( $order->getShippingDescription() ){
            $shippingAddress = $order->getShippingAddress();
            $data['shipping'] = [
                'firstName' => $shippingAddress->getFirstname(),
                'lastName' => $shippingAddress->getLastname(),
                'phone' => $shippingAddress->getTelephone(),
                'address' => [
                    'country' => $shippingAddress->getCountryId(),
                    'state'=> $shippingAddress->getRegionCode(),
                    'city' => $shippingAddress->getCity(),
                    'line1' => $shippingAddress->getStreetLine(1),
                    'line2' => $shippingAddress->getStreetLine(2),
                    'postalCode' => $shippingAddress->getPostcode()
                ]
            ];
        }


        $lineItems = [];
        foreach ($order->getAllItems() as $item){

            $product = $item->getProduct();

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $helperImport = $objectManager->get('\Magento\Catalog\Helper\Image');
            $imageUrl = $helperImport->init($product, 'product_page_image')->setImageFile($product->getImage())->getUrl();

            $lineItems[] = [
                'amount' => $this->_helper->transformAmount( $item->getPrice(), $data['currency']),
                'currency' => $data['currency'],
                'description' => $product->getDescription(),
                'images' => [$imageUrl],
                'name' => htmlspecialchars($item->getName()),
                'quantity' =>  (int)$item->getQtyOrdered()
            ];

        }

        $data['lineItems'] = $lineItems;

        return $data;

    }

    public function addStatus($order,$data){
        $this->_order = $order;

        $new_status = $this->_helper->getStatusUpdate($data['status']);

        if( !empty($new_status) ){

            $massage = 'Payment code: '. $this->_code .'; Transaction: '. $data['id'] .'; Status: '.$data['status'];

            if( $data['errorMessage'] ){
                $massage .= '; Error Message: '. $data['errorMessage'];
            }
            $this->_order->addStatusToHistory($new_status, $massage);

            if( $this->_order->getStatus() == $this->_helper->getBasicConfigData('success_order_status')){

                // invoice
                $invoice = $order->prepareInvoice();
                if ( $invoice->getTotalQty() ) {
                    $invoice->getOrder()->setIsInProcess(true);
                    $invoice->setTransactionId($data['id']);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $invoice->save();
                    $order->addRelatedObject($invoice);
                }

                //发送邮件
                try{
                    $this->_orderSender->send($order);
                }catch (\Exception $e){
                    $this->_logger->addBug('['. $data['orderNo'] .'] '.$e->getMessage());
                }

            }


            $this->_order->save();

        }else{
            $this->_logger->addLog($data['status'],'get status is null');
        }


    }

}