<?php

namespace Pixlee\Pixlee\Helper;

class Pixlee {
  private $apiKey;
  private $secretKey;
  private $userID;
  private $baseURL;

  // Constructor
  public function __construct($apiKey, $secretKey, $userID){
    if( is_null( $apiKey ) || is_null( $secretKey ) || is_null( $userID )){
      throw new Exception("An API Key, API secret, and User ID are required");
    }
    $this->apiKey   = $apiKey;
    $this->secretKey= $secretKey;
    $this->userID   = $userID;
    $this->baseURL  = "https://api.pixlee.com/v1/" . $userID;
  }

  public function getAlbums(){
    return $this->getFromAPI("");
  }
  public function getPhotos($albumID, $options = NULL ){
    return $this->getFromAPI( "/albums/$albumID", $options);
  }
  public function getPhoto($albumID, $photoID, $options = NULL ){
    return $this->getFromAPI( "/albums/$albumID/photos/$photoID", $options);
  }
  // ex of $media = array('photo_url' => $newPhotoURL, 'email_address' => $email_address, 'type' => $type);
  public function createPhoto($albumID, $media){    
    // assign media to the data key
    $data           = array('media' => $media);
    $payload        = $this->signedData($data);
    return $this->postToAPI( "/albums/$albumID/photos", $payload );
  }

  public function createProduct($product_name, $sku, $product_url , $product_image){   
    // assign media to the data key
    $album          = array('album_name' => $product_name);
    $product        = array('name' => $product_name, 'sku' => $sku, 'buy_now_link_url' => $product_url, 'product_photo' => $product_image);  
    $data           = array('album' => $album, 'product' => $product);
    $payload        = $this->signedData($data);
    return $this->postToAPI( "/albums", $payload );
  }

  // Private functions
  private function getFromAPI( $uri, $options = NULL ){
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
    $urlToHit = $this->baseURL . $uri;

    $ch = curl_init( $urlToHit );
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($payload)
    )
  );
    $response   = curl_exec($ch);

    return $this->handleResponse($response, $ch);
  }

  private function signedData($data){

    //Fix for php versions that don't support JSON_UNESCAPED_SLASHES (< php 5.4)
    if(defined("JSON_UNESCAPED_SLASHES")){
      $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);
    }else{
      $jsonData = str_replace('\\/', '/', json_encode($data));
    } 

    $payload        = array('data'      => $data, 
      'api_key'   => $this->apiKey,
      'signature' => hash_hmac('sha256', $jsonData,  $this->secretKey));
    $payload        = json_encode($payload);
    return $payload;
  }

  private function handleResponse($response, $ch){
    $responseInfo   = curl_getinfo($ch);
    $responseCode   = $responseInfo['http_code'];
    $theResult      = json_decode($response);   

    curl_close($ch);

    if( !$this->isBetween( $responseCode, 200, 299 ) ){     
      throw new Exception("HTTP $responseCode response from API");
    }elseif ( is_null( $theResult->status ) ){
      throw new Exception('Pixlee did not return a status');
    }elseif( !$this->isBetween( $theResult->status, 200, 299 ) ){
      $errorMessage   = implode(',', (array)$theResult->message);
      throw new Exception("$theResult->status - $errorMessage ");
    }else{
      return $theResult;
    }
  }

  private function isBetween($theNum, $low, $high){
    if($theNum >= $low &&  $theNum <= $high){
      return true;
    }
    else{
      return false;
    }
  }
}