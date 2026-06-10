<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CookieManager
{
    /**
     * Name of cookie that holds private content version
     */
    public const COOKIE_NAME = 'pixlee_analytics_cookie';
    public const LEGACY_COOKIE_NAME = 'pixlee_analytics_cookie_legacy';

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Get form key cookie
     *
     * @return string|null
     */
    public function get(): ?string
    {
        $cookie = $this->cookieManager->getCookie(self::COOKIE_NAME);
        if (!isset($cookie)) {
            $cookie = $this->cookieManager->getCookie(self::LEGACY_COOKIE_NAME);
        }
        return $cookie;
    }
}
