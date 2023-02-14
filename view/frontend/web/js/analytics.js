/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['pixleeEvents'], function () {
    'use strict';
    return function (config) {
        if (typeof Pixlee_Analytics !== 'undefined') {
            window.pixlee_analytics = new Pixlee_Analytics(config.apiKey);
        }
    }
});
