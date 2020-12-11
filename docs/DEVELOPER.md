# Developer

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