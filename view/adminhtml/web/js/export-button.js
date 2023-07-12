/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/alert'
], function ($, alert) {
    'use strict'

    window.exportToPixlee = function(url){
        let $button = $('#pixlee_export_button')
        if ($button.hasClass('disabled')) {
            return
        }
        $button.addClass('disabled')

        $.ajax({
            url: url,
            data: {
                'form_key': FORM_KEY
            },
            showLoader: true
        }).done(function(data, textStatus) {
            console.log("Export Status: " + textStatus)
        }).fail(function() {
            $button.removeClass('disabled');
            alert({
                content: 'There was an issue that prevented export.'
            })
        })
    }
});
