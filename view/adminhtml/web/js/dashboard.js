define(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * @param {String} id
     * @param {String} link
     */
    function bind(id, link) {
        $('#' + id).click(function () {
            window.location.assign(link);
        });
    }

    /**
     * export/return
     */
    return function (dashboard) {
        bind('contact_sync', dashboard.contactLink);
        bind('importer_sync', dashboard.importerLink);
    };
});
