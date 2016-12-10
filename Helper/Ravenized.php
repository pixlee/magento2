<?php

namespace Pixlee\Pixlee\Helper;

require_once __DIR__ . '/../sentry-php-release-1.2.x/lib/Raven/Autoloader.php';
\Raven_Autoloader::register();

trait Ravenized
{
    public function ravenize($apiKey = null) {

        if (is_null($apiKey)) {

            // If I have a '$this->apiKey' property, then I'm probably an instance of
            // \Pixlee\Pixlee\Helper\Pixlee, and can just use that
            if (property_exists($this, 'apiKey')) {
                $apiKey = $this->apiKey;
            }
            // If I am a class that has the 'getApiKey' function, then I'm probably
            // and instance of \Pixlee\Pixlee\Helper\Data, and can call it directly
            elseif (method_exists($this, 'getApiKey')) {
                $apiKey = $this->getApiKey();
            }
            // Most classes using this trait (our Observers) have a $this->_pixleeData,
            // which is an instance of \Pixlee\Pixlee\Helper\Data, and we can call
            // that instance's 'getApiKey' function
            elseif (property_exists($this, '_pixleeData')) {
                $apiKey = $this->_pixleeData->getApiKey();
            }
            // If we didn't find a way to define apiKey, just return here and give
            // up on trying to instantiate a Raven Handler
            else {
                return;
            }
        }

        // Ask this endpoint, which we expect to remain available, for a Sentry URL
        $urlToHit = "https://distillery.pixlee.com/api/v1/getSentryUrl?api_key="
                    . $apiKey . "&team=Pixlee&project=Magento+2";
        if (property_exists($this, '_logger') && !is_null($this->_logger)) {
            $this->_logger->addDebug("Asking Pixlee Distillery for Sentry URL at: "
                                     . $urlToHit);
        }

        // Make the API call
        $ch = curl_init( $urlToHit );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            )
        );
        $response   = curl_exec($ch);
        $decodedResponse = json_decode($response);

        // We expect the response to look something like this:
        //      {"url":"https://<PUBKEY>:<PRIVKEY>@sentry.io/118103"}
        if (!is_null($decodedResponse) && array_key_exists('url', $decodedResponse)) {
            $sentryUrl = $decodedResponse->{'url'};
        } else {
            return;
        }

        $this->_sentryClient = new \Raven_Client($sentryUrl);
        $this->_sentryClient->install();
    }
}
