const {ApiService} = Shopware.Classes;

class CrehlerPayuPaymentLinkApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'payu') {
        super(httpClient, loginService, apiEndpoint);
    }

    sendPaymentLink(orderId) {
        return this.httpClient
            .post(
                `crehler/${this.getApiBasePath()}/payment-link`,
                {
                    orderId: orderId
                },
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    sendSurchargeLink(orderId) {
        return this.httpClient
            .post(
                `crehler/${this.getApiBasePath()}/surcharge-link`,
                {
                    orderId: orderId
                },
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default CrehlerPayuPaymentLinkApiService;
