define(['trackingCode'], function (_dmTrack) {
    "use strict";

    /**
     * ROI
     * @param items
     * @param total
     */
    function init(items, total) {
        var fLen = items.length;
        var i = 0;

        for (i; i < fLen; i++) {
            _dmTrack("product", items[i]);
        }
        _dmTrack("CheckOutAmount", total);
    }

    /**
     * Export/return tracking code init
     * @param roiCode
     */
    return function(roiCode) {
        if(roiCode.isEnabled) {
            init(roiCode.items, roiCode.total);
        }
    };
});

