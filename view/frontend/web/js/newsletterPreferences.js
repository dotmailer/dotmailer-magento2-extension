require([
    'jquery',
    'mage/calendar'
    ], function ($) {
    'use strict';

    $('.date-field').each(function () {
        $(this).calendar({
            showTime: false
        });
    });
});
