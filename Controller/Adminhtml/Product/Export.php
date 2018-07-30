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

    public function execute()
    {
        if($this->_pixleeData->isActive()) {
            $referrer = $this->request->getHeader('referer');
            $parts = explode("/", $referrer);
            $websiteId = (int) $parts[sizeof($parts) - 2];

            // Pagination variables
            $num_products = $this->_pixleeData->getTotalProductsCount($websiteId);
            $counter = 0;   
            $limit = 100;
            $offset = 0;
            $job_id = uniqid();
            $this->notify_export_status('started', $job_id, $num_products);
            $categoriesMap = $this->_pixleeData->getCategoriesMap();

            while ($offset < $num_products) {
                $products = $this->_pixleeData->getPaginatedProducts($limit, $offset, $websiteId);
                $offset = $offset + $limit;

                foreach ($products as $product) {
                    $counter += 1;
                    $response = $this->_pixleeData->exportProductToPixlee($product, $categoriesMap, $websiteId);
                }
            }

            $this->notify_export_status('finished', $job_id, $counter);
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

    protected function notify_export_status($status, $job_id, $num_products) {
        $api_key = $this->_pixleeData->getApiKey();
        $payload = array(
            'api_key' => $api_key,
            'status' => $status,
            'job_id' => $job_id,
            'num_products' => $num_products,
            'platform' => 'magento_2'
        );

        $ch = curl_init('https://distillery.pixlee.com/api/v1/notifyExportStatus?api_key=' . $api_key);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
    }
}
