<?php

namespace Moneycollect\Payment\Model\Config\Notifications;

class WebhookComment extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $storeManager;
    protected $storeId;
    protected $url;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Url $url,
        array $data = []
    ){
        $this->storeManager = $context->getStoreManager();
        $this->url = $url;
        parent::__construct($context, $data);
    }


    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $url = $this->url->getUrl('moneycollcet/webhook', [ "_secure" => true, '_nosid' => true ]);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url = rtrim($url, "/");
        return __( 'if chose webhook to yes, Money Collcet will be payment results notify to <a href="%1" target="_blank" rel="noopener noreferrer">%1</a>. Please ensure that this link is externally accessible.', $url);

        //return $this->_toHtml();
    }

}