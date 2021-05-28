require(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * @param from
     * @param to
     * @param currentUrl
     * @returns {string}
     */
    function updateUrl(from, to, currentUrl)
    {
        var url = new URL(currentUrl);

        if (from && to) {
            url.searchParams.append("from", from);
            url.searchParams.append("to", to);
        }

        return url.href;
    }

    $(".ddg-reset").on("click", function ()
    {
        var button = $('#' + this.id ),
            from = $("#from").val(),
            to = $("#to").val(),
            url = button.data("ddg-url");

        window.location.href = updateUrl(from, to, url);
    });
});
