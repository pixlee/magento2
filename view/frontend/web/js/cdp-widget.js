/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['pixleeWidget'], function () {
    'use strict';
    return function (config) {
        if (typeof Pixlee !== 'undefined') {
            Pixlee.init({apiKey: config.apiKey});
            Pixlee.addCategoryWidget({
                widgetId: config.widgetId,
                nativeCategoryId: config.nativeCategoryId,
                ecomm_platform: 'magento'
            });
            Pixlee.resizeWidget();
        }
    }
});
