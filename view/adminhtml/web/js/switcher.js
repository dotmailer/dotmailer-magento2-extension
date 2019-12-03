define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'domReady!'
], function (jQuery, confirm) {
    'use strict';

    /**
     * Init
     * @param {Int} useConfirm
     * @param {String} getContent
     * @param {Int} objVal
     * @param {Boolean} isIframe
     * @param {String} switchUrl
     */
    function init(useConfirm, getContent, objVal, isIframe, switchUrl) {
        var scopeSwitcherHandler;

        (function ($) {
            var $storesList = $('[data-role=stores-list]');

            $storesList.on('click', '[data-value]', function (event) {
                var val = $(event.target).data('value'),
                    role = $(event.target).data('role'),
                    switcher = $('[data-role=' + role + ']');

                event.preventDefault();

                if (!switcher.val() || val !== switcher.val()) {
                    switcher.val(val).trigger('change'); // Set the value & trigger event
                }
            });
        })(jQuery);

        /**
         * Switch scope
         * @param {Object} obj
         */
        function switchScope(obj) {
            var switcher = jQuery(obj),
                scopeId = switcher.val(),
                scopeParams = '',
                switcherParams;

            /**
             * Reload
             */
            function reload() {
                var url;

                if (!isIframe) {
                    url = switchUrl + scopeParams;
                    window.location.href = url;
                } else {
                    jQuery('#preview_selected_store').val(scopeId);
                    jQuery('#preview_form').submit();

                    jQuery('.store-switcher .dropdown-menu li a').each(function () {
                        var $this = jQuery(this);

                        if ($this.data('role') === 'store-view-id' && $this.data('value') === scopeId) {
                            jQuery('#store-change-button').html($this.text());
                        }
                    });

                    jQuery('#store-change-button').click();
                }
            }

            if (scopeId) {
                scopeParams = switcher.data('param') + '/' + scopeId + '/';
            }

            if (obj.switchParams) {
                scopeParams += obj.switchParams;
            }

            if (typeof scopeSwitcherHandler !== 'undefined') {
                switcherParams = {
                    scopeId: scopeId,
                    scopeParams: scopeParams,
                    useConfirm: useConfirm
                };
                scopeSwitcherHandler(switcherParams);
            } else if (useConfirm) {
                confirm({
                    content: getContent,
                    actions: {

                        /**
                         * Confirm
                         */
                        confirm: function () {
                            reload();
                        },

                        /**
                         * Cancel
                         */
                        cancel: function () {
                            obj.value = objVal;
                        }
                    }
                });
            } else {
                reload();
            }
        }

        window.scopeSwitcherHandler = scopeSwitcherHandler;
        window.switchScope = switchScope;
    }

    /**
     *
     * @param {Object} switcher
     */
    return function (switcher) {
        init(
            switcher.getUseConfirm,
            switcher.getContent,
            switcher.objVal,
            switcher.isUsingIframe,
            switcher.getSwitchUrl
        );
    };
});
