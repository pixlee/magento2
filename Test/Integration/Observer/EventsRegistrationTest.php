<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Observer;

use Magento\Framework\Event\ConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Observer\AddToCartObserver;
use Pixlee\Pixlee\Observer\CheckoutSuccessObserver;
use Pixlee\Pixlee\Observer\CreateProductTriggerObserver;
use Pixlee\Pixlee\Observer\ValidateCredentialsObserver;

class EventsRegistrationTest extends TestCase
{
    /**
     * @magentoAppIsolation enabled
     * @dataProvider expectedObserversDataProvider
     */
    public function testObserverIsRegisteredForEvent($eventName, $observerName, $instance)
    {
        /** @var ConfigInterface $eventConfig */
        $eventConfig = Bootstrap::getObjectManager()->get(ConfigInterface::class);
        $observers = $eventConfig->getObservers($eventName);

        $this->assertArrayHasKey($observerName, $observers);
        $this->assertSame($instance, $observers[$observerName]['instance']);
    }

    /**
     * @return array
     */
    public function expectedObserversDataProvider()
    {
        return [
            'add to cart' => [
                'checkout_cart_product_add_after',
                'pixlee_checkout_cart_add_product',
                AddToCartObserver::class,
            ],
            'onepage checkout success' => [
                'checkout_onepage_controller_success_action',
                'pixlee_onepage_checkout_success',
                CheckoutSuccessObserver::class,
            ],
            'multishipping checkout success' => [
                'multishipping_checkout_controller_success_action',
                'pixlee_multishipping_checkout_success',
                CheckoutSuccessObserver::class,
            ],
            'product save' => [
                'catalog_product_save_after',
                'pixlee_catalog_product_save',
                CreateProductTriggerObserver::class,
            ],
            'admin config save' => [
                'admin_system_config_changed_section_pixlee_pixlee',
                'pixlee_admin_config_change',
                ValidateCredentialsObserver::class,
            ],
        ];
    }
}
