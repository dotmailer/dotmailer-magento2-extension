define(['domReady!'], function () {
    'use strict';

    return function (config) {
        var iframe = document.createElement('iframe');
        iframe.id = 'ddg-iframe';
        iframe.src = config.iframeSrc;

        document.getElementById('container').appendChild(iframe);

        /**
         *
         */
        function sizeIframe() {
            var body = document.body,
                html = document.documentElement,
                height = Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight);
            iframe.style.height = height + 'px';
        }

        window.onresize = function () {
            sizeIframe();
        };
        sizeIframe();
    }
});
