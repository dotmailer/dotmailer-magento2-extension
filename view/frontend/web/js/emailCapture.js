define(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * Email validation
     * @param {String} sEmail
     * @returns {Boolean}
     */
    function validateEmail(sEmail) {
        var filter
            = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

        return filter.test(sEmail);
    }

    /**
     * Email capture for checkout.
     *
     * @param {String} url
     */
    function emailCaptureCheckout(url) {
        var currentEmail = '';
        var input = '#customer-email';
        var customerEmail_blur = function (event) {
            $('body').off('blur', input);
            var email = event.currentTarget.value;
            if (email && validateEmail(email) && currentEmail !== email) {
                currentEmail = email;
                $.post(url, {
                    email: email
                }).then(emailBlurHandler.bind(this));
                return false;
            }
        };
        var emailBlurHandler = function () {
            $('body').on('blur', input, customerEmail_blur.bind(this));
        };
        emailBlurHandler();
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
        if (emailCapture.isEnabled && emailCapture.type === 'checkout') {
            emailCaptureCheckout(emailCapture.url);
        }

        if (emailCapture.isEnabled && emailCapture.type === 'newsletter') {
            emailCaptureNewsletter(emailCapture.url);
        }
    };
});
