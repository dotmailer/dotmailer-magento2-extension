define([
    'Magento_Ui/js/grid/columns/column',
    'jquery',
    'mage/template',
    'text!Dotdigitalgroup_Email/templates/grid/cells/queue/message.html',
    'Magento_Ui/js/modal/modal'
], function (Column, $, mageTemplate, queueMessageTemplate) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html',
            fieldClass: {
                'data-grid-html-cell': true
            }
        },
        getHtml: function (row) { return row[this.index + '_html']; },
        getEntityId: function (row) { return row[this.index + '_entity_id']; },
        getLabel: function (row) { return row[this.index + '_html']; },
        getTitle: function (row) { return row[this.index + '_title']; },
        getContent: function (row) { return this.getMessageBody(row[this.index + '_messageUrl']); },
        getMessageBody: function (messageUrl) {
            var result;

            $.ajax({
                url: messageUrl,
                method: 'GET',
                dataType: 'JSON',
                contentType: 'application/json',
                async: false,
                success: function (response) {
                    result = response;
                }
            });
            return result;
        },
        preview: function (row) {
            var modalHtml,
            previewPopup;

            modalHtml = mageTemplate(
                queueMessageTemplate,
                {
                    html: this.getHtml(row),
                    title: this.getTitle(row),
                    label: this.getLabel(row),
                    content: this.getContent(row)
                }
            );

            previewPopup = $('<div></div>').html(modalHtml);

            previewPopup.modal(
                {
                    title: $.mage.__(this.getTitle(row)),
                    innerScroll: true,
                    modalClass: '_email-box',
                    buttons: [{
                    text: $.mage.__('Close'),
                    class: '',
                    click: function () {
                        this.closeModal();
                        }
                    }]
                }).trigger('openModal');
        },
        getFieldHandler: function (row) {
            return this.preview.bind(this, row);
        }
    });
});
