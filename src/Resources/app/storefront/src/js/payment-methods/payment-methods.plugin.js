import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class PaymentMethodsPlugin extends Plugin {
    static options = {
        paymentMethodId: null,
        paymentMethodsEndpoint: null,
        lastPayuPaymentMethodSelected: false,
        lastPayuPaymentMethod: null,
        selectedPaymentMethodId: null,
        cartTotalPrice: 0
    };

    init() {
        this._httpClient = new HttpClient();
        this.container = this.el.closest('.payment-methods');

        if (
            this.options.selectedPaymentMethodId === window.localStorage.getItem('payuPaymentMethodId') &&
            window.localStorage.getItem('payuActiveMethod') !== null
        ) {
            this.activePayuMethod = window.localStorage.getItem('payuActiveMethod');
        } else {
            this.activePayuMethod = this.options.lastPayuPaymentMethodSelected ? this.options.lastPayuPaymentMethod : null;
        }
        this.fetchPaymentMethods();
    }

    fetchPaymentMethods() {
        this._httpClient.post(
            this.options.paymentMethodsEndpoint,
            JSON.stringify({
                'paymentMethodId': this.options.paymentMethodId
            }),
            (response) => {
                const payUMethods = JSON.parse(response);
                Object.values(payUMethods).forEach((method) => {
                    if (
                        this.activePayuMethod === null &&
                        this.options.selectedPaymentMethodId === this.options.paymentMethodId
                    ) {
                        this.activePayuMethod = method.value;
                    }
                    if (method.value === this.activePayuMethod) {
                        this.renderPaymentMethods(method, this.container, 'afterbegin');
                    } else {
                        this.renderPaymentMethods(method);
                    }
                    this.registerEvents();
                });
            });
    }

    registerEvents() {
        const paymentMethodInputs = this.container.querySelectorAll('.payment-method:not(.payu-payment-method) input');
        paymentMethodInputs.forEach((input) => {
            input.addEventListener('change', (el) => {
                window.localStorage.removeItem('payuActiveMethod');
                window.localStorage.removeItem('payuPaymentMethodId');
            });
        });
    }

    renderPaymentMethods(method, container = this.el, position = 'afterend') {
        if (this.isMethodNotAvailable(method)) {
            console.warn(`Payment method ${method.name} is not available for this cart total price (min price = ${method.minAmount}, max price = ${method.maxAmount}) or is disabled.`);
            return;
        }
        const isSelected = method.value === this.activePayuMethod;
        container.insertAdjacentHTML(position, `
        <div class="payment-method payu-payment-method payu-payment-method-${method.value}">
            <div class="payment-form-group form-group">
                <div class="form-check payment-method-radio">
                    <input 
                            type="radio" id="PayuPaymentMethod${method.value}" 
                            name="paymentMethodId"
                            value="${this.options.paymentMethodId}" class="form-check-input payment-method-input"
                            ${isSelected ? 'checked' : ''}
                            />
        
                    <label class="form-check-label payment-method-label"
                           for="PayuPaymentMethod${method.value}">
                        <img src="${method.brandImageUrl}" 
                        class="payment-method-image" 
                        alt="${method.name}" 
                        title="${method.name}" 
                        loading="eager"
                        >
                        <div class="payment-method-description">
                            <strong>${method.name}</strong>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        `);
        if (isSelected) {
            let value = method.value;
            if (value.includes('c_')) {
                value = 'c';
            }
            container.insertAdjacentHTML(position, `
            <input 
                type="hidden" 
                required="required" 
                form="confirmOrderForm"
                name="payuPaymentMethod"
                value="${value}"
            >
            `);
        }
        const el = this.container.querySelector(`.payu-payment-method-${method.value} input`);
        el.addEventListener('change', this.savePaymentMethod.bind(this, method.value));
    }

    savePaymentMethod(value) {
        window.localStorage.setItem('payuActiveMethod', value);
        window.localStorage.setItem('payuPaymentMethodId', this.options.paymentMethodId);
    }

    isMethodNotAvailable(method) {
        return this.options.cartTotalPrice !== null &&
            (
                this.options.cartTotalPrice < method.minAmount ||
                this.options.cartTotalPrice > method.maxAmount ||
                method.status !== "ENABLED"
            )
    }
}
