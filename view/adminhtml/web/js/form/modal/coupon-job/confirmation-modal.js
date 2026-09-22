define([
    'uiElement',
    'jquery',
    'ko',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Dotdigitalgroup_Email/js/form/session-source-meta'
], function (Element, $, ko, modal, $t, sourceMeta) {
    'use strict';

    return Element.extend({

        defaults: {
            template: 'Dotdigitalgroup_Email/form/modal/coupon-job/confirmation-modal',
            modalSelector: '#coupon-job-confirmation-modal',
            batchSize: 0,
            codeSeparator: '-',
            data: {
                code_prefix: "",
                code_suffix: "",
                code_format: "",
                code_length: "",
                code_dash: "",
                expires_at: "",
                sales_rule_id: "",
                audience: "",
                data_field: "",
                audienceMeta: {
                    id: "",
                    text: "",
                    audienceType: "",
                    contacts: 0
                },
                salesRuleMeta: {
                    id: "0",
                    label: ""
                }
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.data = ko.observable(this.data);
            return this;
        },

        /**
         * Called by KO's afterRender binding once the template is attached to
         * the live DOM. At this point it is safe to hand the element to the
         * modal widget (which needs to appendTo body).
         *
         * @param {HTMLElement} element  The rendered root element
         */
        onAfterRender: function (element) {
            const self       = this;
            const $container = $(element);

            this._$modal = $container;

            modal({
                type      : 'popup',
                title     : $t('Confirm Coupon Generation'),
                modalClass: 'ddg-coupon-job-confirmation-modal',
                buttons   : [
                    {
                        text : $t('Cancel'),
                        class: 'action-secondary',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text : $t('Generate'),
                        class: 'action-primary',
                        click: function () {
                            this.closeModal();
                            self._submitForm();
                        }
                    }
                ]
            }, $container);
        },

        /**
         * Calculate total batches based on audienceMeta contacts and batch size.
         *
         * @returns {number}
         */
        getTotalBatches: function () {
            const contacts = this.data() && this.data().audienceMeta
                ? parseInt(this.data().audienceMeta.contacts, 10)
                : 0;
            return contacts > 0 && this.batchSize > 0
                ? Math.ceil(contacts / this.batchSize)
                : 0;
        },

        /**
         * Get the audience type with the first letter capitalized.
         *
         * @returns {string}
         */
        getAudienceType: function () {
            const audienceType = this.data() && this.data().audienceMeta.audienceType;
            if (!audienceType) {
                return '';
            }

            return audienceType.charAt(0).toUpperCase() + audienceType.slice(1);
        },

        /**
         * Resolve coupon format label for display.
         *
         * @returns {string}
         */
        getCodeFormatLabel: function () {
            const format = this.data().code_format;

            if (format === 'num') {
                return 'numerical';
            }
            if (format === 'alpha') {
                return 'alphabetical';
            }
            return 'alphanumerical';
        },

        /**
         * Build a sample full coupon preview including prefix and suffix.
         *
         * @returns {string}
         */
        getCouponPreview: function () {
            const prefix = this.data().code_prefix || 'DOT-';
            const suffix = this.data().code_suffix || '';
            return prefix + this.getGeneratedCodePreview() + suffix;
        },

        /**
         * Build a sample generated code portion based on format, length and dash interval.
         *
         * @returns {string}
         */
        getGeneratedCodePreview: function () {
            const length = this.getCodeLength();
            const dashEvery = this.getCodeDashEvery();
            const character = this.getPlaceholderCharacter();
            const rawCode = character.repeat(length);

            if (dashEvery <= 0) {
                return rawCode;
            }

            let code = '';
            for (let i = 0; i < length; i++) {
                let nextCharacter = rawCode.charAt(i);
                if (i !== 0 && (i % dashEvery) === 0) {
                    nextCharacter = this.codeSeparator + nextCharacter;
                }
                code += nextCharacter;
            }
            return code;
        },

        /**
         * Resolve a placeholder character based on coupon format.
         *
         * @returns {string}
         */
        getPlaceholderCharacter: function () {
            const format = this.data().code_format;
            if (format === 'num') {
                return '9';
            }
            return 'X';
        },

        /**
         * Format the expires_at date for display as DD/MM/YYYY.
         * The underlying value (MM/DD/YYYY) is left unchanged.
         *
         * @returns {string}
         */
        getFormattedExpiryDate: function () {
            const raw = this.data().expires_at;
            if (!raw) {
                return '';
            }
            const parts = raw.split('/');
            if (parts.length === 3) {
                return parts[1] + '/' + parts[0] + '/' + parts[2];
            }
            return raw;
        },

        /**
         * Resolve configured coupon code length.
         *
         * @returns {number}
         */
        getCodeLength: function () {
            const value = parseInt(this.data().code_length, 10);
            return Number.isFinite(value) && value > 0 ? value : 9;
        },

        /**
         * Resolve configured dash interval.
         *
         * @returns {number}
         */
        getCodeDashEvery: function () {
            const value = parseInt(this.data().code_dash, 10);
            return Number.isFinite(value) && value >= 0 ? value : 3;
        },

        /**
         * Called by the form component to populate observables and open the modal.
         *
         * @param {Object} sourceData     - this.source.get('data') from the form
         * @param {Object} formComponent  - the calling form instance
         */
        open: function (sourceData, formComponent) {
            this._formComponent = formComponent;
            this.data(Object.assign({}, this.data(), sourceData, {
                audienceMeta: sourceMeta.get('couponJob_audienceMeta'),
                salesRuleMeta: sourceMeta.get('couponJob_salesRuleMeta')
            }));
            if (this._$modal) {
                this._$modal.modal('openModal');
            }
        },

        /**
         * Trigger the actual UI Form submit (validation already done by the form).
         */
        _submitForm: function () {
            if (this._formComponent && typeof this._formComponent.confirmedSubmit === 'function') {
                this._formComponent.confirmedSubmit();
            } else {
                console.error('No form component reference available to submit.');
            }
        }

    });
});
