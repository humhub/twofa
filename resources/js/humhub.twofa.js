/*
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
humhub.module('twofa', function (module, require, $) {
    var client = require('client');
    var loader = require('ui.loader');
    var modal = require('ui.modal');

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
        if (module.config['confirmAction']) {
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
        callDriverAction
    });
});
