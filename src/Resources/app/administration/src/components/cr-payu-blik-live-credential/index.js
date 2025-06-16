import template from './../cr-payu-sandbox-credential/cr-payu-sandbox-credential.html.twig';

const { Component } = Shopware;

Component.extend('cr-payu-blik-live-credential', 'cr-payu-blik-sandbox-credential', {
    template,
    inject: [
        'systemConfigApiService',
        'CrehlerPayuApiService'
    ],
    methods: {
        onTestCredentials() {
            let me = this,
                configComponent = this.$parent.$parent;
            me.isLoading = true;
            configComponent.isLoading = true;

            return this.systemConfigApiService.getValues('CrehlerPayU.config', null)
                .then(values => {
                    me.CrehlerPayuApiService.checkCredentials(values, false, true).then(checkResponse => {
                        me.showNotification(!!checkResponse);
                        me.isLoading = false;
                        configComponent.isLoading = false;
                    })
                });
        }
    }
});
