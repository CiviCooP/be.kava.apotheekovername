<?php

/**
 * Class CRM_KavaOvername_Utils.
 * Various utility functions.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package be.kava.apotheekovername
 * @license AGPL-3.0
 */
class CRM_KavaOvername_Utils {

  /**
   * Adds a link to this module's form to the contact summary page,
   * if the contact shown is an 'apotheekuitbating'.
   * Called from kavaovername.php.
   * @param CRM_Core_Page $page
   */
  public static function addLinkToPage(&$page) {
    if ($page instanceof CRM_Contact_Page_View_Summary) {
      $contactId = $page->getVar('_contactId');
      $subType = $page->get('contactSubtype');
      if ((is_string($subType) && $subType == 'apotheekuitbating') ||
          is_array($subType) && in_array('apotheekuitbating', $subType)
      ) {
        CRM_Core_Region::instance('page-body')->add([
          'template' => 'CRM/KavaOvername/Page/View/Summary/link_overname.tpl',
        ]);
      }
    }
  }

  /**
   * Check if a contact has a certain contact sub type.
   * @param int $contact_id Contact ID
   * @param string $type Type to check for
   * @return bool
   */
  public static function hasContactSubType($contact_id, $type) {
    try {
      $data = civicrm_api3('Contact', 'getsingle', [
        'id'     => $contact_id,
        'return' => 'contact_type,contact_sub_type',
      ]);

      if (is_array($data['contact_sub_type']) && in_array($type, $data['contact_sub_type'])) {
        return TRUE;
      }
      return FALSE;

    } catch (\CiviCRM_API3_Exception $e) {
      return FALSE; // Contact does not exist?
    }
  }

  /**
   * Check if a certain contact (id) exists.
   * @param int $contact_id Contact ID
   * @return bool
   */
  public static function contactIdExists($contact_id) {
    try {
      $data = civicrm_api3('Contact', 'getsingle', [
        'id'     => $contact_id,
        'return' => 'id',
      ]);
      return !empty($data['id']);

    } catch (\CiviCRM_API3_Exception $e) {
      return FALSE; // Contact does not exist
    }
  }
}
