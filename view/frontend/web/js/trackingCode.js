define([], function () {
    "use strict";

    /**
     * Create script tag
     */
    function createTag() {
        var connector = document.createElement('script');
        var s = document.getElementsByTagName('script')[0];

        connector.type = 'text/javascript';
        connector.src
            = ('https:' === document.location.protocol ? 'https://' : 'http://') + 't.trackedlink.net/_dmpt.js';
        s.parentNode.insertBefore(connector, s);
    }

    /**
     * Export/return tracking code init
     * @param trackingCode
     */
    return function(trackingCode) {
        if(trackingCode.isEnabled) {
            createTag();
        }
    };
});

