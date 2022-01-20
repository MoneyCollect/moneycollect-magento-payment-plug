<?php

namespace Asiabill\Payment\Model\Option;

use \Magento\Framework\Data\OptionSourceInterface;

class SaveCard implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Disabled')],
            ['value' => '1', 'label' =>__('Ask the customer (Checked)')],
            ['value' => '2', 'label' =>__('Ask the customer (Unchecked)')]
        ];
    }
}