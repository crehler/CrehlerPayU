import HttpClient from 'src/service/http-client.service';

export default class BlikApiService {
    constructor() {
        this._httpClient = new HttpClient();
    }

    createOrder(data, callback) {
        this._httpClient.post(window.router['payu.blik-without-redirect-payment.create-order'], data , callback);
    }

    checkPaymentState(orderId, callback) {
        this._httpClient.post(
            window.router['payu.blik-without-redirect-payment.check-payment-state'],
            JSON.stringify({ orderId: orderId }),
            callback
        );
    }
}
