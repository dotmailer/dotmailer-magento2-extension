require(['jquery', 'fancybox', 'domReady!'], function ($) {
    'use strict';

    var fb = $('.ddg-fancyBox');

    if (fb && fb !== null) {
        fb.fancybox({
            width: 508,
            height: 670,
            scrolling: 'no',
            hideOnOverlayClick: false,
            helpers: {
                overlay: {
                    closeClick: false
                }
            }
        });

        $(document).on('click', 'a.fancybox-close', function () {
            location.reload();
        });

        window.addEventListener('message', function (event) {
            if (event.origin !== 'https://magentosignup.dotmailer.com') {
                return;
            }

            if (event.data === 'close') {
                location.reload();
            }
        });
    }
});
