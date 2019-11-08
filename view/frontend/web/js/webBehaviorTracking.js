define(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * Web Behavior Script Implementation
     // * @param {String} x
     */
    return function (x) {
        var dm_insight_id = x;
        (function(w,d,u,t,o,c){w['dmtrackingobjectname']=o;c=d.createElement(t);c.async=1;c.src=u;t=d.getElementsByTagName
        (t)[0];t.parentNode.insertBefore(c,t);w[o]=w[o]||function(){(w[o].q=w[o].q||[]).push(arguments);};w[o]('track');
        })(window, document, '//static.trackedweb.net/js/_dmptv4.js', 'script', 'dmPt');
    };
});

