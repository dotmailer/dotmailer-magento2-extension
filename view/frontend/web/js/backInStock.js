define([], function () {
    'use strict';

    return {

        ddNotificationId: '',
        outOfStockVariants: [],
        productId: 0,
        productName: '',
        isSalable: false,

        collectData: function () {
            let ddProductData = {
                id: this.productId,
                title: this.productName,
                available: this.isSalable,
                variants: []
            };

            JSON.parse(this.outOfStockVariants).forEach(function (variant) {
                ddProductData.variants.push(variant);
            });

            this.setProductData(ddProductData);

        },

        setProductData: function (data) {
            window.ddProductData = new Promise((resolve) => {
                resolve(data);
            });

            require(['ddmbis']);
        },

        setNotificationId: function (id) {
            this.ddNotificationId = id;
        },

        setOutOfStockVariants: function (variants) {
            this.outOfStockVariants = variants;
        },

        setProductId: function (productId) {
            this.productId = productId;
        },

        setProductName: function (productName) {
            this.productName = productName;
        },

        setProductIsSalable: function (isSalable) {
            this.isSalable = Number(isSalable) === 1;
        },

        /**
         * @constructor
         */
        'Dotdigitalgroup_Email/js/backInStock': function (settings) {
            const body = document.getElementsByTagName('body')[0];

            if (body.classList.contains('catalog-product-view')) {
                this.setNotificationId(settings.id);
                this.setOutOfStockVariants(settings.variants);
                this.setProductId(settings.product_id);
                this.setProductName(settings.product_name);
                this.setProductIsSalable(settings.product_is_salable);
                this.collectData();
            }
        }
    };
});
