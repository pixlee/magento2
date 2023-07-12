<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Api;

interface AnalyticsServiceInterface
{
    /**
     * @param $event
     * @param $payload
     * @return mixed
     */
    public function sendEvent($event, $payload);

    /**
     * @param $path
     * @param $payload
     * @param $options
     * @return mixed
     */
    public function post($path, $payload, $options = null);
}
