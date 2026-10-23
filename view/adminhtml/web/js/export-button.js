/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, $t, alert) {
    'use strict';

    return function (config, element) {
        var $button = $(element);

        $button.on('click', function (event) {
            event.preventDefault();

            if ($button.hasClass('disabled')) {
                return;
            }

            $button.addClass('disabled');
            $.ajax({
                url: config.url,
                type: 'POST',
                dataType: 'json',
                data: {
                    'form_key': window.FORM_KEY
                },
                showLoader: true
            }).done(function (data) {
                console.log('Emplifi product export finished', data);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                var responseText = jqXHR.responseText || '';

                console.error('Emplifi product export failed', {
                    status: jqXHR.status,
                    statusText: jqXHR.statusText,
                    textStatus: textStatus,
                    error: errorThrown ? String(errorThrown) : '',
                    contentType: jqXHR.getResponseHeader('Content-Type'),
                    responseLength: responseText.length,
                    response: responseText.substring(0, 2000)
                });

                $button.removeClass('disabled');
                alert({
                    content: $t('There was an issue that prevented export (%1). Check the browser console for details.')
                        .replace('%1', jqXHR.status ? jqXHR.status + ' ' + textStatus : textStatus)
                });
            });
        });
    };
});
