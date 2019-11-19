require(['jquery', 'domReady!'], function ($) {
    'use strict';

    $('.ddg-colpicker').colpick({
        /**
         * @param {String} hsb
         * @param {String} hex
         * @param {String} rgb
         * @param {String} el
         */
        onChange: function (hsb, hex, rgb, el) {
            $(el).val('#' + hex);
        }
    });
});
