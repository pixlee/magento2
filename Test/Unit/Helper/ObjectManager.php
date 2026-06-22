<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as MagentoObjectManager;

/**
 * ObjectManager helper that uses passive test doubles for auto-generated mocks.
 *
 * Magento's default helper calls TestCase::createMock(), which triggers PHPUnit 12 notices.
 */
class ObjectManager extends MagentoObjectManager
{
    /**
     * @inheritdoc
     */
    protected function _getMockWithoutConstructorCall($className)
    {
        if (method_exists($this->_testObject, 'createObjectManagerDouble')) {
            return $this->_testObject->createObjectManagerDouble($className);
        }

        return parent::_getMockWithoutConstructorCall($className);
    }

    /**
     * @inheritdoc
     */
    protected function _getResourceModelMock()
    {
        if (!method_exists($this->_testObject, 'createObjectManagerPartialDouble')) {
            return parent::_getResourceModelMock();
        }

        $resourceMock = $this->_testObject->createObjectManagerPartialDouble(
            \Magento\Framework\Module\ModuleResource::class,
            ['getIdFieldName', '__sleep', '__wakeup']
        );
        $resourceMock->method('getIdFieldName')->willReturn('id');

        return $resourceMock;
    }
}
