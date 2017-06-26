define(['domReady!'], function () {
    'use strict';

    /**
     * Create script tag
     */
    function createTag() {
        var connector = document.createElement('script'),
            s = document.getElementsByTagName('script')[0];

        connector.type = 'text/javascript';
        connector.src =
            (document.location.protocol === 'https:' ? 'https://' : 'http://') + 't.trackedlink.net/_dmpt.js';
        s.parentNode.insertBefore(connector, s);
    }

    /**
     * Export/return tracking code init
     * @param {Object} trackingCode
     */
    return function (trackingCode) {
        if (trackingCode.isEnabled) {
            createTag();
        }
    };
});

