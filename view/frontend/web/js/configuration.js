require(['Magento_Customer/js/customer-data'], function(customerData) {
    /**
     * @type {{currencyCode: string, locale: string, storeCode: string}}
     */
    const config = JSON.parse(document.getElementById('dotdigital-configuration-config').textContent);

    customerData.getInitCustomerData().done(function () {
        const obs = customerData.get('cart');
        const cart = obs();
        window.ddg.configuration({
            site: {
                currency: config.currencyCode,
                language: config.locale
            },
            personalization: {
                siteBrand: config.storeCode,
                sessionId: cart.masked_quote_id
            }
        });
    });
});
