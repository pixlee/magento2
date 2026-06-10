<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Service;

/**
 * Documented inbound-analytics response shapes for unit tests.
 *
 * Pixlee docs state success is HTTP 200 to inbound-analytics.pixlee.com/events/*.
 *
 * Replace or extend these fixtures with captured production responses when available.
 */
final class AnalyticsResponseFixtures
{
    public const HTTP_OK = 200;

    public const HTTP_SERVER_ERROR = 500;

    /** HTTP 200 with an empty body — documented success case. */
    public const EMPTY_SUCCESS_BODY = '';

    /** HTTP 200 with production success body from inbound-analytics. */
    public const JSON_OK_STATUS_BODY = '{"status":"OK"}';

    public const MALFORMED_JSON_BODY = 'not-json';
}
