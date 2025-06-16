import BlikCodeFormatterPlugin from './js/blik-without-redirect-payment-method/blik-code-formatter.plugin';
import BlikWithoutRedirectPaymentMethodPlugin from './js/blik-without-redirect-payment-method/blik-without-redirect-payment-method.plugin';
import PaymentMethodsPlugin from "./js/payment-methods/payment-methods.plugin";

const PluginManager = window.PluginManager;

PluginManager.register('BlikCodeFormatterPlugin', BlikCodeFormatterPlugin, '.payu-blik-without-redirect-payment-method--input');
PluginManager.register('BlikWithoutRedirectPaymentMethodPlugin', BlikWithoutRedirectPaymentMethodPlugin, '[data-payu-blik-without-redirect-payment-method]');
PluginManager.register('PaymentMethodsPlugin', PaymentMethodsPlugin, '[data-payment-methods-plugin="true"]');
