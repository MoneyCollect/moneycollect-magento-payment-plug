<?php

namespace Moneycollect\Payment\Model;

class PaymentLogger extends \Magento\Payment\Model\Method\Logger
{
    const PATH = BP.'/var/log/moneycollect';
    const FILE = 'payment.log';
    static $systemLogger = null;
    static $isLogger = false;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Payment\Gateway\ConfigInterface $config = null
    ){
        parent::__construct($logger,$config);
        self::$isLogger = true;
    }

    public function addLog($type,$obj){

        if( !self::$isLogger ) return;

        $data = self::getPrintableObject($obj);

        if( class_exists('\Zend\Log\Writer\Stream') && class_exists('\Zend\Log\Logger') ){
            $dir = self::PATH;
            $file = self::FILE;

            if(!is_dir($dir)){
                mkdir($dir,0755);
            }

            $path = explode('.', $file);
            $path = $path[0];
            $file = $path."_".date('Ymd',time()).'.'.pathinfo($file)['extension'];

            $writer = new \Zend\Log\Writer\Stream($dir.'/'.$file);
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r('['. $type .']: '.$data,true)."\n");

        }else{
            $this->logger->info($data);
        }
    }

    public function addBug($message){
        $this->logger->debug($message);
    }

    public function getPrintableObject($obj){

        if (is_object($obj))
        {
            if (method_exists($obj, 'debug'))
                $data = $obj->debug();
            else if (method_exists($obj, 'getData'))
                $data = $obj->getData();
            else
                $data = $obj;
        }
        else if (is_array($obj))
            $data = json_encode($obj);
        else
            $data = $obj;

        return $data;
    }

}