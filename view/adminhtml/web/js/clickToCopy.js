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
        var $toolTip = $('<div class="ddg-tooltip">' + toolTipText + '</div>');
        $toolTip.css({
            position: 'absolute',
            top: '-15px',
            backgroundColor: '#333',
            color: '#fff',
            padding: '5px',
            borderRadius: '5px'
        });

        element.attr('data-title', toolTipText);
        element.css('backgroundColor', '#fff')
            .parent()
            .css('position', 'relative')
            .append($toolTip);
    }

    $(document).on('click', '.ddg-dynamic-content', function() {
        if ($(this).val() == '') return;

        var toolTipText = $.mage.__('Copied!');

        $(this).select();
        removeTooltip($(this));
        addTooltip(toolTipText,$(this));

        setTimeout(function() {
            removeTooltip($(this));
        }.bind(this), 850);

        document.execCommand("copy");
    });

    $(document).on('mouseenter', '.ddg-dynamic-content', function() {
        if ($(this).val() == '') return;

        var toolTipText = $.mage.__('Click to copy URL');
        addTooltip(toolTipText,$(this));
    });
    $(document).on('mouseleave', '.ddg-dynamic-content', function() {
        removeTooltip($(this));
    });
});

