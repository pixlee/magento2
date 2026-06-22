<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Base test case with PHPUnit version-aware test doubles.
 *
 * Uses stubs on PHPUnit 9+ (avoids PHPUnit 12 "mock without expectations" notices).
 * Falls back to mocks on PHPUnit 6/7/8 (Magento 2.3 / older 2.4 dev stacks).
 */
abstract class AbstractUnitTestCase extends TestCase
{
    /**
     * Entry point for Pixlee\Test\Unit\Helper\ObjectManager auto-mocks.
     *
     * @param string $type
     * @return object
     */
    public function createObjectManagerDouble(string $type)
    {
        return $this->createPassiveDouble($type);
    }

    /**
     * Partial double entry point for Pixlee ObjectManager special-case mocks.
     *
     * @param string $type
     * @param string[] $methods
     * @return object
     */
    public function createObjectManagerPartialDouble(string $type, array $methods)
    {
        return $this->createPartialPassiveDouble($type, $methods);
    }

    /**
     * Passive test double for dependencies that only need return values.
     *
     * @param string $type
     * @return object
     * @throws Exception
     */
    protected function createPassiveDouble(string $type)
    {
        if (method_exists(static::class, 'createStub')) {
            return static::createStub($type);
        }

        return $this->createMock($type);
    }

    /**
     * Passive test double with default method return values.
     *
     * @param string $type
     * @param array<string, mixed> $configuration
     * @return object
     * @throws Exception
     */
    protected function createConfiguredPassiveDouble(string $type, array $configuration)
    {
        if (method_exists(static::class, 'createConfiguredStub')) {
            return static::createConfiguredStub($type, $configuration);
        }

        if (method_exists(static::class, 'createStub')) {
            $double = static::createStub($type);
            foreach ($configuration as $method => $return) {
                $double->method($method)->willReturn($return);
            }

            return $double;
        }

        return $this->createConfiguredMock($type, $configuration);
    }

    /**
     * Partial passive double (subset of methods stubbed).
     *
     * @param string $type
     * @param string[] $methods
     * @return object
     */
    protected function createPartialPassiveDouble(string $type, array $methods)
    {
        if (method_exists(static::class, 'getStubBuilder')) {
            $builder = static::getStubBuilder($type);
            if (method_exists($builder, 'disableOriginalConstructor')) {
                $builder = $builder->disableOriginalConstructor();
            }

            return $builder->onlyMethods($methods)->getStub();
        }

        $builder = $this->getMockBuilder($type);
        if (method_exists($builder, 'disableOriginalConstructor')) {
            $builder = $builder->disableOriginalConstructor();
        }
        if (method_exists($builder, 'onlyMethods')) {
            $builder->onlyMethods($methods);
        } else {
            $builder->setMethods($methods);
        }

        return $builder->getMock();
    }
}
