# CiviCRM Event Invitations

## Scope

This extension allows you to invite contacts in CiviCRM to an event and provides
a simple feedback form where contacts can choose whether they can attend or not.
You can invite contacts via email or letter, both can include a personalized
link, if you use a letter, the link can be presented as a QR link.

Currently, the extension only provides a very basic built-in feedback form. This
form currently only features a simple "register" button which will set the
participant's status to confirmed so Weẃ encourage you to generate an external
landing page / endpoint for the form. (seer below) For Drupal you will most
likely want to use
the [CiviRemote Drupal module](https://github.com/systopia/civiremote) which
includes a lot of pre-built features.

This extension is licensed
under [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0).

## Features

* Select contacts from a CiviCRM search and send them an invitation via email or
  letter
* Use a simple built-in or an individual feedback form that allows contacts to
  reply to your invitation
* Feedback given in the form will update participant status

## Requirements

* PHP v7.0+
* CiviCRM 5.3+

Recommended:

* A system that will provide your feedback form such as an external website or
  the CMS CiviCRM is installed on.

*Remark*: This extension
uses [Chillerlan's QR code generator](https://github.com/chillerlan/php-qrcode)
to generate QR codes.

## Configuration

Find the extension's settings under → Administer → Administration Console →
Event Invitation Configuration (`/civicrm/eventinvitation/settings?reset=1`) and
provide information on the endpoint, if you want to use one.

Create at least one message template that contains one of the following Smarty
variables:

* `{$qr_event_invite_code}` - generates a unique link for the participant
* `{$qr_event_invite_code_img}` - generates a unique link for the participant
  presented as an QR Code with fixed width
* `{$qr_event_invite_code_data}` - generates a unique link for the participant
  presented as an QR Code that can be html formatted as an image

*Remark*: if an event-invitation is being generated as pdf-file from a particular 
message template, then only the *Html*-section of that template will be taken into
account. The *Plain-Text* section will be ignored. In that case, all tokens should
be placed into the *Html* section. *Plain-Text* only message template will result
in empty pdf-files.

### Using a Drupal endpoint

If you are using a Drupal endpoint based on CiviRemote, visit the extension's
settings page, tickt the custom URL box and enter one of the following as the
custom url, depending on your configuration and, if applicable, custom
implementations:
* `https://yourpublicfrontend.org/civiremote/event/register/{token}`
    With the *register* endpoint, the user's reaction to the invitation is being
    processed as a registration, i.e. they must have the permission to register,
    which includes that registration for the event is still open and no other
    restrictions are in effect.
* `https://yourpublicfrontend.org/civiremote/event/update/{token}`
    With the *update* endpoint, the user's reaction to the invitation is being
    processed as an update to a registration (which, technically speaking,
    already exists with the status *Invited*), i.e. they must have the
    permission to update own registrations.

On your public Drupal system, install
[CiviRemote](https://github.com/systopia/civiremote), add a CiviRemote profile
(`/admin/config/cmrf/profiles`) and connector (`/admin/config/cmrf/connectors`)
if you have not done so yet.

## Usage

From a contact search result, choose any number of contacts and select "Invite
to event" from the action menu. You will be presented with a popup dialogue that
allows you to select:

* the event you are inviting the contacts for
* the message template to use
* the sender address
* the role to be assigned to the participants
* whether you want to send emails or generate PDF-Files

The extension will only allow to invite contacts that do not already have a
registration with a positive class for the event in question. You must use a
template that contains at least one of the token described above. After you
hit "Confirm Action" a participant object (registration) with the status "
Invited" will be created for all selected contacts.

When contacts use the feedback link, this registration will be updated to the "
Registered" or "Cancelled" status depending on the user's choices.

If you are aiming at utilizing an automated email workflow, checkin of
participants using QR-Codes and/or using customized remote registration forms,
make sure to have a look at the following extensions:

* [Remote Events - create customized event registration forms and workflows](https://github.com/systopia/de.systopia.remoteevent)
* [Event Checkin - use QR codes (e.g. on event tickets) to checkin participants to CiviCRM Events ](https://github.com/systopia/eventcheckin)
* [Event Communication - define rules to send  out event mails based on participant's role, status etc. (incl. attachments)](https://github.com/systopia/eventmessages)

## Known Issues

The built-in feedback form ist still very basic and limited in its
functionality so, unless you want to extend the built-in form yourself or would
be ready to fund some development to extend its features we would encourage you
to generate an external landing page / endpoint for the form. For Drupal you
will most likely want to use
the [CiviRemote Drupal module](https://github.com/systopia/civiremote) which
includes a lot of pre-built features.

## Documentation
- EN: https://docs.civicrm.org/eventinvitation/en/latest (automatic publishing)
- DE: https://docs.civicrm.org/eventinvitation/de/latest (automatic publishing)
