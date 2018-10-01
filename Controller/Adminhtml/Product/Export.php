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
        \Magento\Framework\App\Request\Http $request,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory  = $resultJsonFactory;
        $this->request            = $request;
        $this->_pixleeData        = $pixleeData;
        $this->_logger            = $logger;
    }

    public function execute()
    {
        $referrer = $this->request->getHeader('referer');	
		preg_match("/section\/pixlee_pixlee\/key\/(.*)\/website\/(.*)\//", $referrer, $matches);		
		$websiteId =(int)($matches[2]);
        $this->_pixleeData->initializePixleeAPI($websiteId);
        if($this->_pixleeData->isActive()) {

            // Pagination variables
            $num_products = $this->_pixleeData->getTotalProductsCount($websiteId);
            $counter = 0;   
            $limit = 100;
            $offset = 0;
            $job_id = uniqid();

            while ($offset < $num_products) {
                $products = $this->_pixleeData->getPaginatedProducts($limit, $offset, $websiteId);
                $offset = $offset + $limit;

                foreach ($products as $product) {
                    $counter += 1;
                    $response = $this->_pixleeData->exportProductToPixlee($product, $websiteId);
                }
            }

            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([
                'message' => 'Success!',
            ]);
        }
    }

    private function _logPixleeMsg($message)
    {
        $this->_logger->addInfo("[Pixlee] :: ".$message);
    }
}
