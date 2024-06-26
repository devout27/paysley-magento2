/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (quote, urlBuilder, storage, url, errorProcessor, customer, fullScreenLoader) {
        'use strict';

        /**
         * Filter template data.
         *
         * @param {Object|Array} data
         */
        var filterTemplateData = function (data) {
            return _.each(data, function (value, key, list) {
                if (_.isArray(value) || _.isObject(value)) {
                    list[key] = filterTemplateData(value);
                }

                if (key === '__disableTmpl') {
                    delete list[key];
                }
            });
        };

        return function (messageContainer, paymentData) {
            var serviceUrl,
                payload;

            paymentData = filterTemplateData(paymentData);
            payload = {
                cartId: quote.getQuoteId(),
                paymentMethod : paymentData
            };

            /** Checkout for guest and registered customer. */
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
                    quoteId: quote.getQuoteId()
                });
                payload.email = quote.guestEmail;
                payload.billingAddress = quote.billingAddress();
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
                payload.billingAddress = quote.billingAddress();
            }
            fullScreenLoader.startLoader();
            return storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).done(
                function () {
                    window.location.replace(url.build('paysley/payment'));
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);



