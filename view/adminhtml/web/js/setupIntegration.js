
require([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/url',
    'eventsource'
], function ($, confirmation) {
    'use strict';
    const confirmationBody = 'By clicking confirm you agree to reset your data.',
        confirmationTitle = 'Are you sure?',
        errorMessage = 'There was a problem setting up your integration. Please check your logs for more detail.',
        placeholderElement = '' +
            '<div class="event-message-placeholder">' +
                '<div class="dd-ellipsis">' +
                    '<div></div>' +
                    '<div></div>' +
                    '<div></div>' +
                    '<div></div>' +
                '</div> ' +
                '<p>' + $.mage.__('Running setup integration, please wait...') + '</p>' +
            '</div>',

    /**
     * Handle progress update
     * Print message to screen when progress update is received
     * @param event
     * @param element
     */
    updateDom = function (event,element) {

        let container = element.parent(),
            messageElement = $('.event-message-placeholder'),
            message = JSON.parse(event.data);

        messageElement
            .css('visibility', 'visible')
            .removeClass('event-message-placeholder')
            .addClass(message.success ? 'message-success' : 'message-warning')
            .addClass('message')
            .addClass('dd-progress')
            .html($.mage.__(message.data));

        container.append(messageElement);
        container.append(placeholderElement);
    },

    /**
     * Handle Open event
     * Disable Call-to-action button when Stream Opens
     */
    prepareDom = function () {
        $('#row_connector_api_credentials_api_integration_setup').hide();
        $('.dd-progress').remove();
        $('.setup-progress').parent().append(placeholderElement);
        $('button.ddg-integration').attr('disabled', true);
    },

    /**
     * Handle close event
     * Close EventStream when receiving close event
     * @param event
     * @param source
     */
    onClose = function (event,source) {
        source.close();
        $('#row_connector_api_credentials_api_integration_setup').show();
        $('.event-message-placeholder').remove();
        $('button.ddg-integration').attr('disabled', false);
    },

    /**
     * Handle error event
     * @param errorEvent
     * @param source
     */
    onError = function (errorEvent,source) {
        onClose(errorEvent,source);
        console.log(errorEvent);
        $('div.setup-progress').css('visibility', 'visible')
            .addClass('message-error')
            .addClass('message')
            .html($.mage.__(errorMessage));
    },

    /**
     * Start EventSource
     */
    setupAccountAsync = function () {

        const setupElement = $('.setup-progress'),
            backend_url = $('.ddg-integration').val(),
            source = new EventSource(backend_url);

        source.addEventListener('close', function (event) {onClose(event,source);},false);
        source.addEventListener('error', function (event) {onError(event,source);},false);
        source.addEventListener('InvalidConfiguration',function (event) {updateDom(event,setupElement);}, false);
        source.addEventListener('AddressBooks', function (event) {updateDom(event,setupElement);}, false);
        source.addEventListener('DataFields', function (event) {updateDom(event,setupElement);}, false);
        source.addEventListener('EnableSyncs', function (event) {updateDom(event,setupElement);}, false);
        source.addEventListener('EasyEmailCapture', function (event) {updateDom(event,setupElement);}, false);
        source.addEventListener('Orders', function (event) {updateDom(event,setupElement);}, false);
        source.addEventListener('Products', function (event) {updateDom(event, setupElement);}, false);
        source.addEventListener('CronCheck', function (event) {updateDom(event, setupElement);}, false);
    },

    /**
     * Show Confirmation
     */
    showConfirmation = function () {

       confirmation({
            title: $.mage.__(confirmationTitle),
            content: $.mage.__(confirmationBody),
            actions: {
                confirm:function (event) {
                    prepareDom();
                    setupAccountAsync(event);
                }
            }
        });

    };

    $(window).on('load', function(){
        $('.ddg-integration').click(showConfirmation);
    });
});

