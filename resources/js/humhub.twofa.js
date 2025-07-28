/*
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
humhub.module('twofa', function (module, require, $) {
    var client = require('client');
    var loader = require('ui.loader');
    var modal = require('ui.modal');

    module.selectGoogleAuthenticatorDriver = function(configLayoutSelector) {
        // Request QR code automatically(without confirmation) on first selecting the driver:
        if ($(configLayoutSelector + ' #twofaGoogleAuthCode').html().trim() === '') {
            $(configLayoutSelector + ' [data-action-click="twofa.callDriverAction"]')
                .data('driver-confirm', 0)
                .click()
                .data('driver-confirm', 1);
        }
    };

    var selectDriver = function(evt) {
        $('[data-driver-fields]').addClass('d-none');
        var configLayoutSelector = '[data-driver-fields="' + evt.$trigger.val().replaceAll('\\', '\\\\') + '"]';

        // Additional action per each Driver:
        var driverSelectFunctionName = 'select' + evt.$trigger.val().substr(evt.$trigger.val().lastIndexOf('\\') + 1);
        if (typeof module[driverSelectFunctionName] === 'function') {
            module[driverSelectFunctionName](configLayoutSelector);
        }

        $(configLayoutSelector).removeClass('d-none');
    };

    var callDriverAction = function(evt) {
        var $container = $(evt.$trigger.data('container'));
        loader.set($container);
        var callAction = function() {
            var data = {
                driver: evt.$trigger.data('driver-class'),
                action: evt.$trigger.data('driver-action'),
            };
            return client.post(evt, {data}).then(function (response) {
                $container.html(response.html);
            }).catch(function (err) {
                module.log.error(err, true);
            });
        };
        if (evt.$trigger.data('driver-confirm')) {
            var options = {
                'header': module.text('confirm.action.header'),
                'body': module.text('confirm.action.question'),
                'confirmText': module.text('confirm.action.button'),
            };
            modal.confirm(options).then(function (confirm) {
                confirm ? callAction() : loader.reset($container);
            }).finally(function () {
                evt.finish();
            });
        } else {
            callAction().finally(function () {
                evt.finish();
            });
        }
    };

    module.export({
        selectDriver,
        callDriverAction,
    });
});
