/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'kashier',
                component: 'ITeam_Kashier/js/view/payment/method-renderer/kashier'
            }
        );
        return Component.extend({});
    }
);
