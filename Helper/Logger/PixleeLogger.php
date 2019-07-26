<?php
namespace Pixlee\Pixlee\Helper\Logger;

class PixleeLogger extends \Monolog\Logger
{
    public function __construct(
        \Pixlee\Pixlee\Helper\Logger\Handler $handler
    ) {
        parent::__construct("PixleeLogger", [$handler]);
    }
}
