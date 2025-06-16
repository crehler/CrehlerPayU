import template from './sw-order-detail-details.html.twig';

const {Component} = Shopware;

Component.override('sw-order-detail-details', {
    template,

    computed: {
        paymentMethod() {
            return this.order.transactions.last().paymentMethod
        },
        showPayUCard() {
            return this.paymentMethod.handlerIdentifier.includes('PayU');
        },
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
        transaction() {
            return this.order.transactions.last();
        },
        orderTransactionListColumns() {
            return [
                {
                    property: 'stateMachineState.translated.name',
                    label: 'crehler-payu.order.detail.transactionList.statusColumn',
                }, {
                    property: 'amount.totalPrice',
                    label: 'crehler-payu.order.detail.transactionList.priceColumn',
                }, {
                    property: 'paymentMethod.translated.name',
                    label: 'crehler-payu.order.detail.transactionList.paymentMethodNameColumn',
                }, {
                    property: 'createdAt',
                    label: 'crehler-payu.order.detail.transactionList.createdAtColumn',
                }
            ];
        }
    }
});
