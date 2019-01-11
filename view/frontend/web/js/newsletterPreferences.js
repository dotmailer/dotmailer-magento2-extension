require([
    'jquery',
    'mage/calendar',
    'domReady!'
    ], function ($) {
    'use strict';

    var li = $('li a[href*="newsletter/manage"]').first();
    $('.date-field').each(function () {
        $(this).calendar({
            showTime: false
        });
    });

    li.parent().addClass('current');
    li.parent().replaceWith("<strong>" + li.text() + "</strong>");
});
