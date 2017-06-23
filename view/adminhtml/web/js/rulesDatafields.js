define(['prototype', 'domReady!'], function () {
    'use strict';

    /**
     * Do update for condition
     * @param {Object} item
     * @param {String} url
     */
    function doUpdateForCondition(item, url) {
        var attribute = item.up(1).down(),
            attributeValue = attribute.down().value,
            value = item.up().next(),
            valueName = value.down().readAttribute('name'),
            condValue = item.value;

        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                attributeValue: attributeValue,
                value: valueName,
                condValue: condValue
            },

            /**
             * @param {Object} transport
             */
            onSuccess: function (transport) {
                var json = transport.responseJSON;

                value.update(json.cvalue);
            }
        });
    }

    /**
     * @param {Object} item
     * @param {String} url
     * @param {String} valueAjaxUrl
     */
    function doUpdate(item, url, valueAjaxUrl) {
        var cond = item.up(1).down().next(),
            condName = cond.down().readAttribute('name'),
            value = item.up(1).down().next(1),
            valueName = value.down().readAttribute('name'),
            attribute = item.value;

        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                attribute: attribute,
                condition: condName,
                value: valueName
            },

            /**
             * @param {Object} transport
             */
            onSuccess: function (transport) {
                var json = transport.responseJSON;

                cond.update(json.condition);
                value.update(json.cvalue);

                $$('.admin__control-table tr td select').each(function (itemToObserve) {
                    Event.observe(itemToObserve, 'change', function () {
                        if (itemToObserve.readAttribute('title') === 'conditions') {
                            doUpdateForCondition(itemToObserve, valueAjaxUrl);
                        }
                    });
                });
            }
        });
    }

    /**
     * Do update with values
     * @param {Object} item
     * @param {String} url
     * @param {String} valueAjaxUrl
     */
    function doUpdateWithValues(item, url, valueAjaxUrl) {
        var arrayKey = item.up(1).readAttribute('id'),
            cond = item.up(1).down().next(),
            condName = cond.down().readAttribute('name'),
            value = item.up(1).down().next(1),
            valueName = value.down().readAttribute('name'),
            attribute = item.value,
            ruleId = $('rule_id').value;

        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                attribute: attribute,
                condition: condName,
                value: valueName,
                arraykey: arrayKey,
                ruleid: ruleId
            },

            /**
             * @param {Object} transport
             */
            onSuccess: function (transport) {
                var json = transport.responseJSON;

                cond.update(json.condition);
                value.update(json.cvalue);

                $$('.admin__control-table tr td select').each(function (itemToObserve) {
                    Event.observe(itemToObserve, 'change', function () {
                        if (itemToObserve.readAttribute('title') === 'conditions') {
                            doUpdateForCondition(itemToObserve, valueAjaxUrl);
                        }
                    });
                });
            }
        });
    }

    /**
     * Observe on click add new condition
     * @param {String} ajaxUrl
     * @param {String} valueAjaxUrl
     */
    function observeOnClickAddNewCondition(ajaxUrl, valueAjaxUrl) {
        $$('.admin__control-table tr td:first-child select').each(function (item) {
            Event.observe(item, 'change', function () {
                doUpdate(item, ajaxUrl, valueAjaxUrl);
            });
        });
        $$('.admin__control-table tr td select').each(function (item) {
            Event.observe(item, 'change', function () {
                if (item.readAttribute('title') === 'conditions') {
                    doUpdateForCondition(item, valueAjaxUrl);
                }
            });
        });
    }

    /**
     * init
     * @param {String} ajaxUrl
     * @param {String} selectAjaxUrl
     * @param {String} valueAjaxUrl
     */
    function init(ajaxUrl, selectAjaxUrl, valueAjaxUrl) {
        $$('.admin__control-table tr td:first-child select').each(function (item) {
            doUpdateWithValues(item, selectAjaxUrl, valueAjaxUrl);
        });

        $$('.admin__control-table tr td:first-child select').each(function (item) {
            Event.observe(item, 'change', function () {
                doUpdate(item, ajaxUrl, valueAjaxUrl);
            });
        });

        $$('.admin__control-table button.action-add').each(function (item) {
            Event.observe(item, 'click', function () {
                observeOnClickAddNewCondition(ajaxUrl, valueAjaxUrl);
            });
        });
    }

    /**
     * export/return
     * @param {Object} rules
     */
    return function (rules) {
        init(
            rules.ajaxUrl,
            rules.selectAjaxUrl,
            rules.valueAjaxUrl
        );
    };
});
