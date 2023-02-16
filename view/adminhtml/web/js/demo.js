/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery'], function ($) {
    'use strict'

    window.requestPixleeDemo = function(url) {
        var $name = $('#pixlee_demo_not_a_customer_name').val();
        var $email = $('#pixlee_demo_not_a_customer_email_address').val();
        var $website = $('#pixlee_demo_not_a_customer_website').val();

        if ($name === '') {
            alert('Name field is required.');
            return;
        }

        if ($email === '' || !$email.includes('@')) {
            alert('Email field is required.');
            return;
        }
        url = "https://app.pixlee.com/leads/add"
        $.ajax({
            method: 'post',
            url: url,
            data: {
                source: 'magento_2_request_demo',
                name: $name,
                email: $email,
                website: $website
            }
        }).done(function(data, textStatus, jqXHR) {
            if (data.result === 'Successful') {
                $('#pixlee_request_demo').addClass('disabled');
                alert('Thanks for requesting a demo. We will get in touch with you shortly.');
            } else {
                alert('Opps! Something went wrong. While we are working on resolving the issue, feel free to reach us at hi@pixleeteam.com');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            alert('Opps! Something went wrong. While we are working on resolving the issue, feel free to reach us at hi@pixleeteam.com');
        })
    }
});
