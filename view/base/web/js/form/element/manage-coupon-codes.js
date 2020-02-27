define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/components/fieldset',
    'Magento_Ui/js/lib/view/utils/async',
    'jquery',
], function (_, uiRegistry, fieldset, async, $) {
    'use strict';

    return fieldset.extend({
        /*eslint-disable no-unused-vars*/
        /**
         * Initialize element
         *
         * @returns {Abstract} Chainable
         */
        initialize: function (elems, position) {
            this._super();
            let obj = this;

            async.async('#ddg-sales-rule-form-tab-coupons', document.getElementById('container'), function (node) {
                uiRegistry
                    .get('sales_rule_form.sales_rule_form.rule_information.use_auto_generation')
                    .on('checked', function () {
                        obj.enableDisableFields();
                    });

                this.enableDisableFields();
            }.bind(this));

            // bind to window
            window.updateEdcCouponUrl = this.updateEdcCouponUrl;

            return this;
        },

        updateEdcCouponUrl: function () {
            var couponAttributes = [];
            var ddgEnabled = document.getElementById('ddg_coupons_enabled').getValue();
            var inputCouponUrl = document.getElementById('ddg_coupons_edc_url');
            var allowResend = parseInt(document.getElementById('ddg_coupons_allow_resend').getValue());
            var cancelSendField = document.getElementById('ddg_coupons_cancel_send');
            var couponUrl = inputCouponUrl.getAttribute('data-baseurl') + '/';
            var expiresAfter = document.getElementById('ddg_coupons_expires_after');
            var expireDays = expiresAfter.getValue();

            if (!parseInt(expireDays) || parseInt(expireDays) < 1) {
                expiresAfter.value = '';
            }

            if (allowResend === 0 && ddgEnabled) {
                cancelSendField.setAttribute('disabled', 'disabled');
            } else {
                cancelSendField.removeAttribute('disabled');
            }

            ['prefix', 'suffix', 'format', 'allow_resend', 'cancel_send', 'expires_after']
                .forEach(function (field) {
                    var inputField = document.getElementById('ddg_coupons_' + field);
                    if (!!inputField.getValue() && !inputField.hasAttribute('disabled')) {
                        couponAttributes.push('code_' + field + '/' + inputField.getValue());
                    }
                });

            couponAttributes.push(inputCouponUrl.getAttribute('data-email-merge-field'));
            inputCouponUrl.setValue(couponUrl + couponAttributes.join('/'));
        },

        /*eslint-enable no-unused-vars*/
        /*eslint-disable lines-around-comment*/

        /**
         * Enable/disable fields on Coupons tab
         */
        enableDisableFields: function () {
            var ddgEnabled = document.getElementById('ddg_coupons_enabled').getValue();
            var isExistingRule = !!document.querySelector('#ddg_coupons_rule_id').getValue();
            var disableAuto;
            var isUseAutoGenerationChecked = uiRegistry
                .get('sales_rule_form.sales_rule_form.rule_information.use_auto_generation')
                .checked();

            if (isExistingRule) {
                this.updateEdcCouponUrl();
            }

            disableAuto = (isUseAutoGenerationChecked && isExistingRule && ddgEnabled);
            this.disableFields(!disableAuto);
        },

        disableFields : function(toDisable) {
            var selector = '#ddg-sales-rule-form-tab-coupons input,' +
                '#ddg-sales-rule-form-tab-coupons select,' +
                '#ddg-sales-rule-form-tab-coupons textarea';

            _.each(
                document.querySelectorAll(selector),
                function (element) {
                    element.disabled = toDisable;
                }
            );

            if (uiRegistry.get('sales_rule_form.sales_rule_form.rule_information.to_date').value() !== '') {
                document.querySelector('#ddg-sales-rule-form-tab-coupons #ddg_coupons_expires_after').disabled = true;
            }
        }
    });
});
