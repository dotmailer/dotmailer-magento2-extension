define(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * Initializer
     * @param {String} url
     */
    function init(url) {
        $('#connector_data_mapping_dynamic_datafield_datafield_button').click(function () {
            var name = $('#connector_data_mapping_dynamic_datafield_datafield_name').val(),
                type = $('#connector_data_mapping_dynamic_datafield_datafield_type').val(),
                defaultVal = $('#connector_data_mapping_dynamic_datafield_datafield_default').val(),
                access = $('#connector_data_mapping_dynamic_datafield_datafield_access').val();

            if (name && type && access) {
                $.post(url, {
                    name: name, type: type, default: defaultVal, visibility: access
                }, function () {
                    window.location.reload();
                });
            }
        });
    }

    /**
     * Export/return dataFields
     * @param {Object} dataFields
     */
    return function (dataFields) {
        init(dataFields.url);
    };
});
