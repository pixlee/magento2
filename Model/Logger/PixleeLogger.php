<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Logger;

use Monolog\Logger;
use Stringable;

class PixleeLogger extends Logger
{
    public function __construct(
        Handler $handler
    ) {
        parent::__construct('PixleeLogger', [$handler]);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function addInfo($message, array $context = []): void
    {
        $this->info($message, $context);
    }
}
