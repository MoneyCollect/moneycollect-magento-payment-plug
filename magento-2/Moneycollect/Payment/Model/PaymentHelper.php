<?php

namespace Moneycollect\Payment\Model;


class PaymentHelper extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = 'moneycollect';

    protected $_url;
    protected $_moduleList;
    protected $_session;
    protected $_cookie;
    protected $_cookieMetadataFactory;
    protected $_orderFactory;
    protected $_order;
    protected $_managerInterface;
    protected $_orderSender;
    protected $_priceCurrency;
    protected $_messageManager;
    protected $_quoteManagement;
    protected $_quoteRepository;

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

        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
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
    }


    public function getBasicConfigData($field){
        $storeId = $this->getStore();
        $path = 'payment/moneycollect/' . $field;
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getPrKey(){
        if( $this->getBasicConfigData('pay_model') === '0' ){
            return 'Bearer ' . $this->getBasicConfigData('test_pr_Key');
        }
        return 'Bearer ' . $this->getBasicConfigData('pr_Key');
    }

    public function getPuKey(){
        if( $this->getBasicConfigData('pay_model') === '0' ){
            return $this->getBasicConfigData('test_pu_Key');
        }
        return $this->getBasicConfigData('pu_Key');
    }


    public function homeUrl(){
        return $this->_url->getBaseUrl();
    }

    public function cancelUrl(){
        return $this->_url->getUrl('moneycollect/back/cancel',['_secure' => true]);
    }

    public function returnUrl(){
        return $this->_url->getUrl('moneycollect/back',['_secure' => true]);
    }

    public function notifyUrl(){
        return $this->_url->getUrl('moneycollect/webhook',['_secure' => true]);
    }


    function transformAmount($amount,$currency){
        switch ($currency){
            case strpos('CLP,ISK,VND,KRW,JPY',$currency) !== false:
                return (int)$amount;
                break;
            case strpos('IQD,KWD,TND',$currency) !== false:
                return (int)($amount*1000);
                break;
            default:
                return (int)($amount*100);
                break;
        }
    }


    function getStatusUpdate($status){
        switch ( $status ){
            case 'succeeded':
                $new_status = $this->getConfigData('success_order_status');
                break;
            case 'failed':
                $new_status = $this->getConfigData('failure_order_status');
                break;
            case 'canceled':
                $new_status = $this->getConfigData('cancel_order_status');
                break;
            case 'requires_payment_method':
            case 'requires_confirmation':
            case 'requires_action':
            case 'processing':
                $new_status = $this->getConfigData('hold_order_status');
                break;
            default:
                $new_status = '';
        }
        return $new_status;
    }

}