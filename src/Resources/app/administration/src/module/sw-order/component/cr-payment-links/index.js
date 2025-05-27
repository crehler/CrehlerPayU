import template from './cr-payment-links.html.twig';

const {Component} = Shopware;
const {Mixin} = Shopware;

Component.register('cr-payment-links', {
    template,

    props: {
        order: {
            type: Object,
            required: true,
        }
    },

    data() {
        return {
            isLoading: false,
            paymentLink: null,
            surchargeLink: null,
        }
    },

    inject: [
        'CrehlerPayuPaymentLinkApiService'
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    computed: {
        paymentLinkModalTitle() {
            if (this.paymentLink !== null) {
                return this.$tc('crehler-payu.paymentLink.surchargeButton');
            }
            if (this.surchargeLink !== null) {
                return this.$tc('crehler-payu.paymentLink.surchargeModalTitle');
            }
        },
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
        surcharge() {
            let transactionSum = 0.0;
            this.order.transactions.forEach(transaction => {
                if (
                    transaction.stateMachineState.technicalName === 'paid' ||
                    transaction.stateMachineState.technicalName === 'paid_partially' ||
                    transaction.stateMachineState.technicalName === 'reminded'
                ) {
                    transactionSum += transaction.amount.totalPrice;
                }
            });

            return transactionSum !== 0 && this.order.amountTotal > transactionSum ? this.order.amountTotal - transactionSum : 0;
        },
    },

    methods: {
        async sendPaymentLink() {
            this.isLoading = true;
            try {
                const resp = await this.CrehlerPayuPaymentLinkApiService.sendPaymentLink(this.order.id);
                if (resp.success === false) {
                    this.createNotificationError({
                        message: resp.message,
                    });
                    this.isLoading = false;
                    return;
                }
                this.createNotificationSuccess({
                    title: "Success",
                    message: this.$tc('crehler-payu.paymentLink.PaymentLinkSuccessMessage')
                });
                this.paymentLink = resp.paymentLink;
                this.isLoading = false;
            } catch (e) {
                this.createNotificationError({
                    message: e,
                });
                console.error(e);
                this.isLoading = false;
            }
            this.$emit('save-edits');
        },
        async sendSurchargeLink() {
            this.isLoading = true;
            try {
                const resp = await this.CrehlerPayuPaymentLinkApiService.sendSurchargeLink(this.order.id)
                if (resp.success === false) {
                    this.createNotificationError({
                        message: resp.message,
                    });
                    this.isLoading = false;
                    return;
                }
                this.createNotificationSuccess({
                    title: "Success",
                    message: this.$tc('crehler-payu.paymentLink.SurchargeSuccessMessage')
                });
                this.surchargeLink = resp.paymentLink;
                this.isLoading = false;
            } catch (e) {
                this.createNotificationError({
                    message: e,
                });
                console.error(e);
                this.isLoading = false;
            }
            this.$emit('save-edits');
        },
        onClose() {
            this.paymentLink = null;
            this.surchargeLink = null;
        }
    }
});
