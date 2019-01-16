require(['jquery',
    'jquery/ui',
    'domReady!',
    'mage/translate'], function ($) {
    'use strict';
    
    function removeTooltip(element)
    {
        element.css('position','');
        $('.ddg-tooltip').remove();
    }

    function addTooltip(toolTipText,element)
    {
        element.attr('data-title', toolTipText);
        element.parent().append("<div class='ddg-tooltip'>" + toolTipText + "</div>");
        element.parent().css('position','relative');
    }

    $('.ddg-dynamic-content').click(function(){
        var toolTipText = $.mage.__('Copied!');

        $(this).select();
        removeTooltip($(this));
        addTooltip(toolTipText,$(this));
        setTimeout(function() {

            removeTooltip($(this));
        }, 850);
        document.execCommand("copy");
    });

    $('.ddg-dynamic-content').hover( function() {
            var toolTipText = $.mage.__('Click to copy URL');

            addTooltip(toolTipText,$(this));
        }
        ,function() {
            removeTooltip($(this));
        }
    );

});

