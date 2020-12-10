/*
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
humhub.module('twofa', function (module, require, $) {
    var client = require('client');
    var loader = require('ui.loader');

    var callDriverAction = function(evt) {
        var $container = $(evt.$trigger.data('container'));
        var data = {
            driver: evt.$trigger.data('driver-class'),
            action: evt.$trigger.data('driver-action'),
        };
        loader.set($container);
        client.post(evt, {data}).then(function (response) {
            $container.html(response.html);
        }).catch(function (err) {
            module.log.error(err, true);
        }).finally(function () {
            evt.finish();
        });
    };

    module.export({
        callDriverAction
    });
});
