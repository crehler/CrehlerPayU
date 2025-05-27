import template from './sw-order-detail-pay.html.twig';
import './sw-order-detail-pay.scss';

const { Component } = Shopware;

Component.register('sw-order-detail-payu', {
    template,
    inject: ['CrehlerPayuApiService'],
    props: {
        orderId: {
            type: String,
            required: true
        },
        isLoading: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            isPayU: true,
            method: []
        }
    },
    mounted() {
        this.refreshData()
    },
    methods: {
        refreshData() {
            let me = this;
            me.CrehlerPayuApiService.getDetails(me.orderId).then((response) => {
                me.isPayU = response.isPayU;
                me.method = response.method;
            });
        }
    },
    watch: {
        orderId () {
            this.refreshData();
        }
    }
});
