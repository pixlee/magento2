<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Pixlee;

class PixleeTest extends TestCase
{
    public function testGetExtensionVersionReadsFromComposerJson(): void
    {
        $composerJson = json_encode(['version' => '3.0.2']);

        $reader = $this->createMock(Read::class);
        $reader->method('isExist')->with('composer.json')->willReturn(true);
        $reader->method('isReadable')->with('composer.json')->willReturn(true);
        $reader->method('readFile')->with('composer.json')->willReturn($composerJson);

        $readFactory = $this->createMock(ReadFactory::class);
        $readFactory->method('create')->willReturn($reader);

        $componentRegistrar = $this->createMock(ComponentRegistrarInterface::class);
        $componentRegistrar->method('getPath')
            ->with(ComponentRegistrar::MODULE, Pixlee::MODULE_NAME)
            ->willReturn('/var/www/html/vendor-dev/pixlee/magento2');

        $subject = new Pixlee(
            $componentRegistrar,
            $readFactory,
            $this->createMock(ModuleList::class),
            $this->createMock(PixleeLogger::class),
            new Json()
        );

        $this->assertSame('3.0.2', $subject->getExtensionVersion());
        $this->assertSame('3.0.2', $subject->getExtensionVersion(), 'Version should be cached');
    }

    public function testGetExtensionVersionFallsBackToSetupVersion(): void
    {
        $reader = $this->createMock(Read::class);
        $reader->method('isExist')->willReturn(false);

        $readFactory = $this->createMock(ReadFactory::class);
        $readFactory->method('create')->willReturn($reader);

        $componentRegistrar = $this->createMock(ComponentRegistrarInterface::class);
        $componentRegistrar->method('getPath')->willReturn('/path/to/module');

        /** @var ModuleList&MockObject $moduleList */
        $moduleList = $this->createMock(ModuleList::class);
        $moduleList->method('getOne')
            ->with(Pixlee::MODULE_NAME)
            ->willReturn(['setup_version' => '1.2.3']);

        $subject = new Pixlee(
            $componentRegistrar,
            $readFactory,
            $moduleList,
            $this->createMock(PixleeLogger::class),
            new Json()
        );

        $this->assertSame('suv1.2.3', $subject->getExtensionVersion());
    }
}
