<?php
namespace Pixlee\Pixlee\Helper;

class PostProductInfoToAPI
{
	public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData        = $pixleeData;
        $this->_logger            = $logger;
    }
	public function PostCartAddedProductInfo($productId, $productQuantity, $productPrice,$websiteId)
	{
		$this->_pixleeData->initializePixleeAPI($websiteId);
		$secretKey = $this->_pixleeData->getSecretKey(); #fetching the secret key stored in the pixlee config in the admin panel		
		$data = array(
            'product_id' => $productId,
			'price' => $productPrice,
			'quantity' => $productQuantity,
			'currency' => "USD"
        );
		$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$signature = base64_encode(hash_hmac('sha1', $data,  $secretKey , true));
		$urlToHit = "https://takehomemagento.herokuapp.com/analytics";
		#using curl to POST the product info added to the cart
		$ch = curl_init($urlToHit);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
            'Signature: ' . $signature
            )
        );
        $response   = curl_exec($ch);
		$responseCode = curl_getinfo($ch)['http_code'];
		$this->_logger->addInfo("[Pixlee POST API response code] :: ".$responseCode); #logging th response code to system.log file
		curl_close($ch);
	}
}
?>