import BlikApiService from './service/blik-api.service';
import BlikCodeValidatorService from './service/blik-code-validator.service';
import BlikModalUtil from './util/blik-without-redirect-payment-method-modal.util';
import Plugin from 'src/plugin-system/plugin.class';
import TosValidatorService from './service/tos-validator.service';

export default class BlikWithoutRedirectPaymentMethodPlugin extends Plugin {
    static options = {
        blikDurationTimeout: 500,
        paymentMethodId: '',
        isInvalidClass: 'is-invalid',
        isSuccessClass: 'is--success',
        isHiddenClass: 'is--hidden',
        pseudoModalDialogSelector: '.modal-dialog',
        pseudoModalDialogCenteredClass: 'modal-dialog-centered',
        pseudoModalParentAdditionalClass: 'payu-blik-without-redirect-payment-method-modal-container',
        pseudoModalAdditionalClass: 'payu-blik-without-redirect-payment-method-modal',
        modalContentSelector: '.payu-blik-without-redirect-payment-method--modal-content',
        modalContentMessageWaitSelector: '.payu-blik-without-redirect-payment-method--modal-content-message-wait',
        modalContentMessageErrorSelector: '.payu-blik-without-redirect-payment-method--modal-content-message-error',
        modalContentMessageSuccessSelector: '.payu-blik-without-redirect-payment-method--modal-content-message-success',
        blikCodeInputSelector: '.payu-blik-without-redirect-payment-method--input',
        requiredTosContainerSelector: '.confirm-tos [required="required"]',
        submitButtonSelector: '#confirmFormSubmit',
    }

    init() {
        this.$blikModal = document.querySelector(this.options.modalContentSelector);
        this.$blikCodeInput = document.querySelector(`${this.options.blikCodeInputSelector}[form="${this.el.id}"]`);
        this.$blikButtonSubmit = this.el.querySelector(this.options.submitButtonSelector);
        this.$isOrderCreated = false;
        this._errors = [];
        this._tosValidator = new TosValidatorService();
        this._blikCodeValidatorService = new BlikCodeValidatorService();
        this._blikApiClient = new BlikApiService();

        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('submit', this._onSubmitForm.bind(this));
        this.$blikButtonSubmit.addEventListener('click', this._prepareModal.bind(this))
    }

    _onSubmitForm(e) {
        e.preventDefault();
    }

    _prepareModal() {
        if (this._tosValidator.validate() === false) {
            return;
        }

        this.$blikCodeInput.classList.remove(this.options.isInvalidClass);

        if (this._blikCodeValidatorService.validate(this.$blikCodeInput.value) === false) {
            this.$blikCodeInput.classList.add(this.options.isInvalidClass);

            return;
        }

        this.$pseudoModal = new BlikModalUtil(this.$blikModal.outerHTML);

        this.$pseudoModal.open(this._onOpenBlik.bind(this));

        this.$blikModal = this.$pseudoModal.getModal();

        this._blikApiClient.createOrder(new FormData(this.el), this._handleOrder.bind(this));
        this.$blikModal.classList.add(this.options.pseudoModalAdditionalClass);
        this.$blikModal.parentNode.classList.add(this.options.pseudoModalParentAdditionalClass);
        this.$blikModal.querySelector(this.options.pseudoModalDialogSelector)
            .classList.add(this.options.pseudoModalDialogCenteredClass);
    }

    _onOpenBlik() {
        PluginManager.initializePlugins();
    }

    _handleOrder(response) {
        const json = JSON.parse(response);

        if (json.orderId) {
            this.$isOrderCreated = true;
        }

        this._initDate = null;
        this._finishUrl = json.finishUrl;

        if (this._handleErrors(json) === false) {
            return false;
        } else if (json.success && json.orderId) {
            this._orderId = json.orderId;
            this._initDate = new Date();

            setTimeout(() => {
                this._blikApiClient.checkPaymentState(json.orderId, this._handleCheckPaymentState.bind(this));
            }, this.options.blikDurationTimeout)
        }
    }

    _handleCheckPaymentState(response) {
        const json = JSON.parse(response);

        if (
            this._initDate instanceof Date
            && Math.round(((new Date() - this._initDate % 86400000) % 3600000) / 60000) >= 5
        ) {
            this.$blikModal.querySelector(this.options.modalContentSelector)
                .classList.add(this.options.isInvalidClass);
            this.$blikModal.querySelector(this.options.modalContentMessageWaitSelector)
                .classList.add(this.options.isHiddenClass);
            this.$blikModal.querySelector(this.options.modalContentMessageErrorSelector)
                .classList.remove(this.options.isHiddenClass);
            this.$blikModal.querySelector(this.options.modalContentMessageSuccessSelector)
                .classList.add(this.options.isHiddenClass);
            setTimeout(() => {
                window.location.replace(this._finishUrl);
            }, 5000);
        } else if (!this._handleErrors(json)) {
            return false;
        } else if (json.waiting) {
            setTimeout(() => {
                this._blikApiClient.checkPaymentState(this._orderId, this._handleCheckPaymentState.bind(this));
            }, this.options.blikDurationTimeout)
        } else {
            if (json.success) {
                this.$blikModal.classList.add(this.options.isSuccessClass);
                this.$blikModal.querySelector(this.options.modalContentMessageWaitSelector)
                    .classList.add(this.options.isHiddenClass);
                this.$blikModal.querySelector(this.options.modalContentMessageErrorSelector)
                    .classList.add(this.options.isHiddenClass);
                this.$blikModal.querySelector(this.options.modalContentMessageSuccessSelector)
                    .classList.remove(this.options.isHiddenClass);

                setTimeout(() => {
                    window.location.replace(this._finishUrl);
                }, 5000);
            }
        }
    }

    _handleErrors(json) {
        if (json.waiting) {
            return true;
        } else if (typeof json.error !== 'undefined' && json.error.length > 0) {
            this._errors += json.error;

            return false;
        } else if (!json.success) {
            this.$blikModal.querySelector(this.options.modalContentSelector)
                .classList.add(this.options.isInvalidClass);
            this.$blikModal.querySelector(this.options.modalContentMessageWaitSelector)
                .classList.add(this.options.isHiddenClass);
            this.$blikModal.querySelector(this.options.modalContentMessageErrorSelector)
                .classList.remove(this.options.isHiddenClass);
            this.$blikModal.querySelector(this.options.modalContentMessageSuccessSelector)
                .classList.add(this.options.isHiddenClass);
            setTimeout(() => {
                window.location.replace(this._finishUrl);
            }, 5000);

            return false;
        }

        return true;
    }
}
