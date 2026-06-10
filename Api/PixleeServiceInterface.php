<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Api;

interface PixleeServiceInterface
{
    /**
     * Set scope
     *
     * @param null|string $scopeType
     * @param int|null|string $scopeCode
     * @return void
     */
    public function setScope($scopeType, $scopeCode);

    /**
     * Validate credentials
     *
     * @return false|string
     */
    public function validateCredentials();

    /**
     * Get albums
     *
     * @param array|null $options
     * @return false|string
     */
    public function getAlbums($options = null);

    /**
     * Notify export status
     *
     * @param string $status
     * @param string $jobId
     * @param int $numProducts
     * @return void
     */
    public function notifyExportStatus($status, $jobId, $numProducts);

    /**
     * Create product
     *
     * @param array $productInfo
     * @return mixed
     */
    public function createProduct(array $productInfo);

    /**
     * Get data from API
     *
     * @param string $path
     * @param object|array|null $options
     * @return false|string
     */
    public function get($path, $options = null);

    /**
     * Post data to API
     *
     * @param string $path
     * @param string $payload
     * @param object|array|null $options
     * @return false|string
     */
    public function post(string $path, string $payload, $options = null);
}
