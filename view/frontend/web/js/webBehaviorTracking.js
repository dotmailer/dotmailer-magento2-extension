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

        initIdsStorage: function () {
            storage.onStorageInit(this.identifiersConfig.namespace, this.idsStorageHandler.bind(this));
            return this;
        },

        idsStorageHandler: function (idsStorage) {
            this.productStorage = storage.createStorage(this.productStorageConfig);
            this.productStorage.data.subscribe(this.dataCollectionHandler.bind(this));
            return this;
        },

        dataCollectionHandler: function (data) {
            let productData;
            let productId;

            productId = parseInt(
                document.querySelector('[data-product-id]')
                    .getAttribute('data-product-id')
            );

            if (typeof data[productId] === 'undefined') {
                productId = parseInt(
                    $('[name=product]').val()
                );
            }

            productData = data[productId];

            if (productData != null) {
                var trackingData = {
                    product_name: productData.name || '',
                    product_url: productData.url || '',
                    product_currency: productData.currency_code || '',
                    product_status: parseInt(productData.is_salable) === 1 ? 'In stock' : 'Out of stock',
                    product_price: productData.price_info.final_price || 0,
                    product_specialPrice: productData.price_info.special_price || 0,
                    product_sku: productData.extension_attributes.ddg_sku || '',
                    product_brand: productData.extension_attributes.ddg_brand || '',
                    product_categories: (productData.extension_attributes.ddg_categories || []).join(','),
                    product_image_path: productData.extension_attributes.ddg_image || '',
                    product_description: productData.extension_attributes.ddg_description || ''
                };

                this.wbtTrack(trackingData);
            }
        },

        initWbt: function (id) {
            window.dm_insight_id = id;

            (function(w,d,u,t,o,c){w['dmtrackingobjectname']=o;c=d.createElement(t);c.async=1;c.src=u;t=d.getElementsByTagName
            (t)[0];t.parentNode.insertBefore(c,t);w[o]=w[o]||function(){(w[o].q=w[o].q||[]).push(arguments);};
            })(window, document, '//static.trackedweb.net/js/_dmptv4.js', 'script', 'dmPt');

            return this;
        },

        wbtTrack: function (data) {
            window.dmPt('track', data || {});
        },

        /**
         * @param settings
         * @constructor
         */
        'Dotdigitalgroup_Email/js/webBehaviorTracking': function (settings) {
            this.initWbt(settings.id)
                .initIdsStorage();

            var body = document.getElementsByTagName('body')[0];

            if (body.classList.contains('catalogsearch-result-index')) {
                var search = document.getElementById('search');
                this.wbtTrack({
                    'searched_term' : search.getAttribute('value')
                });
            } else if (!body.classList.contains('catalog-product-view')) {
                this.wbtTrack();
            }
        }
    };
});
