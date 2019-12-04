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
     *
     * @param selectors
     * @param url
     */
    function emailCapture(selectors) {
        $(document).on('blur', selectors.join(', '), function() {
            var email = $(this).val();
            if (!email || email === previousEmail || !validateEmail(email)) {
                return;
            }

            if (typeof window.dmPt !== 'undefined') {
                window.dmPt('identify', email);
            }
        });
    }

    /**
     * Exported/return email capture
     * @param {Object} config
     */
    return function () {
        let selectors = [];
        selectors.push('input[id="email"]');
        emailCapture(selectors);
    };
});
