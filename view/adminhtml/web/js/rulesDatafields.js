define(['prototype'], function () {
    "use strict";

    function doUpdateForCondition(item, url){
        var attribute = item.up(1).down();
        var attributeValue = attribute.down().value;
        var value = item.up().next();
        var valueName = value.down().readAttribute('name');
        var condValue = item.value;

        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                attributeValue: attributeValue,
                value: valueName,
                condValue: condValue
            },
            onSuccess: function(transport){
                var json = transport.responseJSON;

                value.update(json.cvalue);
            }
        });
    }

    function doUpdate(item, url, valueAjaxUrl){
        var cond = item.up(1).down().next();
        var condName = cond.down().readAttribute('name');
        var value = item.up(1).down().next(1);
        var valueName = value.down().readAttribute('name');
        var attribute = item.value;

        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                attribute: attribute,
                condition: condName,
                value: valueName
            },
            onSuccess: function(transport){
                var json = transport.responseJSON;

                cond.update(json.condition);
                value.update(json.cvalue);

                $$('.admin__control-table tr td select').each(function(itemToObserve) {
                    Event.observe(itemToObserve,'change', function(){
                        if(itemToObserve.readAttribute('title') === 'conditions'){
                            doUpdateForCondition(itemToObserve, valueAjaxUrl);
                        }
                    });
                });
            }
        });
    }

    function doUpdateWithValues(item, url, valueAjaxUrl){
        var arrayKey = item.up(1).readAttribute('id');
        var cond = item.up(1).down().next();
        var condName = cond.down().readAttribute('name');
        var value = item.up(1).down().next(1);
        var valueName = value.down().readAttribute('name');
        var attribute = item.value;
        var ruleId = $('rule_id').value;

        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                attribute: attribute,
                condition: condName,
                value: valueName,
                arraykey: arrayKey,
                ruleid: ruleId
            },
            onSuccess: function(transport){
                var json = transport.responseJSON;

                cond.update(json.condition);
                value.update(json.cvalue);

                $$('.admin__control-table tr td select').each(function(itemToObserve) {
                    Event.observe(itemToObserve,'change', function(){
                        if(itemToObserve.readAttribute('title') === 'conditions'){
                            doUpdateForCondition(itemToObserve, valueAjaxUrl);
                        }
                    });
                });
            }
        });
    }

    function observeOnClickAddNewCondition(ajaxUrl, valueAjaxUrl) {
        $$('.admin__control-table tr td:first-child select').each(function(item) {
            Event.observe(item,'change', function(){
                doUpdate(item, ajaxUrl, valueAjaxUrl);
            });
        });
        $$('.admin__control-table tr td select').each(function(item) {
            Event.observe(item,'change', function(){
                if(item.readAttribute('title') === 'conditions'){
                    doUpdateForCondition(item, valueAjaxUrl);
                }
            });
        });
    }

    function init(ajaxUrl, selectAjaxUrl, valueAjaxUrl) {
        $$('.admin__control-table tr td:first-child select').each(function(item) {
            doUpdateWithValues(item, selectAjaxUrl, valueAjaxUrl);
        });

        $$('.admin__control-table tr td:first-child select').each(function(item) {
            Event.observe(item,'change', function(){
                doUpdate(item, ajaxUrl, valueAjaxUrl);
            });
        });

        $$('.admin__control-table button.action-add').each(function(item) {
            Event.observe(item,'click', function(){
                observeOnClickAddNewCondition(ajaxUrl, valueAjaxUrl);
            });
        });
    }

    /**
     * export/return
     */
    return function(rules)
    {
        init(
            rules.ajaxUrl,
            rules.selectAjaxUrl,
            rules.valueAjaxUrl
        );
    };
});