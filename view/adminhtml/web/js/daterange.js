require(['jquery'], function (j) {
    j(document).ready(function () {

        /**
         * Update url params
         * @param uri
         * @param key
         * @param value
         * @returns {string}
         */
        function updateUrlParameter(uri, key, value) {
            "use strict";
            var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i'),
                separator = uri.indexOf('?') !== -1 ? '&' : '?';

            if (uri.match(re)) {
                uri = uri.replace(re, '$1' + key + '=' + value + '$2');
            } else {
                uri = uri + separator + key + '=' + value;
            }

            return uri;
        }

        /**
         * Observe change on given element
         * @param value
         */
        function observeChange(value) {
            "use strict";
            j('#' + value).change(function () {
                changeUrls(value);
            });
        }

        /**
         * Change urls
         * @param value
         */
        function changeUrls(value) {
            "use strict";
            var elmToChange =
                [
                    '#connector_developer_settings_sync_settings_reset_orders',
                    '#connector_developer_settings_sync_settings_reset_reviews',
                    '#connector_developer_settings_sync_settings_reset_wishlists',
                    '#connector_developer_settings_sync_settings_reset_catalog'
                ];

            j.each(elmToChange, function (k, v) {
                var str = j(v).attr('onclick'),
                    updatedUrl = updateUrlParameter(str, value, encodeURIComponent(j('#' + value).val()));

                j(v).attr('onclick', updatedUrl);
            });
        }

        function start() {
            var elmToObserve = ['from', 'to'];

            j.each(elmToObserve, function (key, value) {
                observeChange(value);
            });
        }

        start();
    });
});
