require(['jquery',
         'jquery/ui',
         'domReady!',
         "Magento_Ui/js/modal/modal",
         'mage/translate'], function ($) {
    'use strict';

    $('.ddg-dynamic-content').click(function(){
        $(this).select();

        setTimeout(function() {
            $('<div />').html($.mage.__('The URL has been copied to clipboard.'))
                .modal({
                    title: $.mage.__('URL Copied'),
                    autoOpen: true,
                    closed: function () {
                    },
                    buttons: [{
                        text: $.mage.__('Close'),
                        attr: {
                            'data-action': 'confirm'
                        },
                        'class': 'action-primary',
                    }]
                });
        }, 150);

        document.execCommand("copy");
    });

    $('.ddg-dynamic-content').hover( function() {
        var toolTipText = $.mage.__('Click To Copy The URL');

        $(this).attr('data-title', toolTipText);
        $(this).parent().append("<div class='ddg-tooltip'>" + toolTipText + "</div>");
        $(this).parent().css('position','relative');
    }
    ,function() {
        $(this).css('position','');
        $('.ddg-tooltip').remove();
    }
    );
});
