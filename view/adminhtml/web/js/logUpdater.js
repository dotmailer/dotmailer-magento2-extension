define(['jquery'], function ($) {
    "use strict";

    /**
     * scroll to the bottom of text
     */
    function console_scroll() {
        var logData = document.getElementById("log_data");
        var dh = logData.scrollHeight;
        var ch = logData.clientHeight;

        if (dh > ch) {
            logData.scrollTop = dh - ch;
        }
    }

    /**
     * Update elements
     * @param log
     * @param url
     */
    function doUpdate(log, url) {
        $.post(url, {log: log}, function (json) {
            $('#log_data').html(json.content);
            $('#connector-log-header').html(json.header);
            console_scroll();
        });
    }

    /**
     * Export/return log updater
     * @param logUpdater
     */
    return function(logUpdater) {
        console_scroll();

        //Observer select
        $('#connector-log-selector').change(function () {
            doUpdate($('#connector-log-selector').val(), logUpdater.url);
        });

        //Observe button click for reload
        $('#connector-log-reloader').click(function () {
            doUpdate($('#connector-log-selector').val(), logUpdater.url);
        });
    };
});