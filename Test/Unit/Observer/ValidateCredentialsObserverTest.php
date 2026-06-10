<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Observer;

use Exception;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Api\PixleeServiceInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Observer\ValidateCredentialsObserver;

class ValidateCredentialsObserverTest extends TestCase
{
    public function testValidateCredentialsSkipsWhenInactive(): void
    {
        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('getScope')->with(1)->willReturn([
            'scopeType' => ScopeInterface::SCOPE_WEBSITES,
            'scopeCode' => 1,
        ]);
        $apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn(false);

        $pixleeService = $this->createMock(PixleeServiceInterface::class);
        $pixleeService->expects($this->never())->method('validateCredentials');

        $subject = new ValidateCredentialsObserver($apiConfig, $pixleeService);
        $subject->validateCredentials('1');
    }

    public function testValidateCredentialsDeletesKeysAndThrowsWhenInvalid(): void
    {
        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('getScope')->with(1)->willReturn([
            'scopeType' => ScopeInterface::SCOPE_WEBSITES,
            'scopeCode' => 1,
        ]);
        $apiConfig->method('isActive')->willReturn(true);
        $apiConfig->expects($this->once())->method('deleteActive')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1);
        $apiConfig->expects($this->once())->method('deletePrivateApiKey')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1);
        $apiConfig->expects($this->once())->method('deleteSecretKey')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1);

        $pixleeService = $this->createMock(PixleeServiceInterface::class);
        $pixleeService->expects($this->once())->method('setScope')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1);
        $pixleeService->method('validateCredentials')->willReturn(false);

        $subject = new ValidateCredentialsObserver($apiConfig, $pixleeService);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Private API Key or Secret Key.');

        $subject->validateCredentials('1');
    }

    public function testValidateCredentialsDeletesKeysWhenServiceReturnsEmptyString(): void
    {
        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('getScope')->willReturn([
            'scopeType' => ScopeInterface::SCOPE_WEBSITES,
            'scopeCode' => 1,
        ]);
        $apiConfig->method('isActive')->willReturn(true);
        $apiConfig->expects($this->once())->method('deleteActive');

        $pixleeService = $this->createMock(PixleeServiceInterface::class);
        $pixleeService->method('validateCredentials')->willReturn('');

        $subject = new ValidateCredentialsObserver($apiConfig, $pixleeService);

        $this->expectException(Exception::class);
        $subject->validateCredentials('1');
    }

    public function testValidateCredentialsPassesWhenServiceReturnsTruthy(): void
    {
        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('getScope')->willReturn([
            'scopeType' => ScopeInterface::SCOPE_WEBSITES,
            'scopeCode' => 1,
        ]);
        $apiConfig->method('isActive')->willReturn(true);
        $apiConfig->expects($this->never())->method('deleteActive');

        $pixleeService = $this->createMock(PixleeServiceInterface::class);
        $pixleeService->method('validateCredentials')->willReturn('{"ok":true}');

        $subject = new ValidateCredentialsObserver($apiConfig, $pixleeService);
        $subject->validateCredentials('1');
    }

    public function testExecuteReadsWebsiteFromEvent(): void
    {
        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('getScope')->with(2)->willReturn([
            'scopeType' => ScopeInterface::SCOPE_WEBSITES,
            'scopeCode' => 2,
        ]);
        $apiConfig->method('isActive')->willReturn(false);

        $subject = new ValidateCredentialsObserver(
            $apiConfig,
            $this->createMock(PixleeServiceInterface::class)
        );

        $event = new Event(['website' => 2]);
        $subject->execute(new Observer(['event' => $event]));
    }
}
