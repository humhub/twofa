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
## Interception

Since 1.4 the verification is enforced through the core user gate system
(`TwofaGate`, see the core `docs/develop/user-gates.md`) instead of a
`Controller::EVENT_BEFORE_ACTION` handler. The former `twofa.beforeCheck` event
has been removed.

The gate applies to full page navigation and AJAX/PJAX requests, but not to
token-authenticated API requests — REST, CalDAV and similar endpoints are
therefore not intercepted and do not need to opt out. Login and logout
(`user/auth`) as well as the mobile push token update stay reachable while
verification is pending.

There is no longer a per-controller opt-out: the gate intercepts every full
page request of a user with pending verification until the check is completed.