<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Service;

trait HttpResponseValidationTrait
{
    /**
     * @param string $path
     * @param int $status
     * @param string $body
     * @return bool
     */
    protected function isValidHttpResponse(string $path, int $status, string $body): bool
    {
        if ($status < 200 || $status > 299) {
            $this->logger->error('Invalid HTTP response from Pixlee API', [
                'status' => $status,
                'body' => $body,
                'path' => $path,
            ]);
            return false;
        }

        return true;
    }
}
