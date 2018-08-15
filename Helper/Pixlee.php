<?php

namespace Pixlee\Pixlee\Helper;

class Pixlee 
{
    private $apiKey;
    private $baseURL;

    // Constructor
    public function __construct($apiKey, $secretKey, $logger)
    {
        // YUNFAN NOTE: This check prevents me from reaching the page where I would
        // fill in the very things it's checking for...which is very Catch-22
        /*
        if( is_null( $apiKey ) || is_null( $secretKey ) || is_null( $userID )){
            throw new Exception("An API Key, API secret, and User ID are required");
        }
        */
        $this->_logger   = $logger;
        $this->apiKey    = $apiKey;
        $this->secretKey = $secretKey;
        $this->baseURL   = "http://distillery.pixlee.com/api/v2";
    }

    public function getAlbums()
    {
        return $this->getFromAPI("/albums");
    }
    public function createProduct($product_name, $sku, $product_url , $product_image, $product_id = NULL, $aggregateStock = NULL, $variantsDict = NULL, $extraFields = NULL, $currencyCode, $price, $regionalInfo)
    {
        $this->_logger->addDebug("* In createProduct");
        /*
            Converted from Rails API format to distillery API format
            Also, now sending _account_ 'api_key' instead of _user_ 'api_key'
            Instead of:
            {
                'album': {
                     'album_name': <VAL>
                 }
                'product: {
                     'name': <VAL>,
                     'sku': <VAL>,
                     'buy_now_link_url': <VAL>,
                     'product_photo': <VAL>
                 }
            }
            Is now:
            {
                'title': <VAL>,
                'album_type': <VAL>,
                'num_photo': <VAL>,
                'num_inbox_photo': <VAL>,
                'product':
                    'sku': <VAL>,
                    'product_photo': <VAL>,
                    'buy_now_link_url': <VAL>,
                    'stock': <VAL>,
                    'name': <VAL>,
                    ...
                    'regiona_info': [
                        {
                            'buy_now_link_url': <VAL>,
                            'stock': <VAL>,
                            'name': <VAL>,
                            ...
                        }
                    ]
                }
            }
        */
        $product = array(
            'name' => $product_name, 
            'sku' => $sku, 
            'buy_now_link_url' => $product_url,
            'product_photo' => $product_image, 
            'stock' => $aggregateStock,
            'native_product_id' => $product_id, 
            'variants_json' => $variantsDict,
            'extra_fields' => $extraFields, 
            'currency' => $currencyCode,
            'price' => $price,
            'regional_info' => $regionalInfo
        );

        $data = array(
            'title' => $product_name, 
            'album_type' => 'product', 
            'live_update' => false, 
            'num_photo' => 0,
            'num_inbox_photo' => 0, 
            'product' => $product
        );
        //Fix for php versions that don't support JSON_UNESCAPED_SLASHES (< php 5.4)
        if(defined("JSON_UNESCAPED_SLASHES")){
            $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $payload = str_replace('\\/', '/', json_encode($data));
        }
        return $this->postToAPI( "/albums?api_key=" . $this->apiKey, $payload );
    }

    private function getFromAPI( $uri, $options = NULL )
    {
        $apiString    = "?api_key=".$this->apiKey;
        $urlToHit     = $this->baseURL;
        $urlToHit     = $urlToHit . $uri . $apiString;

        if( !is_null($options)){
            $queryString  = http_build_query($options);
            $urlToHit     = $urlToHit . "&" . $queryString;
        }      

        $ch = curl_init( $urlToHit );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-Alt-Referer: magento2.pixlee.com'
            )
        );
        $response   = curl_exec($ch);

        $this->_logger->addInfo("Inside getFromAPI");
        $responseCode = curl_getinfo($ch)['http_code'];
        $this->_logger->addInfo("Response code = {$responseCode}");

        if( !$this->isBetween( $responseCode, 200, 299 ) ){
            return false;
        } else {
            return true;
        }
    }

    private function postToAPI($uri, $payload){
        $this->_logger->addDebug("*** In postToAPI");
        $this->_logger->addDebug("With this URI: {$uri}");
        $urlToHit = $this->baseURL . $uri;

        $ch = curl_init( $urlToHit );
        $this->_logger->addDebug("Hitting URL: {$urlToHit}");
        $this->_logger->addDebug("With payload: {$payload}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-Alt-Referer: magento2.pixlee.com',
            'Content-Length: ' . strlen($payload),
            'Signature: ' . $this->generateSignature($payload)
            )
        );
        $response   = curl_exec($ch);

        $this->_logger->addDebug("Got response: {$response}");
        return $this->handleResponse($response, $ch);
    }

    private function generateSignature($data) {
        return base64_encode(hash_hmac('sha1', $data,  $this->secretKey, true));
    }

    private function handleResponse($response, $ch){
        $responseInfo   = curl_getinfo($ch);
        $responseCode   = $responseInfo['http_code'];
        $theResult      = json_decode($response);

        curl_close($ch);

        // Unlike the rails API, distillery doesn't return such pretty statuses
        // On successful creation, we get a JSON with the created product's fields:
        //  {"id":217127,"title":"Tori Tank","user_id":1055,"account_id":216,"public_contribution":false,"thumbnail_id":0,"inbox_thumbnail_id":0,"public_viewing":false,"description":null,"deleted_at":null,"public_token":null,"moderation":false,"email_slug":"A27EfF","campaign":false,"instructions":null,"action_link":null,"password":null,"has_password":false,"collect_email":false,"collect_custom_1":false,"collect_custom_1_field":null,"location_updated_at":null,"captions_updated_at":null,"redis_count":null,"num_inbox_photos":null,"unread_messages":null,"num_photos":null,"updated_dead_at":null,"live_update":false,"album_type":"product","display_options":{},"photos":[],"created_at":"2016-03-11 04:28:45.592","updated_at":"2016-03-11 04:28:45.592"}
        // On product update, we just get a string that says:
        //  Product updated.
        // Suppose we'll check the HTTP return code, but not expect a JSON 'status' field
        if( !$this->isBetween( $responseCode, 200, 299 ) ){
            $this->_logger->addWarning("[Pixlee] :: HTTP $responseCode response from API. Not able to export/update product");
            return $theResult;
        } else {
            return $theResult;
        }
    }


    private function isBetween($theNum, $low, $high)
    {
        if($theNum >= $low &&  $theNum <= $high){
            return true;
        } else {
            return false;
        }
    }
}
