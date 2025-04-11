Changelog
=========

1.1.4 (Unreleased)
----------------------
- Enh #58: Tests for `next` version
- Enh #65: Use PHP CS Fixer
- Enh #66: Reduce translation message categories
- Enh #: Add ability to skip 2FA for specific actions using `\humhub\components\Controller::skip2faCheck`

1.1.3 (June 8, 2023)
--------------------
- Fix #53: Fix initialize of inline script of QR code

1.1.2 (May 17, 2023)
-------------------
- Fix #55: Don't send a verification code when trusted IP address

1.1.1 (January 3, 2023)
-----------------------
- Fix #52: Fix checking of current IP address by trusted networks list

1.1.0 (November 9, 2022)
------------------------
- Enh #41: Added Option to use Google Authentication as Default
- Fix #50: Don't send a verification code when browser was remembered

1.0.7 (March 2, 2022)
---------------------
- Fix #45: Fix remember browser

1.0.6 (February 2, 2022)
-------------------------
- Enh #36: Update logout url to POST method
- Enh: Added French translations
- Enh #33: Added trusted network functionality
- Enh #16: Added remember browser for X days
- Fix #41: Fix error for user without email address

1.0.5 (August 10 , 2021)
-----------------------
- Fix #29: Fix button "Log out" to prevent pjax
- Fix #31: Don't require 2FA on administration action "Impersonate"

1.0.4 (15 June, 2021)
---------------------
- Fix #23: Urlencode account name in otpauth URL 
- Fix #25: Fix double rendering QR code after cancel of requesting new code

1.0.3 (May 11, 2021)
--------------------
- Fix #22: Composer dependencies for Google Auth missing in marketplace package 

1.0.2 (May 10, 2021)
--------------------
- Enh #18: Generate QR code for Google authenticator by local JS script (Don't send TOTP key to Google)

1.0.1 (May 6, 2021)
-------------------
- Fix: Link in translatable string
- Enh: Use controller config for not intercepted actions (HumHub 1.9+)
- Fix: Don't verify code if user must change password

1.0.0 (February 9, 2021)
------------------------
- Enh: Initial release
- Init: Default driver to send code by e-mail
- Enh: Driver "Google Authenticator"
- Enh: Require pin code before enabling Google Authenticator
