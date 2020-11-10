# CiviCRM Event Invitations

## Scope

This extension allows you to invite contacts in CiviCRM to an event and provides a simple feedback form where contacts can choose whether they can attend or not. You can invite contacts via email or letter, both can include a personalized link, if you use a letter, the link can be presented as a QR link.

Instead of using the built-in endpoint for the registration, we'd encourage you to generate an external landing page.
If you would like to build your feedback form based on Drupal 8 you will most likely want to have a look at and/or use the CiviRemote Drupal module which includes a lot of pre-built features (https://github.com/systopia/civiremote).


This extension is licensed under [AGPL-3.0](LICENSE.txt).

## Features

* Select contacts from a CiviCRM search and send them an invitation via email or letter
* Individual feedback form that allows contacts to reply to your invitation
* Feedback given in the form will update participant status

TODO: Complete Feature List

## Requirements

* PHP v7.0+
* CiviCRM 5.3

Recommended:
* A system that will provide your feedback form such as an external website

*Remark*: This extension uses [Chillerlan's QR code generator](https://github.com/chillerlan/php-qrcode) to generate QR codes.

## Configuration

No configuration required.

If you want to use an external landing page, you can provide that on the EventInvitation settings page.

