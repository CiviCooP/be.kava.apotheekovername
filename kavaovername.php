<?php

require_once 'kavaovername.civix.php';


/* -- Custom hook implementations -- */

/**
 * Adds a link to the extension form to the contact summary.
 * Implements hook_civicrm_pageRun().
 * @link https://docs.civicrm.org/dev/en/stable/hooks/hook_civicrm_pageRun/
 */
function kavaovername_civicrm_pageRun(&$page) {
  CRM_KavaOvername_Utils::addLinkToPage($page);
}


/* -- Default Civix hooks follow -- */

/**
 * Implements hook_civicrm_config().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function kavaovername_civicrm_config(&$config) {
  _kavaovername_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function kavaovername_civicrm_xmlMenu(&$files) {
  _kavaovername_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function kavaovername_civicrm_install() {
  _kavaovername_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function kavaovername_civicrm_postInstall() {
  _kavaovername_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function kavaovername_civicrm_uninstall() {
  _kavaovername_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function kavaovername_civicrm_enable() {
  _kavaovername_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function kavaovername_civicrm_disable() {
  _kavaovername_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function kavaovername_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _kavaovername_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function kavaovername_civicrm_managed(&$entities) {
  _kavaovername_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 * Generate a list of case-types.
 * Note: This hook only runs in CiviCRM 4.4+.
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function kavaovername_civicrm_caseTypes(&$caseTypes) {
  _kavaovername_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 * Generate a list of Angular modules.
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function kavaovername_civicrm_angularModules(&$angularModules) {
  _kavaovername_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function kavaovername_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _kavaovername_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
 * function kavaovername_civicrm_preProcess($formName, &$form) {
 * } // */

/**
 * Implements hook_civicrm_navigationMenu().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 * function kavaovername_civicrm_navigationMenu(&$menu) {
 * _kavaovername_civix_insert_navigation_menu($menu, NULL, array(
 * 'label' => ts('The Page', array('domain' => 'be.kava.kavaovername')),
 * 'name' => 'the_page',
 * 'url' => 'civicrm/the-page',
 * 'permission' => 'access CiviReport,access CiviContribute',
 * 'operator' => 'OR',
 * 'separator' => 0,
 * ));
 * _kavaovername_civix_navigationMenu($menu);
 * } // */
