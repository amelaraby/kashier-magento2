/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'uiComponent',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'ITeam_Kashier/js/model/credit-card-validation/custom-cc-types-validator'
    ],
    function (Component, $, creditCardNumberValidator, customCardNumberValidator) {
        'use strict';

        $.each({
            'kashier-validate-card-type': [
                function (number, item, allowedTypes) {
                    var cardInfo,
                        i,
                        l;

                    if (customCardNumberValidator(number).isValid) {
                        return true;
                    }

                    if (!creditCardNumberValidator(number).isValid) {
                        return false;
                    }

                    cardInfo = creditCardNumberValidator(number).card;

                    for (i = 0, l = allowedTypes.length; i < l; i++) {
                        if (cardInfo.title == allowedTypes[i].type) { //eslint-disable-line eqeqeq
                            return true;
                        }
                    }

                    return false;
                },
                $.mage.__('Please enter a valid credit card type number.')
            ]
        }, function (i, rule) {
            rule.unshift(i);
            $.validator.addMethod.apply($.validator, rule);
        });

        return Component.extend({});
    }
);
