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
        if($this->_pixleeData->isActive()){
            $products = $this->_pixleeData->getUnexportedProducts();
            $products->getSelect();

            $job_id = uniqid();
            $counter = 0;
            $this->notify_export_status('started', $job_id, count($products));

            foreach ($products as $product) {
                $ids = $product->getStoreIds();
                if(isset($ids[0])) {
                    $product->getStoreId($ids[0]);
                }
                $response = $this->_pixleeData->exportProductToPixlee($product);
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

        $ch = curl_init('https://19978d4b.ngrok.io/api/v1/notifyExportStatus?api_key=' . $api_key);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
    }
}
