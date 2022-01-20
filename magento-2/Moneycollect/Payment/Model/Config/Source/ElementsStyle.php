<?php

namespace Moneycollect\Payment\Model\Config\Source;

use \Magento\Framework\Data\OptionSourceInterface;

class ElementsStyle implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('One row')],
            ['value' => '1', 'label' =>__('Two rows')]
        ];
    }
}