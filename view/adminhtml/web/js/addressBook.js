define(['jquery','domReady!'], function ($) {
    'use strict';

    /**
     * Initializer
     * @param {String} url
     */
    function init(url) {
        $('#sync_settings_dynamic_addressbook_addressbook_button').click(function () {
            var name = $('#sync_settings_dynamic_addressbook_addressbook_name').val(),
                visibility = $('#sync_settings_dynamic_addressbook_visibility').val();

            if (name && visibility) {
                $.post(url, {
                    name: name, visibility: visibility
                }, function () {
                    window.location.reload();
                });
            }
        });
    }

    /**
     * Export/return addressBook
     * @param {Object} addressBook
     */
    return function (addressBook) {
        init(addressBook.url);
    };
});
