require(['jquery', 'domReady!'], function ($) {
    'use strict';

    $(document).ready(function () {

        /**
         * Update url params
         * @param {String} uri
         * @param {String} key
         * @param {String} value
         * @returns {String}
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
         * @param {String} value
         */
        function changeUrls(value) {
            var elmToChange =
                [
                    '#row_connector_developer_settings_sync_settings_reset_orders',
                    '#row_connector_developer_settings_sync_settings_reset_reviews',
                    '#row_connector_developer_settings_sync_settings_reset_wishlists',
                    '#row_connector_developer_settings_sync_settings_reset_catalog'
                ];

            $.each(elmToChange, function (k, v) {
                var button = $(v).find('button'),
                    str = button.attr('onclick'),
                    updatedUrl = updateUrlParameter(str, value, encodeURIComponent($('#' + value).val()));

                button.attr('onclick', updatedUrl);
            });
        }

        /**
         * Observe change on given element
         * @param {String} value
         */
        function observeChange(value) {
            $('#' + value).change(function () {
                changeUrls(value);
            });
        }

        /**
         * Init
         */
        function start() {
            var elmToObserve = ['from', 'to'];

            $.each(elmToObserve, function (key, value) {
                observeChange(value);
            });
        }

        start();
    });
});
