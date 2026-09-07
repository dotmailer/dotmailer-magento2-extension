define([
    'Magento_Ui/js/form/form',
    'Dotdigitalgroup_Email/js/form/session-source-meta',
    'uiRegistry',
    'mage/translate',
], function (Form, sourceMeta, registry, $t, messageList) {
    'use strict';

    return Form.extend({
        defaults: {
            confirmationModalName: 'ddg_coupon_job_confirmation_modal'
        },

        /**
         * Override save to validate first, then open the confirmation modal.
         *
         * @param {String} redirect
         * @param {Object} data
         */
        save: function (redirect, data) {

            this.validate();

            if (!this.additionalInvalid && !this.source.get('params.invalid')) {
                this.setAdditionalData(data);
                this._pendingRedirect = redirect;
                this._openConfirmationModal();
            } else {
                this.focusInvalid();
            }
        },

        /**
         * Open the confirmation modal if available, otherwise submit the form.
         *
         * @private
         */
        _openConfirmationModal: function () {
            var sourceData = this.source.get('data');
            var modalComponent = registry.get(this.confirmationModalName);
            if (modalComponent && typeof modalComponent.open === 'function') {
                modalComponent.open(sourceData, this);
            } else {
                this.submit(this._pendingRedirect);
            }
        },

        /**
         * Submit the form if the confirmation modal is confirmed.
         * Merges audienceMeta, salesRuleMeta and batchSize from session storage/modal into
         * the form source data so they are included in the POST payload.
         *
         * @returns {void}
         */
        confirmedSubmit: function () {
            const audienceMeta  = sourceMeta.get('couponJob_audienceMeta');
            const salesRuleMeta = sourceMeta.get('couponJob_salesRuleMeta');
            const modalComponent = registry.get(this.confirmationModalName);

            if (audienceMeta) {
                this.source.set('data.audienceMeta', audienceMeta);
            }

            if (salesRuleMeta) {
                this.source.set('data.salesRuleMeta', salesRuleMeta);
            }

            if (modalComponent && modalComponent.batchSize) {
                this.source.set('data.batch_size', modalComponent.batchSize);
            }

            sourceMeta.remove('couponJob_audienceMeta');
            sourceMeta.remove('couponJob_salesRuleMeta');

            this.submit(this._pendingRedirect);
        }
    });
});
