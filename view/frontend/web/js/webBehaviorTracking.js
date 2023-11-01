define([
    'Magento_Catalog/js/product/storage/storage-service',
    'jquery'
], function (storage, $) {
    'use strict';

    return {
        identifiersConfig: {
            namespace: 'product_data_storage'
        },

        productStorageConfig: {
            namespace: 'product_data_storage',
            customerDataProvider: 'product_data_storage',
            className: 'DataStorage'
        },

        /**
         *
         */
        initIdsStorage: function () {
            storage.onStorageInit(this.identifiersConfig.namespace, this.idsStorageHandler.bind(this));

            return this;
        },

        /**
         * @param {Object} idsStorage
         */
        idsStorageHandler: function (idsStorage) {
            this.productStorage = storage.createStorage(this.productStorageConfig);
            this.productStorage.data.subscribe(this.dataCollectionHandler.bind(this));

            return this;
        },

        /**
         * @param {Array} data
         */
        dataCollectionHandler: function (data) {
            let productData;
            let productId;

            productId = parseInt(
                $('[name=product]').val()
            );

            productData = data[productId];

            if (productData != null) {
                var specialPriceBeforeTax = productData.price_info.extension_attributes.tax_adjustments.final_price,
                    specialPriceAfterTax = Math.round(productData.price_info.final_price * 100) / 100,
                    regularPriceBeforeTax = productData.price_info.extension_attributes.tax_adjustments.regular_price,
                    regularPriceAfterTax = Math.round(productData.price_info.regular_price * 100) / 100,
                    hasDiscountedPrice = specialPriceBeforeTax < regularPriceBeforeTax,
                    trackingData = {
                        product_name: productData.name || '',
                        product_url: productData.url || '',
                        product_currency: productData.currency_code || '',
                        product_status: parseInt(productData.is_salable) === 1 ? 'In stock' : 'Out of stock',
                        product_price: regularPriceBeforeTax || 0,
                        product_price_incl_tax: regularPriceAfterTax || 0,
                        product_specialPrice: hasDiscountedPrice ? specialPriceBeforeTax : 0,
                        product_specialPrice_incl_tax: hasDiscountedPrice ? specialPriceAfterTax : 0,
                        product_sku: productData.extension_attributes.ddg_sku || '',
                        product_brand: productData.extension_attributes.ddg_brand || '',
                        product_categories: (productData.extension_attributes.ddg_categories || []).join(','),
                        product_image_path: productData.extension_attributes.ddg_image || '',
                        product_description: productData.extension_attributes.ddg_description || ''
                };

                this.wbtTrack(trackingData);
            }
        },

        /**
         * @param {String} id
         * @param {String} subdomain
         * @param {String} region
         */
        initWbt: function (id, subdomain = 'static', region = 'r1-') {
            var scriptPath = '//' +
                (subdomain === 'static' ? subdomain : region + subdomain) +
                '.trackedweb.net/js/_dmptv4.js';

            window.dm_insight_id = id;

            (function (w, d, u, t, o, c) {
                w['dmtrackingobjectname'] = o; w['dmtrackingdomain'] = subdomain + '.trackedweb.net'; c = d.createElement(t); c.async = 1; c.src = u; t = d.getElementsByTagName
                (t)[0]; t.parentNode.insertBefore(c,t); w[o] = w[o] || function () {
                    (w[o].q = w[o].q || []).push(arguments);
                };
            })(window, document, scriptPath, 'script', 'dmPt');

            return this;
        },

        /**
         * @param {Array} data
         */
        wbtTrack: function (data) {
            window.dmPt('track', data || {});
        },

        /**
         * @param {Object} settings
         * @constructor
         */
        'Dotdigitalgroup_Email/js/webBehaviorTracking': function (settings) {
            var wbt = this.initWbt(settings.id, settings.subdomain, settings.region),
                body = document.getElementsByTagName('body')[0],
                search = document.getElementById('search') ?
                    document.getElementById('search') :
                    document.getElementsByName('q').length ? document.getElementsByName('q')[0] : null;

            if (body.classList.contains('catalogsearch-result-index')) {
                if (search && search.hasAttribute('value')) {
                    this.wbtTrack({
                        'searched_term': search.getAttribute('value')
                    });
                }
            } else if (body.classList.contains('catalog-product-view')) {
                wbt.initIdsStorage();
            } else {
                this.wbtTrack();
            }
        }
    };
});
