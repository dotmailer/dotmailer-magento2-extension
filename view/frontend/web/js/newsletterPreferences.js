require([
    'jquery',
    'mage/calendar',
    'domReady!'
    ], function ($) {
    'use strict';

    $('.date-field').each(function () {
        $(this).calendar({
            showTime: false
        });
    });
});
