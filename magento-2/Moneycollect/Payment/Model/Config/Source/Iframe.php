<?php

namespace Asiabill\Payment\Model\Option;

use \Magento\Framework\Data\OptionSourceInterface;;

class Iframe implements OptionSourceInterface {
	
    /**
     * @return array
     */
	public function toOptionArray() {
        return [
            ['value' => '0', 'label' =>__('Redirect')],
            ['value' => '1', 'label' => __('Iframe')]
        ];
    }
}

