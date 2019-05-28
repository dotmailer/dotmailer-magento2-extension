define(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * Email validation
     * @param {String} sEmail
     * @returns {Boolean}
     */
    function validateEmail(sEmail) {
        var filter
            = /^([+\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

        return filter.test(sEmail);
    }

    /**
     * Email capture for checkout
     * @param {String} url
     * @param {String} input
     *
     */
    function emailCaptureCheckout(url, input) {
        var previousEmail = '';
        $('body').on('blur', input, function () {
            var email = $(this).val();

            if (email === previousEmail) {
                return false;
            }

            if (email && validateEmail(email)) {
                previousEmail = email;
                $.post(url, {
                    email: email
                });
            }
        });
    }

    /**
     * Email capture for newsletter field
     * @param {String} url
     */
    function emailCaptureNewsletter(url) {
        $('input[id=newsletter]').each(function (index, element) {
            // Observe onblur event on element
            $(element).on('blur', function () {
                var email = $(element).val();

                if (email && validateEmail(email)) {
                    $.post(url, {
                        email: email
                    });
                }
            });
        });
    }

    /**
     * Exported/return email capture
     * @param {Object} emailCapture
     */
    return function (emailCapture) {
        if (emailCapture.type === 'checkout') {
            //Pre 2.3.2
            emailCaptureCheckout(emailCapture.url, 'input[id=customer-email]');
            //From 2.3.2
            emailCaptureCheckout(emailCapture.url, 'input[id=checkout-customer-email]');
        }

        if (emailCapture.type === 'newsletter') {
            emailCaptureNewsletter(emailCapture.url);
        }
    };
});
