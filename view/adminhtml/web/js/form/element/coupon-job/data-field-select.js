/**
 * Data field select element — select2 AJAX typeahead.
 *
 * - Fetches string-type data fields from Dotdigital via AJAX.
 * - Allows the user to type a new name and press Enter to "create" it
 *   (the field will be created server-side on coupon generation).
 * - Single-select, value stored as the field name string.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'select2',
    'mage/translate'
], function (Abstract, $, select2, $t) {
    'use strict';

    return Abstract.extend({

        defaults: {
            elementTmpl: 'Dotdigitalgroup_Email/form/element/coupon-job/data-field-select',
            searchUrl: '',
            websiteId: 0
        },

        /**
         * After the element is rendered, initialize select2 with tag support.
         *
         * @param {HTMLElement} element
         */
        onAfterRender: function (element) {
            const self = this;
            const $element  = $(element).find('select');

            $element.select2({
                placeholder: $t('Search or type a new Data Field name…'),
                allowClear: true,
                minimumInputLength: 0,
                tags: true,
                createTag: (params)=>{
                    let term = $.trim(params.term).toUpperCase().replace(/ /g, "_");
                    if (!term) return null;
                    return {
                        id: term ,
                        text: term + $t(' [Create new Datafield]'),
                        newTag: true
                    };
                },
                ajax: {
                    url: self.searchUrl,
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            website_id: self.websiteId
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map((entry) => {
                                return {
                                    id: entry.record.name,
                                    text: entry.record.name}
                            })
                        };
                    },
                    cache: false
                }
            });
            $element.data('select2').$container.addClass('select admin__control-select');

            $element.on('select2:select', function (e) {
                self.value(e.params.data.id);
            });

            $element.on('select2:clear', function () {
                self.value('');
            });
        }
    });
});


