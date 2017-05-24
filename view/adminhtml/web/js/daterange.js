require(['jquery'], function (j) {
    "use strict";
    j(document).ready(function () {

        /**
         * Update url params
         * @param uri
         * @param key
         * @param value
         * @returns {string}
         */
        function updateUrlParameter(uri, key, value) {
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
         * Change urls
         * @param value
         */
        function changeUrls(value) {
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

        /**
         * Observe change on given element
         * @param value
         */
        function observeChange(value) {
            j('#' + value).change(function () {
                changeUrls(value);
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
