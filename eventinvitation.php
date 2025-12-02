<?php

declare(strict_types = 1);


// phpcs:disable PSR1.Files.SideEffects
require_once 'eventinvitation.civix.php';
require_once __DIR__ . '/vendor/autoload.php';
// phpcs:enable

use CRM_Eventinvitation_ExtensionUtil as E;

/**
 * @param string $objectType
 * @param array<int, array<string, mixed>> $tasks
 * @return void
 */
function eventinvitation_civicrm_searchTasks(string $objectType, array &$tasks):void {
  // add "Invite to event" task to contact list
  if ($objectType === 'contact') {
    $tasks[] = [
      'title' => E::ts('Invite to event'),
      'class' => 'CRM_Eventinvitation_Form_Task_ContactSearch',
      'result' => FALSE,
    ];
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 *
 * @phpstan-param array<string, mixed> $config
 */
function eventinvitation_civicrm_config(array $config):void {
  _eventinvitation_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function eventinvitation_civicrm_install():void {
  _eventinvitation_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function eventinvitation_civicrm_enable():void {
  _eventinvitation_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
 *
 * // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 * function eventinvitation_civicrm_navigationMenu(&$menu) {
 * _eventinvitation_civix_insert_navigation_menu($menu, 'Mailings', array(
 * 'label' => E::ts('New subliminal message'),
 * 'name' => 'mailing_subliminal_message',
 * 'url' => 'civicrm/mailing/subliminal',
 * 'permission' => 'access CiviMail',
 * 'operator' => 'OR',
 * 'separator' => 0,
 * ));
 * _eventinvitation_civix_navigationMenu($menu);
} // */
