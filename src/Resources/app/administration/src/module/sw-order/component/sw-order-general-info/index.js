const {Component} = Shopware;

Component.override('sw-order-general-info', {
    computed: {
        transaction() {
            return this.order.transactions.last();
        }
    }
});
