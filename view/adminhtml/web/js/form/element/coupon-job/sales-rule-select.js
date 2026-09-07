/**
 * Sales rule select element.
 *
 * Watch for changes to the sales rule select as part of the coupon job form and persist the selection in
 * session storage to allow access by the confirmation modal which is outside the form scope and cannot access the
 * select element directly.
 *
 * @see Dotdigitalgroup_Email/js/form/session-source-meta
 */
define([
    'Magento_Ui/js/form/element/select',
    'Dotdigitalgroup_Email/js/form/session-source-meta',
], function (Select, sourceMeta) {
    'use strict';

    return Select.extend({

        /**
         * Wire up the value subscription after the parent initialises.
         *
         * @return {*}
         */
        initialize: function () {
            this._super();
            this._prefixOptionLabels();

            this.value.subscribe(function (newValue) {
                this._writeSalesRuleMeta(newValue);
            }, this);

            return this;
        },

        /**
         * Prefix each option label with its ID: "{id} - {label}".
         *
         * @private
         */
        _prefixOptionLabels: function () {
            const indexed = this.indexedOptions || {};

            Object.keys(indexed).forEach(function (key) {
                const option = indexed[key];
                if (option && option.value && option.label) {
                    option.label = `${option.label} [ID:${option.value}]`;
                }
            });
        },

        /**
         * Find the matching option label and persist meta to the form source.
         *
         * @param {string} value
         * @private
         */
        _writeSalesRuleMeta: function (value) {
            if (!value) {
                sourceMeta.remove('couponJob_salesRuleMeta');
                return;
            }

            const options = this.indexedOptions || {};
            const match   = options[value];
            const label = match ? (match.label || String(value)) : String(value);

            sourceMeta.update('couponJob_salesRuleMeta', {
                id: String(value),
                label: label
            });
        }
    });
});
