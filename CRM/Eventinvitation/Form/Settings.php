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

class CRM_Eventinvitation_Form_Settings_AdminSettings extends CRM_Core_Form
{
    private const LINK_TARGET_IS_CUSTOM_FORM_NAME = 'link_target_is_custom';
    private const CUSTOM_LINK_TARGET_FORM_NAME = 'custom_link_target';

    private const SETTINGS_KEY = 'eventinvitation_settings';

    public function buildQuickForm()
    {
        $this->add(
            'checkbox',
            self::LINK_TARGET_IS_CUSTOM_FORM_NAME,
            E::ts('Activate for using a custom target link. Otherwise the build-in page is used.')
        );

        $this->add(
            'text',
            self::CUSTOM_LINK_TARGET_FORM_NAME,
            E::ts('Custom link target URL:'),
            ['class' => 'huge'],
            true
        );
        $this->addRule(
            self::CUSTOM_LINK_TARGET_FORM_NAME,
            E::ts('Enter a valid web address beginning with \'http://\' or \'https://\'.'),
            'url'
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
