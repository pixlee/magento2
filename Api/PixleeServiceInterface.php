<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Api;

Interface PixleeServiceInterface
{
    /**
     * @param null|string $scopeType
     * @param int|null|string $scopeCode
     * @return void
     */
    public function setScope($scopeType, $scopeCode);

    /**
     * @return false|string
     */
    public function validateCredentials();

    /**
     * @param null $options
     * @return false|string
     */
    public function getAlbums($options = null);

    /**
     * @param $status
     * @param $jobId
     * @param $numProducts
     * @return void
     */
    public function notifyExportStatus($status, $jobId, $numProducts);

    /**
     * @param array $productInfo
     * @return mixed
     */
    public function createProduct(array $productInfo);

    /**
     * @param $path
     * @param $options
     * @return false|string
     */
    public function get($path, $options = null);

    /**
     * @param string $path
     * @param string $payload
     * @param object|array|null $options
     * @return false|string
     */
    public function post(string $path, string $payload, $options = null);
}
