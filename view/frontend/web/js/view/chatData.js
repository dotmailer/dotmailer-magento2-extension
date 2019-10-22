define([
    'Magento_Customer/js/customer-data',
    'jquery',
    'mage/cookies'
], function (customerData, $) {
    'use strict';

    /**
     * Enable the chat widget
     *
     * @param chatData
     */
    function startChat(chatData) {
        var storageKey = chatData().cookieName;
        window.comapiConfig = {
            apiSpace: chatData().apiSpaceId,
            launchTimeout: 2000
        };

        (function (d, s, id) {
            var js, cjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) { return; }
            js = d.createElement(s); js.id = id;
            js.src = '//cdn.dnky.co/widget/bootstrap.js';
            cjs.parentNode.insertBefore(js, cjs);
        }(document, 'script', 'comapi-widget'));

        // listen for widget message events
        window.addEventListener("message", function (event) {
            if (event.data.type !== 'SetWidgetState') {
                return;
            }

            if (event.data.show === 'hidden') {
                // user has closed the chat
                sessionStorage.removeItem(storageKey);

            } else if (sessionStorage.getItem(storageKey) === null) {
                // Sync the Magento user id
                window.COMAPI_WIDGET_API.profile.getProfile()
                    .then(function (profile) {
                        $.ajax({
                            url: chatData().profileEndpoint,
                            type: "POST",
                            data: "profileId=" + profile.id,
                            success: function (result) {
                                // store profile ID in session to flag interaction
                                sessionStorage.setItem(storageKey, profile.id);
                                // store profile ID in cookie for server-side reference
                                $.cookie(storageKey, profile.id);
                            }
                        });
                    });
            }
        });
    }

    return function () {
        var sectionName = 'chatData';
        var chatData = customerData.get(sectionName);

        // check we have API space ID, that chat is enabled, and the API space ID was refreshed under 6 hours ago
        if (
            typeof chatData().apiSpaceId === 'undefined'
            || chatData().data_id < Math.floor((new Date().getTime() / 1000) - (60 * 60))
        ) {
            customerData.invalidate([sectionName]);
            customerData.reload([sectionName], true)
                .done(function () {
                    var chatData = customerData.get(sectionName);
                    if (chatData().isEnabled) {
                        startChat(chatData);
                    }
                });
        } else if (chatData().isEnabled) {
            startChat(chatData);
        }
    }
});
