require(['jquery',
    'jquery/ui',
    'domReady!',
    'mage/translate'], function ($) {
    'use strict';

    /**
     * Removes The Tooltip
     * @param element
     */
    function removeTooltip(element)
    {
        element.css('position','');
        $('.ddg-tooltip').remove();
    }

    /**
     * Adds the tooltip
     * @param toolTipText
     * @param element
     */
    function addTooltip(toolTipText,element)
    {
        element.attr('data-title', toolTipText);
        element.parent().append("<div class='ddg-tooltip'>" + toolTipText + "</div>");
        element.parent().css('position','relative');
    }

    $('.ddg-disabled-button').hover(function() {
            var toolTipText = $.mage.__('Your API credentials are not set. Please make sure that you have a valid Engagement Cloud account.');

            $(this).attr('onClick', '#');
            addTooltip(toolTipText, $(this));
        }
        ,function() {
            removeTooltip($(this));
        }
    );
});
