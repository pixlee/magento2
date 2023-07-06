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
     * @param $websiteId
     * @return void
     */
    public function setWebsiteId($websiteId);

    /**
     * @param $websiteId
     * @return false|string
     */
    public function validateCredentials($websiteId);

    /**
     * @param $options
     * @return false|string
     */
    public function getAlbums($options = null);

    /**
     * @param $status
     * @param $jobId
     * @param $numProducts
     * @param $websiteId
     * @return void
     */
    public function notifyExportStatus($status, $jobId, $numProducts, $websiteId);

    /**
     * @param $websiteId
     * @param array $productInfo
     * @return mixed
     */
    public function createProduct(
        $websiteId,
        array $productInfo
    );

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
