<?php

namespace Moneycollect\Payment\Model\Ui;


class Card extends \Moneycollect\Payment\Model\PaymentConfigProvider
{
    protected $code = 'moneycollect';

    public function getConfig(){

        $config = parent::getConfig();

        if ($this->method->isAvailable($this->checkoutSession->getQuote())) {

            $icons = [];

            $card_icons = $this->method->getConfigData('card_icons');
            if( $card_icons != '' ){
                foreach( explode(',',$card_icons) as $value ){
                    $icons[] = $this->repository->createAsset('Moneycollect_Payment::images/card/'.$value.'.png', [])->getUrl();
                }
            }

            $config['payment'][$this->code]['icons'] = $icons;

            // 站内支付参数
            $config['payment'][$this->code]['init'] = $this->method->initConfig();

        }


        return $config;
    }

}
