<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Api;

use Magento\Store\Api\Data\StoreInterface;

interface AnalyticsServiceInterface
{
    /**
     * @param String $event
     * @param array $payload
     * @param StoreInterface $store
     * @return mixed
     */
    public function sendEvent($event, $payload, $store);

    /**
     * @param $path
     * @param $payload
     * @param $options
     * @return mixed
     */
    public function post($path, $payload, $options = null);
}
