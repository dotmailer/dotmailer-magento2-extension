define(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * scroll to the bottom of text
     */
    function consoleScroll() {
        var logData = document.getElementById('log_data'),
            dh = logData.scrollHeight,
            ch = logData.clientHeight;

        if (dh > ch) {
            logData.scrollTop = dh - ch;
        }
    }

    /**
     * Update elements
     * @param {String} log
     * @param {String} url
     */
    function doUpdate(log, url) {
        $.post(url, {
            log: log
        }, function (json) {
            $('#log_data').html(json.content);
            $('#connector-log-header').html(json.header);
            consoleScroll();
        });
    }

    /**
     * Export/return log updater
     * @param {Object} logUpdater
     */
    return function (logUpdater) {
        consoleScroll();

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
