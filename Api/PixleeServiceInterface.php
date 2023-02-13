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
     * @return mixed
     */
    public function setWebsiteId($websiteId);

    /**
     * @param $websiteId
     * @return mixed
     */
    public function validateCredentials($websiteId);

    /**
     * @param $options
     * @return mixed
     */
    public function getAlbums($options = null);

    /**
     * @param $status
     * @param $jobId
     * @param $numProducts
     * @param $websiteId
     * @return mixed
     */
    public function notifyExportStatus($status, $jobId, $numProducts, $websiteId);

    /**
     * @param $product_name
     * @param $sku
     * @param $product_url
     * @param $product_image
     * @param $currencyCode
     * @param $price
     * @param $regionalInfo
     * @param $product_id
     * @param $aggregateStock
     * @param $variantsDict
     * @param $extraFields
     * @return mixed
     */
    public function createProduct(
        $product_name,
        $sku,
        $product_url,
        $product_image,
        $currencyCode,
        $price,
        $regionalInfo,
        $product_id = null,
        $aggregateStock = null,
        $variantsDict = null,
        $extraFields = null
    );

    /**
     * @param $path
     * @param $options
     * @return mixed
     */
    public function get($path, $options = null);

    /**
     * @param $path
     * @param $payload
     * @param $options
     * @return mixed
     */
    public function post($path, $payload, $options = null);
}
