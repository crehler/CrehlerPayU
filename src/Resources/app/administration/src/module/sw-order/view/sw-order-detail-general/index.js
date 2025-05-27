import template from './sw-order-detail-general.html.twig';
const { Component } = Shopware;

Component.override('sw-order-detail-general', {
    template,

    computed: {
        paymentMethod() {
            return this.order.transactions.last().paymentMethod
        },
        showPayUCard() {
            return this.paymentMethod.handlerIdentifier.includes('PayU');
        },

    }
});
