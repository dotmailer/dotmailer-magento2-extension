define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            defaultCodeLength: 9,
            defaultCodeDash: 3,
            defaultCodeSeparator: '-',
            defaultCodePrefix: 'DOT-'
        },

        /**
         * Logic to process the row data for the "jump" dump
         *
         * @param {{
         *   created_at: string,
         *   id: string,
         *   id_field_name: string,
         *   job_configuration: string,
         *   job_details: string,
         *   name: string,
         *   orig_data: null|Object,
         *   sales_rule_id: string,
         *   status: string,
         *   updated_at: string,
         *   _rowIndex: number
         * }} rows
         */
        processRows: function (rows) {
            this.configuration = JSON.parse(rows.job_configuration);
            this.configuration.coupon_preview = this.buildCouponPreview(this.configuration);
        },

        /**
         * Build coupon preview from saved job configuration.
         *
         * @param {Object} configuration
         * @returns {string}
         */
        buildCouponPreview: function (configuration) {
            const prefix = configuration.code_prefix || this.defaultCodePrefix;
            const suffix = configuration.code_suffix || '';
            const codeLength = this.toPositiveInt(configuration.code_length, this.defaultCodeLength);
            const codeDash = this.toNonNegativeInt(configuration.code_dash, this.defaultCodeDash);
            const placeholder = this.getPlaceholderCharacter(configuration.code_format);
            const rawGeneratedCode = placeholder.repeat(codeLength);

            if (codeDash <= 0) {
                return prefix + rawGeneratedCode + suffix;
            }

            let generatedCode = '';
            for (let i = 0; i < codeLength; i++) {
                let nextCharacter = rawGeneratedCode.charAt(i);
                if (i !== 0 && (i % codeDash) === 0) {
                    nextCharacter = this.defaultCodeSeparator + nextCharacter;
                }
                generatedCode += nextCharacter;
            }

            return prefix + generatedCode + suffix;
        },

        /**
         * Return preview character for the selected format.
         *
         * @param {string} codeFormat
         * @returns {string}
         */
        getPlaceholderCharacter: function (codeFormat) {
            if (codeFormat === 'num') {
                return '9';
            }

            return 'X';
        },

        /**
         * Format an expires_at date string (YYYY-MM-DD) for display as DD/MM/YYYY.
         *
         * @param {string} expiresAt
         * @returns {string}
         */
        formatExpiresAt: function (expiresAt) {
            if (!expiresAt) {
                return '';
            }
            const date = new Date(expiresAt + 'T00:00:00');
            if (isNaN(date.getTime())) {
                return expiresAt;
            }
            const dd = String(date.getDate()).padStart(2, '0');
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const yyyy = date.getFullYear();
            return dd + '/' + mm + '/' + yyyy;
        },

        /**
         * Parse positive integer with fallback.
         *
         * @param {*} value
         * @param {number} fallback
         * @returns {number}
         */
        toPositiveInt: function (value, fallback) {
            const parsedValue = parseInt(value, 10);
            return Number.isFinite(parsedValue) && parsedValue > 0 ? parsedValue : fallback;
        },

        /**
         * Parse non-negative integer with fallback.
         *
         * @param {*} value
         * @param {number} fallback
         * @returns {number}
         */
        toNonNegativeInt: function (value, fallback) {
            const parsedValue = parseInt(value, 10);
            return Number.isFinite(parsedValue) && parsedValue >= 0 ? parsedValue : fallback;
        }
    });
});
