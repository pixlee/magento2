<?php

namespace Pixlee\Pixlee\Helper;

require_once __DIR__ . '/../sentry-php-release-1.2.x/lib/Raven/Autoloader.php';
\Raven_Autoloader::register();

trait Ravenized
{
    public function ravenize() {
        $this->_sentryClient = new \Raven_Client('https://a03c6eb8e0a344ef8f16f17f30f6ab55:a7dab98919284543b14d27d113281f7f@sentry.io/118103');
        $this->_sentryClient->install();
    }
}
