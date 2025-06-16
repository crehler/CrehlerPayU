const { ApiService } = Shopware.Classes;

class CrehlerPayuApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'payu') {
        super(httpClient, loginService,  apiEndpoint);
    }

    getDetails(orderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(`crehler/${this.getApiBasePath()}/detail/${orderId}`, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    checkCredentials(data, checkSandbox, isBlik) {
        data.checkSandbox = checkSandbox;
        data.isBlik = isBlik;

        return this.httpClient
            .post(
                `crehler/${this.getApiBasePath()}/check-credentials`,
                data,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    sendPostRequest(endpoint, data= {}){
        return this.httpClient
            .post(
                `crehler/${this.getApiBasePath()}/${endpoint}`,
                data,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default CrehlerPayuApiService;
