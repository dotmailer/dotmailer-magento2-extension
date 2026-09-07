define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict';

    return Column.extend({

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
            const job_details = JSON.parse(rows.job_details);
            this.report = job_details.report
        },

        /**
         * Logic for the specific column data
         *
         * @param value
         * @returns {string|string}
         */
        formatNumber: function (value) {
            return value ? Number(value).toLocaleString() : '0';
        },

        /**
         * Logic for the specific column data
         *
         * @param value
         * @param total
         * @returns {string}
         */
        formatPercentage: function (value, total) {
            if (!total) return '0';
            const raw = (value / total) * 100;
            if ((raw >= 99.95 && raw < 100) || (raw > 0 && raw <= 0.05)) {
                return raw.toFixed(2);
            }
            return raw.toFixed(1);
        },
    });
});
