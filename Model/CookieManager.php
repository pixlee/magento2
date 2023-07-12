<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CookieManager
{
    /**
     * Name of cookie that holds private content version
     */
    const COOKIE_NAME = 'pixlee_analytics_cookie';
    const LEGACY_COOKIE_NAME = 'pixlee_analytics_cookie_legacy';

    /**
     * CookieManager
     *
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
     * @return string
     */
    public function get()
    {
        $cookie = $this->cookieManager->getCookie(self::COOKIE_NAME);
        if(!isset($cookie)) {
          $cookie = $this->cookieManager->getCookie(self::LEGACY_COOKIE_NAME);
        }
        return $cookie;
    }
}
