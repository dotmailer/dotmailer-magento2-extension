/**
 * Dotdigitalgroup Email Session Storage Helper
 *
 * A simple utility to manage JSON data in session storage, providing get, update, and remove methods.
 * The update method merges new data with existing data to prevent overwriting unrelated values.
 */
define([], function () {
    'use strict';

    return {
        /**
         * Get and Parse JSON from session storage
         */
        get: function (key) {
            const data = sessionStorage.getItem(key);
            try {
                return data ? JSON.parse(data) : null;
            } catch (e) {
                console.error('Error parsing session data', e);
                return null;
            }
        },

        /**
         * Save or UPDATE data without losing existing values
         */
        update: function (key, newData) {
            let existingData = this.get(key) || {};
            const updatedData = Object.assign({}, existingData, newData);
            sessionStorage.setItem(key, JSON.stringify(updatedData));
        },

        remove: function (key) {
            sessionStorage.removeItem(key);
        }
    };
});
