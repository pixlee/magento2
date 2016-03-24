<?php

namespace Pixlee\Pixlee\Helper;

class Pixlee 
{
    private $apiKey;
    private $baseURL;

    // Constructor
    public function __construct($apiKey, $logger)
    {
        // YUNFAN NOTE: This check prevents me from reaching the page where I would
        // fill in the very things it's checking for...which is very Catch-22
        /*
        if( is_null( $apiKey ) || is_null( $secretKey ) || is_null( $userID )){
            throw new Exception("An API Key, API secret, and User ID are required");
        }
        */
        $this->_logger  = $logger;
        $this->apiKey   = $apiKey;
        $this->baseURL  = "http://distillery.pixlee.com/api/v2";
    }

    public function getAlbums()
    {
        return $this->getFromAPI("/albums");
    }

    public function createProduct($product_name, $sku, $product_url , $product_image, $product_id = NULL, $aggregateStock = NULL, $variantsDict = NULL){
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
                    'name': <VAL>,
                    'sku': <VAL>,
                    'buy_now_link_url': <VAL>,
                    'product_photo': <VAL>
                }
            }
        */
        $product = array('name' => $product_name, 'sku' => $sku, 'buy_now_link_url' => $product_url,
            'product_photo' => $product_image, 'stock' => $aggregateStock,
            'native_product_id' => $product_id, 'variants_json' => $variantsDict);
        $data = array('title' => $product_name, 'album_type' => 'product', 'num_photo' => 0,
            'num_inbox_photo' => 0, 'product' => $product);
        $payload = $this->signedData($data);
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
                'Content-Type: application/json'
            )
        );
        $response   = curl_exec($ch);

        return $this->handleResponse($response, $ch);
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
            'Content-Length: ' . strlen($payload)
            )
        );
        $response   = curl_exec($ch);

        $this->_logger->addDebug("Got response: {$response}");
        return $this->handleResponse($response, $ch);
    }

    // The rails API takes a signature, which was a sha256 of the payload
    // we were about to send, JSONified.
    // There was also a check for a JSON_UNESCAPED_SLASHES constant, which would
    // 'fix' the JSON before encoding, for older PHP versions
    // Since distillery doesn't check such a signature, this function is now much simpler
    private function signedData($data){
        return json_encode($data);
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
            throw new Exception("HTTP $responseCode response from API");
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
