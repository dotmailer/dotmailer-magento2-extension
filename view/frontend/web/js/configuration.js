(() => {
    /**
     * @type {{currencyCode: string, locale: string, storeCode: string}}
     */
    const config = JSON.parse(document.getElementById('dotdigital-configuration-config').textContent);

    const applyConfiguration = (cart) => {
        window.ddg.configuration({
            site: {
                currency: config.currencyCode,
                language: config.locale
            },
            personalization: {
                siteBrand: config.storeCode,
                sessionId: cart ? cart.masked_quote_id : undefined
            }
        });
    };

    // A visitor without private content cannot yet have a storefront cart.
    if (!document.cookie.match(/(?:^|;\s*)private_content_version=([^;]+)/)) {
        applyConfiguration(null);
    }

    require(['Magento_Customer/js/customer-data'], function(customerData) {
        const cart = customerData.get('cart');
        const configureCart = (data) => {
            if (data.masked_quote_id || Number(data.summary_count) === 0) {
                applyConfiguration(data);
            }
        };

        customerData.getInitCustomerData().done(function () {
            cart.subscribe(configureCart);
            if (!customerData.getExpiredSectionNames().includes('cart')) {
                configureCart(cart());
            }
        });
    });
})();
