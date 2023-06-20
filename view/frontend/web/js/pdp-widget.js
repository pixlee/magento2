/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['pixleeWidget'], function () {
    'use strict';

    return function (config) {
        if (typeof Pixlee !== 'undefined') {
            Pixlee.init({apiKey: config.apiKey});
            Pixlee.addProductWidget({
                widgetId: config.widgetId,
                skuId: config.skuId,
                accountId: config.accountId
            });
            Pixlee.resizeWidget();
        }
    }
});
