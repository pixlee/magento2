<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Model\Export;

use Pixlee\Pixlee\Api\PixleeServiceInterface;

/**
 * Test double that records the calls the export makes instead of talking to
 * the Emplifi/Pixlee API. Used by the product export integration tests.
 */
class PixleeServiceSpy implements PixleeServiceInterface
{
    /** @var array<int, array> */
    public $createdProducts = [];

    /** @var array<int, array{status:string, jobId:string, numProducts:int}> */
    public $exportStatuses = [];

    /** @var array<int, array{scopeType:mixed, scopeCode:mixed}> */
    public $scopes = [];

    /**
     * @inheritdoc
     */
    public function setScope($scopeType, $scopeCode)
    {
        $this->scopes[] = ['scopeType' => $scopeType, 'scopeCode' => $scopeCode];
    }

    /**
     * @inheritdoc
     */
    public function validateCredentials()
    {
        return PixleeServiceInterface::CREDENTIALS_INVALID;
    }

    /**
     * @inheritdoc
     */
    public function getAlbums($options = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function notifyExportStatus($status, $jobId, $numProducts)
    {
        $this->exportStatuses[] = [
            'status' => $status,
            'jobId' => $jobId,
            'numProducts' => $numProducts,
        ];
    }

    /**
     * @inheritdoc
     */
    public function createProduct(array $productInfo)
    {
        $this->createdProducts[] = $productInfo;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function get($path, $options = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function post(string $path, string $payload, $options = null)
    {
        return false;
    }
}
