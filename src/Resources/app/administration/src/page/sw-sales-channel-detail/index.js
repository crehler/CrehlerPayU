Shopware.Component.override('sw-sales-channel-detail', {
    inject: ['CrehlerPayuApiService'],
    methods: {
        onSave() {
            this.$super('onSave');

            const paymentMethodIds = this.salesChannel.paymentMethods.map((method) => method.id);

            this.CrehlerPayuApiService.sendPostRequest(
                'sales-channel-payment-configuration-notification',
                {
                    'paymentMethodIds': paymentMethodIds
                }
            ).then((response) => {
                if (response.error) {
                    this.createNotificationError({
                        title: this.$tc('crehler-payu.storeFrontForm.errorTitle'),
                        message: this.$tc('crehler-payu.storeFrontForm.errorMessage'),
                        autoClose: true
                    });
                }

                if (typeof response.sandbox !== 'undefined' && response.sandbox === true) {
                    this.createNotificationWarning({
                        title: this.$tc('crehler-payu.sadboxWarning.title'),
                        message: this.$tc('crehler-payu.sadboxWarning.message'),
                        autoClose: false
                    })
                }

                if (typeof response.credentials !== 'undefined' && response.credentials === false) {
                    this.createNotificationError({
                        title: this.$tc('crehler-payu.credentialsError.title'),
                        message: this.$tc('crehler-payu.credentialsError.message'),
                        autoClose: false
                    })
                }
            });
        }
    }
});
