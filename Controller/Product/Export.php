<?php
/**
 * Copyright © 2015 Pixlee
 * @author teemingchew
 */

namespace Pixlee\Pixlee\Controller\Product;

abstract class Export extends \Magento\Backend\App\Action
{
  public function __construct(
      \Psr\Log\LoggerInterface $logger
  ) {
      $this->_logger      = $logger;
  }

  public function execute()
  {
    $this->_logger->addInfo("[Pixlee] :: exporting!!");
  }

  // protected $_coreResource;
  // protected $_pixleeData;
  // protected $_logger;
  // protected $_pixleeAPI;

  // public function __construct(
    /*\Magento\Catalog\Model\Product $catalogProduct,
    \Magento\Backend\Helper\Data $adminhtmlData,
    \Pixlee\Pixlee\Helper\Data $pixleeData,
    \Psr\Log\LoggerInterface $logger*/
  //){
    // $this->_catalogProduct    = $catalogProduct;
    // $this->_adminhtmlData = $adminhtmlData;
    // $this->_pixleeData  = $pixleeData;
    // $this->_logger            = $logger;
  // }

  // private function _logPixleeMsg($message){
  //   $this->_logger->addInfo("[Pixlee] :: ".$message);
  // }

  // public function getNewPixlee() {
  //   if(!empty($this->_pixleeAPI)) {
  //     return $this->_pixleeAPI;
  //   } elseif($this->_pixleeData->isActive()){
  //     $pixleeKey = $this->_pixleeData->getApiKey();
  //     $pixleeSecret = $this->_pixleeData->getSecretKey();
  //     $pixleeUserId = $this->_pixleeData->getUserId();

  //     try {
  //       $this->_pixleeAPI = new Pixlee_Pixlee($pixleeKey, $pixleeSecret, $pixleeUserId);
  //       return $this->_pixleeAPI;
  //     } catch (Exception $e) {
  //       $this->_logPixleeMsg($e->getMessage());
  //     }
  //   }
  // }

  // public function execute() {
  //   var_dump('hello');
    // $this->getResponse()->setBody("<h1>Hello there</h1>");

    // $pixlee = $this->getNewPixlee();
    // if($this->_pixleeData->isActive()){
    //   $products = $this->getUnexportedProducts();
    //   $products->getSelect()->limit(10);
    //   foreach ($products as $product) {
    //     $ids = $product->getStoreIds();
    //     if(isset($ids[0])) {
    //       $product->getStoreId($ids[0]);
    //     }
    //     $pixlee->exportProductToPixlee($product);
    //   }

    //   $count = $this->getUnexportedProducts()->count();
    //   if($count) {
    //     $json = array(
    //       'action' => 'continue',
    //       'url' => $this->_adminhtmlData->getUrl('*/pixlee_export/export'),
    //       'remaining' => $count
    //       );
    //   } else {
    //     $json = array('action' => 'success');
    //   }
    // }

    // $json['pixlee_remaining_text'] = $helper->getPixleeRemainingText();
    // $this->getResponse()->setHeader('Content-type', 'application/json');
    // $this->getResponse()->setBody(json_encode($json));
  // }
}
