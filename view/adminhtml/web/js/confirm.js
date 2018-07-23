require([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'domReady!'
], function ($, confirmation, $t) {
    'use strict';

    /**
     * Show confirmation widget
     * @param {Object} element
     */
    function showConfirmation(element) {
        var content;

        if (element.attr('id') === 'sync_settings_addressbook_allow_non_subscribers') {
            content = $t('You are about to allow dotmailer to import customers that haven\'t explicitly opted into your emails. This means Customers and Guests address book will contain contacts that you might not be able to reach out, depending on the applicable regulations. Do you wish to continue?');
        } else {
            content = $t('You are about to enable this feature for customers that haven\'t explicitly opted into your emails. Do you wish to continue?');
        }

        confirmation({
            title: $("label[for='" + element.attr("id") + "'] span").text(),
            content: content,
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
