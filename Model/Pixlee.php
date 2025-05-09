<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Exception;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Serialize\SerializerInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class Pixlee
{
    const MODULE_NAME = 'Pixlee_Pixlee';
    const PLATFORM = 'magento_2';

    /**
     * @var string
     */
    protected $version;

    /**
     * @var ComponentRegistrarInterface
     */
    protected $componentRegistrar;
    /**
     * @var ReadFactory
     */
    protected $readFactory;
    /**
     * @var ModuleList
     */
    protected $moduleList;
    /**
     * @var PixleeLogger
     */
    protected $logger;
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     * @param ModuleList $moduleList
     * @param PixleeLogger $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory,
        ModuleList $moduleList,
        PixleeLogger $logger,
        SerializerInterface $serializer
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
        $this->moduleList = $moduleList;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Attempt to get version from composer.json or fallback to module setup_version
     * @return string
     */
    public function getExtensionVersion()
    {
        if (empty($this->version)) {
            try {
                $path = $this->componentRegistrar->getPath(
                    ComponentRegistrar::MODULE,
                    self::MODULE_NAME
                );
                $reader = $this->readFactory->create($path);
                $fileName = 'composer.json';
                if ($reader->isExist($fileName) && $reader->isReadable($fileName)) {
                    $composerJsonData = $reader->readFile($fileName);
                    $data = $this->serializer->unserialize($composerJsonData);
                    $this->version = $data['version'];
                    return $this->version;
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
            $this->version = 'suv' . $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
        }

        return $this->version;
    }
}
