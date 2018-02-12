define(['dmpt', 'domReady!'], function (_dmTrack) {
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
            _dmTrack('product', items[i]);
        }
        _dmTrack('CheckOutAmount', total);
    }

    /**
     * Export/return tracking code init
     * @param {Object} roiCode
     */
    return function (roiCode) {
        if (roiCode.isEnabled) {
            init(roiCode.items, roiCode.total);
        }
    };
});

