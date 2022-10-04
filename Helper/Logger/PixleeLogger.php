<?php
namespace Pixlee\Pixlee\Helper\Logger;

class PixleeLogger extends \Monolog\Logger
{
    public function __construct(
        \Pixlee\Pixlee\Helper\Logger\Handler $handler
    ) {
        parent::__construct("PixleeLogger", [$handler]);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function addInfo(string|Stringable $message, array $context = []): void
    {
        $this->info($message, $context);
    }
}
