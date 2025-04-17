# Developer

## Individual Drivers

New driver should be extended from `humhub\modules\twofa\drivers\BaseDriver` in the folder `drivers`.

Also new created driver should be added into array `humhub\modules\twofa\Module->drivers`:

```php
public $drivers = [
    EmailDriver::class,
    GoogleAuthenticatorDriver::class,
];
```

Default driver `humhub\modules\twofa\Module->defaultDriver` is used for Users from enforced Groups:

```php
public $defaultDriver = EmailDriver::class;
```
## Events

### `twofa.beforeCheck`

The `twofa.beforeCheck` event is triggered before a Two-Factor Authentication (2FA) check is performed.

Other modules can listen to this event and set `$handled = true` to skip the 2FA check.

This mechanism allows disabling 2FA:

- Globally for a module via its `beforeAction()` method
- For specific controllers via their `beforeAction()` method
- For specific actions within a controller via conditional logic in `beforeAction()`

Example:
```php
Yii::$app->on('twofa.beforeCheck', function (Event $event) use ($action) {
    $event->handled = $action->controller->id === 'some-controller'; // Will disable 2FA for `some-controller`
});
```