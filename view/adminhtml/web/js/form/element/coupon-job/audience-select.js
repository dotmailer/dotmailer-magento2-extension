/**
 * Audience select element — radio toggle between Lists and Segments,
 * each loaded via select2 AJAX typeahead (read-only, no tag creation).
 *
 * Mirrors the data-field-select pattern exactly.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'Dotdigitalgroup_Email/js/form/session-source-meta',
    'jquery',
    'select2',
    'mage/translate'
], function (Abstract, sourceMeta, $, select2, $t) {
    'use strict';

    return Abstract.extend({

        defaults: {
            elementTmpl: 'Dotdigitalgroup_Email/form/element/coupon-job/audience-select',
            listsUrl:    '',
            segmentsUrl: '',
            websiteId:   0,
            audienceType: 'list'
        },

        /**
         * After the element is rendered, wire up the radio buttons and
         * initialise select2 on the dropdown with the default (lists) URL.
         *
         * @param {HTMLElement} element  Root element returned by afterRender
         */
        onAfterRender: function (element) {
            const self     = this;
            const $root    = $(element);
            const $select  = $root.find('select.audience-select2');
            const $radios  = $root.find('input[name="audience_type_radio"]');

            self._initSelect2($select, self.listsUrl);
            $select.data('select2').$container.addClass('select admin__control-select');

            $radios.on('change', function () {
                const type = $(this).val();
                self.audienceType = type;
                self.value('');
                $select.val(null).trigger('change');

                const url = type === 'segment' ? self.segmentsUrl : self.listsUrl;
                self._initSelect2($select, url);
            });
        },

        /**
         * (Re-)initialise select2 on the given element with a new AJAX URL.
         *
         * @param {jQuery} $select
         * @param {string} url
         */
        _initSelect2: function ($select, url) {
            const self = this;

            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            $select.select2({
                placeholder: $t('Search for a list or segment…'),
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q:params.term || '',
                            website_id: self.websiteId
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map((entry) => {
                                return {
                                    id: entry.record.id,
                                    text:`${entry.record.name} [Total: ${$t( entry.record?.contacts )}]`,
                                    contacts: entry.record.contacts
                                }
                            })
                        };
                    },
                    cache: false
                }
            });
            $select.data('select2').$container.addClass('select admin__control-select');

            $select.on('select2:select', function (e) {
                const data = e.params.data;
                sourceMeta.update('couponJob_audienceMeta', {
                    id: data.id,
                    text: data.text.replace(/\s*\[Total:.*$/, '').trim(),
                    audienceType: self.audienceType,
                    contacts: data.contacts
                });
                self.value(data.id);
            });

            $select.on('select2:clear', function () {
                sourceMeta.remove('couponJob_audienceMeta')
                self.value('');
            });
        }
    });
});
