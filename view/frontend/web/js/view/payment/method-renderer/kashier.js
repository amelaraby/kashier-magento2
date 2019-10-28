define([
    'jquery',
    'mage/translate',
    'Magento_Payment/js/view/payment/cc-form',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'ITeam_Kashier/js/model/credit-card-validation/custom-cc-types-validator',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Ui/js/modal/alert'
], function ($, $t, Component, fullScreenLoader, creditCardData, customCardNumberValidator, additionalValidators, VaultEnabler, redirectOnSuccessAction, alert) {
    'use strict';

    return Component.extend({
        defaults: {
            placeOrderHandler: null,
            validateHandler: null,
            active: false,
            showThreeDsIframe: false,
            acsUrl: '',
            paReq: '',
            termUrl: '',
            threeDsResponse: null,
            tokenizationResponse: null,
            template: 'ITeam_Kashier/payment/form'
        },

        initialize: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());
        },
        /**
         * @returns {Bool}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * @returns {String}
         */
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()].vaultCode;
        },
        _initCustomCardTypesSelectedCard: function () {
            var self = this;
            this.creditCardNumber.subscribe(function (value) {
                var result;

                if (self.selectedCardType() !== null) {
                    return false;
                }

                self.selectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = customCardNumberValidator(value);

                if (!result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.selectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.creditCardNumber = value;
                    self.creditCardType(result.card.type);
                }
            });
        },

        /**
         * Set list of observable attributes
         *
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            this._super()
                .observe(['active', 'acsUrl', 'termUrl', 'paReq', 'showThreeDsIframe', 'threeDsResponse', 'tokenizationResponse']);

            const self = this;
            setTimeout(function () {
                self._initCustomCardTypesSelectedCard();
            }, 1000);

            this._initIframeListener();

            return this;
        },

        /**
         * @returns {String}
         */
        getCode: function () {
            return 'kashier';
        },

        /**
         * @returns {Object}
         */
        getData: function () {
            var data = {
                method: this.getCode(),
                'additional_data': {
                    'cc_type': this.creditCardType(),
                    'three_ds_response': this.threeDsResponse(),
                    'tokenization_response': this.tokenizationResponse()
                }
            };

            this.vaultEnabler.visitAdditionalData(data);

            return data;
        },

        /**
         * Check if payment is active
         *
         * @returns {Boolean}
         */
        isActive: function () {
            const active = this.getCode() === this.isChecked();

            this.active(active);

            return active;
        },
        context: function () {
            return this;
        },

        /**
         * @param {Function} handler
         */
        setPlaceOrderHandler: function (handler) {
            this.placeOrderHandler = handler;
        },

        /**
         * @param {Function} handler
         */
        setValidateHandler: function (handler) {
            this.validateHandler = handler;
        },
        validate: function () {
            return this.validateHandler();
        },
        /**
         * Place order.
         */
        placeOrder: function (data, event) {
            const self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);

                this.tokenizeCard()
                    .then(function (response) {
                        self.tokenizationResponse(JSON.stringify(response));
                        self.getPlaceOrderDeferredObject()
                            .fail(
                                function (jqXHR, textStatus, errorThrown) {
                                    self._placeOrderFailureHandler(jqXHR, textStatus, errorThrown);
                                }
                            ).done(function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        });

                        return true;
                    })
                    .catch(function (error) {
                        self.isPlaceOrderActionAllowed(true);
                        console.log(error);
                    });
            }

            return false;
        },
        tokenizeCard: function () {
            let request = {
                method: 'POST',
                headers: {
                    "Content-type": "application/json; charset=UTF-8"
                }
            };

            request.body = JSON.stringify({
                hash: window.checkoutConfig.payment[this.getCode()].tokenizationHash,
                merchantId: window.checkoutConfig.payment[this.getCode()].merchantId,
                card_holder_name: "John Doe",
                card_number: this.creditCardNumber(),
                ccv: this.creditCardVerificationNumber(),
                expiry_month: this.creditCardExpMonth().padStart(2, '0'),
                expiry_year: this.creditCardExpYear().slice(-2),
                shopper_reference: window.checkoutConfig.payment[this.getCode()].shopperReference,
                tokenValidity: this.vaultEnabler.isActivePaymentTokenEnabler() ? 'perm' : 'temp'
            });

            return fetch(window.checkoutConfig.payment[this.getCode()].tokenizationUrl, request)
                .then((response) => {
                    if ((response.status >= 200 && response.status < 300)) {
                        return Promise.resolve(response.json())
                    } else {
                        return Promise.reject(new Error(response.statusText))
                    }
                })
                .then((response) => {
                    if ("".concat(response.body.status).toUpperCase() !== "SUCCESS") {
                        return Promise.reject(new Error(response.error.explanation))
                    }

                    return Promise.resolve(response);
                });
        },
        _placeOrderFailureHandler: function (jqXHR) {
            var self = this;
            if (jqXHR.responseJSON.message.toLowerCase().includes('3dsecure')) {
                this.messageContainer.clear();
                $.ajax({
                    url: window.checkoutConfig.payment.kashier.threeDsUrl,
                    type: 'post',
                    context: this,
                    data: {},
                    dataType: 'json',

                    /**
                     * {Function}
                     */
                    beforeSend: function () {
                        fullScreenLoader.startLoader();
                    },

                    /**
                     * {Function}
                     */
                    success: function (response) {
                        self.paReq(response.paReq);
                        self.acsUrl(response.acsUrl);
                        self.termUrl(response.processACSRedirectURL);

                        $('#' + self.getCode() + '_3ds_form_hidden').submit();
                        self.showThreeDsIframe(true);
                        fullScreenLoader.stopLoader();
                    },
                });
            } else {
                self.isPlaceOrderActionAllowed(true);
            }
        },
        _initIframeListener: function () {
            if (window.addEventListener) {
                addEventListener("message", this.iFrameListener.bind(this), false);
            } else {
                attachEvent("onmessage", this.iFrameListener.bind(this));
            }
        },
        iFrameListener: function (e) {
            const iFrameMessage = e.data;
            console.log(iFrameMessage);
            // noinspection EqualityComparisonWithCoercionJS
            if (iFrameMessage.message == "merchantStoreRedirect" && iFrameMessage.params) {
                this.showThreeDsIframe(false);
                console.log(iFrameMessage.params.status);
                switch (iFrameMessage.params.status) {
                    case 'SERVER_ERROR' || 'INVALID_REQUEST':
                        this.iFrameErrorHandler();
                        break;
                    case  'SUCCESS':
                        // noinspection EqualityComparisonWithCoercionJS
                        if (iFrameMessage.params.response && iFrameMessage.params.response.card.result == 'SUCCESS') {
                            this.threeDsResponse(JSON.stringify(iFrameMessage.params));
                            this.placeOrder();
                        } else {
                            this.iFrameErrorHandler();
                        }
                        break;
                    default:
                        this.iFrameErrorHandler();
                        break;
                }
            }
        },
        iFrameErrorHandler: function () {
            const errorMessage = $t('Please check your credit card info and try again.');

            // this.messageContainer.addErrorMessage({
            //     message: errorMessage
            // });

            alert({
                content: errorMessage
            });

            this.isPlaceOrderActionAllowed(true);
        }
    });
});
