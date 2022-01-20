<?php

namespace Moneycollect\Payment\Model\Config\Source;

use \Magento\Framework\Data\OptionSourceInterface;;

class IconLocation implements OptionSourceInterface {
	
    /**
     * @return array
     */
	public function toOptionArray() {
        return [
            ['value' => 'none', 'label' =>__('Disabled')],
            ['value' => 'left', 'label' => __('Left hand side of the title')],
            ['value' => 'right', 'label' => __('Right hand side of the title')],
        ];
    }
}

