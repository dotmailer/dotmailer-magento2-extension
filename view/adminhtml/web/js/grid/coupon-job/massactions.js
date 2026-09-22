define([
    'underscore',
    'Magento_Ui/js/grid/massactions',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (_, Massactions, alert, $t) {
    'use strict';

    return Massactions.extend({

        defaults: {
            /**
             * Statuses from which a coupon job may safely be deleted.
             */
            terminalStatuses: ['complete', 'failed', 'cancelled'],

            /**
             * Actions that may only be applied to terminal jobs.
             */
            terminalOnlyActions: ['delete'],

            rows: [],

            imports: {
                rows: '${ $.provider }:data.items'
            }
        },

        /**
         * Block a terminal-only action when the selection contains a job that is
         * still running.
         *
         * The server-side controller repeats this check, which is what protects the
         * "Select All" case — when the grid is in exclude mode the client has no way
         * to enumerate every selected row, so the request is allowed through and the
         * controller reports how many rows it skipped.
         *
         * @param {String} actionIndex - Action identifier.
         * @returns {Object} Chainable.
         */
        applyAction: function (actionIndex) {
            if (this.isTerminalOnly(actionIndex) && this.hasRunningSelection()) {
                alert({
                    content: $t(
                        'Only completed, failed or cancelled jobs can be deleted.'
                        + ' Please deselect any pending or processing jobs.'
                    )
                });

                this.close();

                return this;
            }

            return this._super(actionIndex);
        },

        /**
         * Check whether an action may only be applied to terminal jobs.
         *
         * @param {String} actionIndex - Action identifier.
         * @returns {Boolean}
         */
        isTerminalOnly: function (actionIndex) {
            return _.contains(this.terminalOnlyActions, actionIndex);
        },

        /**
         * Check whether the current selection includes a job that is still running.
         *
         * @returns {Boolean}
         */
        hasRunningSelection: function () {
            var data = this.getSelections(),
                selected;

            if (!data || data.excludeMode) {
                return false;
            }

            selected = data.selected || [];

            if (!selected.length) {
                return false;
            }

            return _.some(selected, function (id) {
                var row = _.find(this.rows, function (item) {
                    return String(item.id) === String(id);
                });

                return !!row && !_.contains(this.terminalStatuses, row.status);
            }, this);
        }
    });
});
