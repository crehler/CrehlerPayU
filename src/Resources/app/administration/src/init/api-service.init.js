import CrehlerPayuApiService from '../core/service/api/crehler.payu.api.service';
import CrehlerPayuPaymentLinkApiService from "../core/service/api/crehler.payu.paymentLink.api.service";

const { Application } = Shopware;

Application.addServiceProvider('CrehlerPayuApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new CrehlerPayuApiService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('CrehlerPayuPaymentLinkApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new CrehlerPayuPaymentLinkApiService(initContainer.httpClient, container.loginService);
});
