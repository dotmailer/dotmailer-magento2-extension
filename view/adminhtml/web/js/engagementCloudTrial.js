define(['jquery', 'domReady!'], function ($) {
    'use strict';

    return function (config) {
        var eventMethod, eventMessage;

        if (window.addEventListener) {
            eventMethod = 'addEventListener';
            eventMessage = 'message';
        } else {
            eventMethod = 'attachEvent';
            eventMessage = 'onmessage';
        }

        window[eventMethod](eventMessage, function (e) {
            if (e.origin !== config.micrositeUrl) {
                return;
            }

            $.post({
                url: config.callback,
                data: {
                    origin: e.origin,
                    code: e.data.code,
                    apispaceid: e.data.apispaceid || null,
                    token: e.data.token || null,
                    apiendpoint: e.data.apiendpoint || null,
                    apiusername: e.data.apiusername || null,
                    apipassword: e.data.apipassword || null
                },
                success: function (data) {
                    document.getElementById('ddg-iframe')
                        .contentWindow
                        .postMessage({
                            'accepted': true
                        }, e.origin);
                }
            });
        }, true);
    };
});
