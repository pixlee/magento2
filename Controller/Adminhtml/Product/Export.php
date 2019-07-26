<?php
/**
 * Copyright © 2015 Pixlee
 * @author teemingchew
 */

namespace Pixlee\Pixlee\Controller\Adminhtml\Product;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;

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
        \Pixlee\Pixlee\Helper\Logger\PixleeLogger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory  = $resultJsonFactory;
        $this->request            = $request;
        $this->_pixleeData        = $pixleeData;
        $this->_logger            = $logger;
        $this->_curl              = new Curl;
    }

    public function execute()
    {
        $url = $this->request->getRequestUri();
        preg_match("/pixlee_export\/product\/export\/website_id\/(.*)\/key\//", $url, $matches);
        $websiteId = (int) ($matches[1]);
        $this->_pixleeData->initializePixleeAPI($websiteId);

        if ($this->_pixleeData->isActive()) {

            // Pagination variables
            $num_products = $this->_pixleeData->getTotalProductsCount($websiteId);
            $counter = 0;
            $limit = 100;
            $offset = 0;
            $job_id = uniqid();
            $this->notifyExportStatus('started', $job_id, $num_products);
            $categoriesMap = $this->_pixleeData->getCategoriesMap();

            while ($offset < $num_products) {
                $products = $this->_pixleeData->getPaginatedProducts($limit, $offset, $websiteId);
                $offset = $offset + $limit;

                foreach ($products as $product) {
                    $counter++;
                    $response = $this->_pixleeData->exportProductToPixlee($product, $categoriesMap, $websiteId);
                }
            }

            $this->notifyExportStatus('finished', $job_id, $counter);
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([
                'message' => 'Success!',
            ]);
        }
    }

    protected function notifyExportStatus($status, $job_id, $num_products)
    {
        $api_key = $this->_pixleeData->getApiKey();
        $payload = [
            'api_key' => $api_key,
            'status' => $status,
            'job_id' => $job_id,
            'num_products' => $num_products,
            'platform' => 'magento_2'
        ];

        $this->_curl->setOption(CURLOPT_CUSTOMREQUEST, "POST");
        $this->_curl->setOption(CURLOPT_POSTFIELDS, json_encode($payload));
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->addHeader('Content-type', 'application/json');

        $this->_curl->post('https://distillery.pixlee.com/api/v1/notifyExportStatus?api_key=' . $api_key, $payload);
    }
}
