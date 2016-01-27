<?php
/**
 * Copyright © 2015 Pixlee
 * @author teemingchew
 */

namespace Pixlee\Pixlee\Controller\Adminhtml\Product;

use Magento\Framework\Controller\Result\JsonFactory;

class Export extends \Magento\Backend\App\Action
{
  protected $resultJsonFactory;
  protected $_pixleeData;
  protected $_logger;

  public function __construct(
      \Magento\Backend\App\Action\Context $context,
      JsonFactory $resultJsonFactory,
      \Pixlee\Pixlee\Helper\Data $pixleeData,
      \Psr\Log\LoggerInterface $logger
  ) {
      parent::__construct($context);
      $this->resultJsonFactory  = $resultJsonFactory;
      $this->_pixleeData        = $pixleeData;
      $this->_logger            = $logger;
  }

  public function saveProductLocally($product, $response)
  {
    $albumId = 0;
    if(isset($response->data->album->id)) {
      $albumId = $response->data->album->id;
    } else if(isset($response->data->product->album_id)) {
      $albumId = $response->data->product->album_id;
    }

    if($albumId) {
      try {
        $album = $this->_objectManager->create('Pixlee\Pixlee\Model\Album');
        $album->setProductId($product->getId());
        $album->setPixleeAlbumId($albumId);
        $album->save();
        $this->_logger->addInfo("Product saved successfully. ID is {$product->getId()}");
        return true;
      } catch (Exception $e) {
        $this->_logger->addInfo($e->getMessage());
      }
    } else {
      $this->_logger->addInfo("Product not saved");
      return false;
    }
  }

  public function execute()
  {
    if($this->_pixleeData->isActive()){
      $products = $this->_pixleeData->getUnexportedProducts();
      $products->getSelect();
      foreach ($products as $product) {
        $ids = $product->getStoreIds();
        if(isset($ids[0])) {
          $product->getStoreId($ids[0]);
        }
        $response = $this->_pixleeData->exportProductToPixlee($product);
        $this->saveProductLocally($product, $response);
      }

      $resultJson = $this->resultJsonFactory->create();
      return $resultJson->setData([
          'message' => 'Success!',
      ]);
    }
  }

  private function _logPixleeMsg($message){
    $this->_logger->addInfo("[Pixlee] :: ".$message);
  }
}
