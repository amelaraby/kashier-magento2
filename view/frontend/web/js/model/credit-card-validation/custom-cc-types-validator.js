/* @api */
define([
    'jquery',
    'mageUtils'
], function ($, utils) {
    'use strict';

    var customCcTypes = [{
        title: 'Meeza',
        type: 'MEEZA',
        pattern: '^50[0-9]{11}(?:[0-9]{3})?$',
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
            name: 'CVV',
            size: 3
        }
    }];

    /**
     * @param {*} card
     * @param {*} isValid
     * @return {Object}
     */
    function resultWrapper(card, isValid)
    {
        return {
            card: card,
            isValid: isValid
        };
    }

    return function (cardNumber) {
        let i;

        for (i = 0; i < customCcTypes.length; i++) {
            if (new RegExp(customCcTypes[i].pattern).test(cardNumber)) {
                return resultWrapper(customCcTypes[i], true)
            }
        }

        return resultWrapper(null, false)
    };
});
