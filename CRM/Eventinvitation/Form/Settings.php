<?php

/*-------------------------------------------------------+
| SYSTOPIA Event Invitation                              |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Zschiedrich (zschiedrich@systopia.de)       |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

use CRM_Eventinvitation_ExtensionUtil as E;

class CRM_Eventinvitation_Form_Settings extends CRM_Core_Form
{
    // TODO: These (three) settings constants should be at a more central place and be renamed:
    const LINK_TARGET_IS_CUSTOM_FORM_NAME = 'link_target_is_custom';
    const CUSTOM_LINK_TARGET_FORM_NAME = 'custom_link_target';

    const SETTINGS_KEY = 'eventinvitation_settings';

    public function buildQuickForm()
    {
        $this->setTitle(E::ts("Event Invitation Configuration"));

        $this->add(
            'checkbox',
            self::LINK_TARGET_IS_CUSTOM_FORM_NAME,
            E::ts('Use a custom registration endpoint')
        );

        $this->add(
            'text',
            self::CUSTOM_LINK_TARGET_FORM_NAME,
            E::ts('Url of custom registration endpoint:'),
            ['class' => 'huge'],
            false
        );
        $this->addRule(
            self::CUSTOM_LINK_TARGET_FORM_NAME,
            E::ts('Enter a valid web address beginning with \'http://\' or \'https://\'.'),
            'url'
        );
        $this->addRule(
            self::CUSTOM_LINK_TARGET_FORM_NAME,
            E::ts('The link must include the placeholder <code>{token}</code>.'),
            'regex',
            '/\{token\}/'
        );

        $settings = Civi::settings()->get(self::SETTINGS_KEY);
        $this->setDefaults($settings);

        $this->addButtons(array(
            array(
                'type' => 'submit',
                'name' => E::ts('Save'),
                'isDefault' => true,
            ),
        ));

        parent::buildQuickForm();
    }

    public function postProcess()
    {
        parent::postProcess();

        $values = $this->exportValues(
            [
                self::LINK_TARGET_IS_CUSTOM_FORM_NAME,
                self::CUSTOM_LINK_TARGET_FORM_NAME,
            ],
            true
        );

        Civi::settings()->set(self::SETTINGS_KEY, $values);
    }
}
