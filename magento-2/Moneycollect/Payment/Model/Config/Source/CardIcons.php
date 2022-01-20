<?php

namespace Moneycollect\Payment\Model\Config\Source;

use \Magento\Framework\Data\OptionSourceInterface;

class CardIcons implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'visa', 'label' =>__('Visa')],
            ['value' => 'master', 'label' =>__('MasterCard')],
            ['value' => 'ae', 'label' =>__('American Express')],
            ['value' => 'jcb', 'label' =>__('JCB')],
            ['value' => 'discover', 'label' =>__('Discover')],
            ['value' => 'diners', 'label' =>__('Diners Club')],
            ['value' => 'maestro', 'label' =>__('Maestro')],
            ['value' => 'unionpay', 'label' =>__('UnionPay')],
        ];
    }
}