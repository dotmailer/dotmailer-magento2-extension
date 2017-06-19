define(['jquery'], function ($) {
    "use strict";

    function bind(id, link) {
        $('#' + id).click(function() {
            window.location.assign(link);
        });
    }

    /**
     * export/return
     */
    return function(dasboard)
    {
        bind('contact_sync', dasboard.contactLink);
        bind('importer_sync', dasboard.importerLink);
    };
});