const {Component} = Shopware;

Component.override('sw-order-list', {

    inject: [
        'stateStyleDataProviderService',
    ],

    methods: {
        getVariantFromPaymentState(order) {
            let technicalName = order.transactions.last().stateMachineState.technicalName;

            const style = this.stateStyleDataProviderService.getStyle('order_transaction.state', technicalName);

            return style.colorCode;
        },
        transaction(item) {
            return item.transactions.last();
        },
    }
});
