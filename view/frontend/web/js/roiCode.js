define(['jquery', 'dmmpt'], function ($) {
    'use strict';

    /**
     * ROI
     * @param {Array} items
     * @param {Float} total
     */
    function init(items, total) {
        var fLen = items.length,
            i = 0;

        for (i; i < fLen; i++) {
            window._dmTrack('product', items[i]);
        }
        window._dmTrack('CheckOutAmount', total);
        window._dmCallHandler();
    }

    /**
     * Export/return tracking code init
     * @param {Object} roiCode
     */
    return function (roiCode) {
        $(document).ready(function () {
            init(roiCode.items, roiCode.total);
        });
    };
});

