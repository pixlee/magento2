<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Logger;

use Monolog\Logger;

class PixleeLogger extends Logger
{
    /**
     * @param Handler $handler
     */
    public function __construct(
        Handler $handler
    ) {
        parent::__construct('PixleeLogger', [$handler]);
    }

    /**
     * Log an informational message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function addInfo($message, array $context = []): void
    {
        $this->info($message, $context);
    }

    /**
     * Log a verbose diagnostic message; dropped unless the handler level is lowered to DEBUG
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function addDebug($message, array $context = []): void
    {
        $this->debug($message, $context);
    }
}
