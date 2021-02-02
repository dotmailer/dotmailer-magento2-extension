define(['jquery', 'domReady!'], function ($) {
    'use strict';

    var previousEmail;

    /**
     * Email validation
     * @param {String} sEmail
     * @returns {Boolean}
     */
    function validateEmail(sEmail) {
        return /^([+\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/
            .test(sEmail);
    }

    /**
     * Send captured email
     * For checkout, post email to emailCapture controller
     * For all types, de-anonymise the user in the tracking script (if present)
     *
     * @param {Array} selectors
     * @param {String} type - (checkout, newsletter, login)
     * @param {String} url
     */
    function emailCapture(selectors, type, url) {
        $(document).on('blur', selectors.join(', '), function () {
            var email = $(this).val();

            if (!email || email === previousEmail || !validateEmail(email)) {
                return;
            }

            if (typeof window.dmPt !== 'undefined') {
                window.dmPt('identify', email);
            }

            if (type === 'checkout') {
                $.post(url, {
                    email: email
                });
            }
        });
    }

    /**
     * Exported/return email capture
     * @param {Object} config
     */
    return function (config) {
        let selectors = [];

        switch (config.type) {
            case 'checkout' :
                selectors.push('input[id="customer-email"]');
                break;

            case 'newsletter' :
                selectors.push('input[id="newsletter"]');
                break;

            case 'login' :
                selectors.push('input[id="email"]');
                break;
        }

        if (selectors.length !== 0) {
            var ajaxUrl = config.url ? config.url : null;
            emailCapture(selectors, config.type, ajaxUrl);
        }
    };
});
