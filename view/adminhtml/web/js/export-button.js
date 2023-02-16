/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery'], function ($) {
    'use strict'

    window.exportToPixlee = function(url){
        var $button = $('#pixlee_export_button')
        if($button.hasClass('disabled')) {
            return
        }

        $.ajax({
            url: url,
            data: {
                'form_key': FORM_KEY
            },
        }).done(function(data, textStatus, jqXHR) {
            $button.addClass('disabled');
        }).fail(function(jqXHR, textStatus, errorThrown) {
            alert('There was an issue that prevented export.')
        })
    }
});
