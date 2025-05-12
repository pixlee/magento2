<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Logger;

use Magento\Framework\Logger\Handler\System;
use Monolog\Logger;

class Handler extends System
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/pixlee.log';

    /**
     * Must use Logger::INFO for backwards compatibility
     * @var int
     */
    protected $loggerType = Logger::INFO;
}
