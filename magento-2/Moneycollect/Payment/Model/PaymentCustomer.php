<?php

namespace Moneycollect\Payment\Model;

class PaymentCustomer
{

    protected $_customerSession;
    protected $_helper;
    protected $_logger;
    protected $_remoteAddress;

    public $customer_id;
    public $mc_id = '';

    function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        PaymentHelper $helper,
        PaymentLogger $logger
    ){
        $this->_customerSession = $customerSession;
        $this->_remoteAddress = $remoteAddress;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->customer_id = $this->_customerSession->getCustomerId();

        if( $this->customer_id ){
            $this->setId();
        }
    }


    public function setId($mc_id = ''){
        if( !empty($mc_id) ){
            $this->mc_id = $mc_id;
        }else{
            $this->mc_id = $this->getId();
        }
    }

    public function getId(){

        if( !empty( $this->mc_id ) ){
            return $this->mc_id;
        }

        if( !$this->customer_id ){
            return '';
        }

        $resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection= $resources->getConnection();
        $themeTable = $resources->getTableName('moneycollect_customers');
        $sql = 'select * from '. $themeTable .' where customer_id = '.$this->customer_id;
        $data = $connection->fetchAll($sql);
        if( $data ){
            return $data[0]['mc_id'];
        }else{
            return '';
        }
    }

    public function getIp(){
        return $this->_remoteAddress->getRemoteAddress();
    }

    public function createId($id){

        if( empty( $this->mc_id ) ){
            try{
                $resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
                $connection= $resources->getConnection();
                $themeTable = $resources->getTableName('moneycollect_customers');
                $sql = 'Insert into '. $themeTable .'(customer_id,mc_id,create_time) Values ('. $this->customer_id .', "'. $id .'", "'. date('Y-m-d H:i:s') .'" )';
                $connection->query($sql);
            }catch (\Exception $e){
                $this->_logger->addBug('create id error :'.$e->getMessage());
            }
        }

    }

    public function deleteId(){
        try{
            $resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
            $connection= $resources->getConnection();
            $themeTable = $resources->getTableName('moneycollect_customers');
            $sql = 'Delete from '. $themeTable .' where customer_id = "'. $this->customer_id .'"';
            $connection->query($sql);
        }catch (\Exception $e){
            $this->_logger->addBug('deletc id error :'.$e->getMessage());
        }
    }


}