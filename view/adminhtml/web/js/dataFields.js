define(['jquery'], function($){
    "use strict";

    /**
     * Initializer
     * @param url
     */
    function init(url) {
        $('#connector_data_mapping_dynamic_datafield_datafield_button').click(function () {
            var name  	  = $('#connector_data_mapping_dynamic_datafield_datafield_name').val();
            var type  	  = $('#connector_data_mapping_dynamic_datafield_datafield_type').val();
            var d_default = $('#connector_data_mapping_dynamic_datafield_datafield_default').val();
            var access    = $('#connector_data_mapping_dynamic_datafield_datafield_access').val();

            if(name && type && access) {
                $.post(url, {name: name, type: type, deafult: d_default, visiblity: access}, function () {
                    window.location.reload();
                });
            }
        });
    }

    /**
     * Export/return dataFields
     * @param dataFields
     */
    return function(dataFields) {
        init(dataFields.url);
    };
});