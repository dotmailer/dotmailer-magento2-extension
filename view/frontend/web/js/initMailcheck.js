define(['jquery', 'mailcheck'], function ($, Mailcheck) {
    'use strict';

    /**
     * Initialise
     */
    function init() {
        // Go through all input type email fields
        $(document).on('blur', 'input[type=email]', function () {
            // Observe onblur event on element
            var element = this;

            Mailcheck.run({
                email: $(element).val(),

                /**
                 * @param {Object} suggestion
                 */
                suggested: function (suggestion) {
                    // Ensure the suggestion text can be translated.
                    var suggestionText = $.mage.__('Did you mean'),
                        suggestionHtml = '<div class=\'mailcheck-advice\'>' + suggestionText +
                        ' <a href=\'#\' class=\'suggested-domain\' data-email=\'' + suggestion.full + '\'>' +
                        suggestion.address + '@<strong>' + suggestion.domain + '</strong></a>?</div>';

                    // Remove any already existed suggestions
                    $('.mailcheck-advice').each(function (index, adviceElement) {
                        adviceElement.remove();
                    });

                    // Insert suggestion html after input field
                    $(element).after(suggestionHtml);
                },

                /**
                 * Remove suggestion
                 */
                empty: function () {
                    // If empty than field than remove suggestion
                    if ($(element).next('.mailcheck-advice')) {
                        $(element).next('.mailcheck-advice').remove();
                    }
                }
            });
        });
        //Bind onclick event to suggestion
        $(document).on('click', 'a.suggested-domain', function () {
            var el = $('a.suggested-domain'),
                input = el.parent().prev('input[type=email]');

            input.val(el.attr('data-email'));
            // remove mailcheck element
            el.closest('.mailcheck-advice').remove();
            // Prevent default action
            return false;
        });
    }

    /**
     * export/return mailcheck initialization
     */
    return function (initMailcheck) {
        if (initMailcheck.isEnabled) {
            init();
        }
    };
});
