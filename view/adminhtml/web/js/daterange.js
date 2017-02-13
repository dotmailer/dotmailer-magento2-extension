require(['jquery'], function (j) {
    j(document).ready(function () {

        function updateUrlParameter(uri, key, value) {
            var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
            var separator = uri.indexOf('?') !== -1 ? '&' : '?';
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + '=' + value + '$2');
            }
            else {
                return uri + separator + key + '=' + value;
            }
        }

        var elmToObserve = ['from', 'to'];
        var elmToChange =
            [
                '#connector_developer_settings_sync_settings_reset_orders',
                '#connector_developer_settings_sync_settings_reset_reviews',
                '#connector_developer_settings_sync_settings_reset_wishlists',
                '#connector_developer_settings_sync_settings_reset_catalog'
            ];
        j.each(elmToObserve, function (key, value) {
            j('#' + value).change(function () {
                j.each(elmToChange, function (k, v) {
                    var str = j(v).attr('onclick');
                    var updatedUrl = updateUrlParameter(str, value, encodeURIComponent(j('#' + value).val()));
                    j(v).attr('onclick', updatedUrl);
                });
            });
        });
    })
});