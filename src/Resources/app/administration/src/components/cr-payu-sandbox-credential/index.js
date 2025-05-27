import template from './cr-payu-sandbox-credential.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cr-payu-sandbox-credential', {
    template,
    inject: [
        'CrehlerPayuApiService',
        'systemConfigApiService'
    ],
    mixins: [
        Mixin.getByName('notification')
    ],
    data() {
        return {
            isLoading: false
        }
    },
    methods: {
        onTestCredentials() {
            let me = this,
                configComponent = this.$parent.$parent;
            me.isLoading = true;
            configComponent.isLoading = true;

            return this.systemConfigApiService.getValues('CrehlerPayU.config', null)
                .then(values => {
                    me.CrehlerPayuApiService.checkCredentials(values, true).then(checkResponse => {
                        me.showNotification(!!checkResponse);
                        me.isLoading = false;
                        configComponent.isLoading = false;
                    })
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        saveData(componentName) {
            let component = this.$parent;

            if (component.config.componentName !== componentName) {
                return;
            }

            let group = component.$parent;
            let configElement = group.$parent;

            return configElement.saveAll()
        },
        showNotification(result) {
            if (result) {
                this.createNotificationSuccess({
                    title: this.$tc('crehler-payu.config.successTitle'),
                    message: this.$tc('crehler-payu.config.successMessage'),
                    autoClose: false
                })
            } else {
                this.createNotificationError({
                    title: this.$tc('crehler-payu.config.errorTitle'),
                    message: this.$tc('crehler-payu.config.errorMessage'),
                    autoClose: false
                })
            }
        }
    }
});
