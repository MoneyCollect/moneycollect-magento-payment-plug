<?php

namespace Moneycollect\Payment\Model\Config\Source;

use \Magento\Framework\Data\OptionSourceInterface;

class CheckoutModel implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('In-page Checkout (recommended for most websites)')],
            ['value' => '1', 'label' =>__('Hosted Payment Page (redirected for payment)')]
        ];
    }
}