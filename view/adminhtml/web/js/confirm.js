require([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'domReady!'
], function ($, confirmation) {
    'use strict';

    /**
     * Show confirmation widget
     * @param {Object} element
     */
    function showConfirmation(element) {
        confirmation({
            title: $("label[for='" + element.attr("id") + "'] span").text(),
            content: 'You are about to enable this for non-subscriber contacts that haven\'t explicitly opted into ' +
            'your emails. This means you might not be able to reach out to non-subscribed contacts, depending on ' +
            'the applicable regulations. Do you wish to continue?',
            actions: {
                confirm: function () {
                    element.val(1);
                },
                cancel: function () {
                    element.val(0);
                }
            }
        });
    }

    /**
     * Init function
     */
    function init() {
        var elements = [
            $('#connector_automation_review_settings_allow_non_subscribers'),
            $('#connector_configuration_abandoned_carts_allow_non_subscribers'),
            $('#sync_settings_addressbook_allow_non_subscribers')
        ];

        $.each(elements, function (index, element) {
            $(element).on('change', function () {
                if (element.val() === '1') {
                    showConfirmation(element);
                }
            });
        });
    }

    //initialise
    init();
});
