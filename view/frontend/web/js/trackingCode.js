/**
 * This module defines a custom UI component for the Dotdigital dmpt tracking code.
 */
define([
    'uiComponent',
], function (Component) {
    'use strict';
    /**
     * The custom UI component.
     *
     * @class
     * @extends uiComponent
     */
    return Component.extend({
        /**
         * Initializes the component.
         *
         * @param {Object} path - The path to the remote tracking script.
         */
        initialize: function (path) {
            this._super();
            require.config({
                paths: {
                    'dmpt': path
                }
            });
            require(['dmpt']);
            return this;
        }
    });
});
