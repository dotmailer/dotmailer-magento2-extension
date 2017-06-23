require(['jquery', 'jquery/ui', 'domReady!'], function ($) {
    'use strict';

    var el = $('.ddg-datepicker');

    el.datepicker({
        dateFormat: 'yy-mm-dd'
    });
    el.addClass('datepicker');
});
