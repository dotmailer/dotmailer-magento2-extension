require(['jquery',
    'jquery/ui',
    'domReady!',
    'mage/translate'], function ($) {
    'use strict';

    /**
     * @param {Object} element
     */
    function removeTooltip(element) {
        element.css('position', '');
        $('.ddg-tooltip').remove();
    }

    /**
     * @param {String} toolTipText
     * @param {Object} element
     */
    function addTooltip(toolTipText, element) {
        if ($(this).prop('disabled')) {
            return;
        }
        let $toolTip = $('<div class="ddg-tooltip">' + toolTipText + '</div>');

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

    $(document).on('click', '.ddg-dynamic-content', function () {
        if ($(this).val() === '') {
            return;
        }

        removeTooltip($(this));
        navigator
            .clipboard
            .writeText($(this).val())
            .then(() => addTooltip(
                $.mage.__('Copied!'),
                $(this)
            ))
            .finally(() => {
                setTimeout(function () {
                    removeTooltip($(this));
                }.bind(this), 850);
            });
    });

    $(document).on('mouseenter',
        '.ddg-dynamic-content',
        function () {
            if ($(this).prop('disabled')) {
                return;
            }
            if ($(this).val() === '') {
                return;
            }

            let toolTipText = $.mage.__('Click to copy URL');

            addTooltip(toolTipText, $(this));
        });

    $(document).on('mouseleave', '.ddg-dynamic-content', function () {
        removeTooltip($(this));
    });
});

