<?php

/**
 * Class CRM_KavaOvername_CustomField.
 * Functions to get and cache custom field information. Copied from nl.sp.generic.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package be.kava.apotheekovername
 * @license AGPL-3.0
 */
class CRM_KavaOvername_CustomField {

  /**
   * @var \CRM_KavaOvername_CustomField $instance
   */
  private static $instance;

  /**
   * @var array $customGroupCache
   */
  private $customGroupCache = [];

  /**
   * @var array $customGroupMapping
   */
  private $customGroupMapping = [];

  /**
   * @var array $optionGroupCache
   */
  private $optionGroupCache = [];

  /**
   * Get instance.
   * @return \CRM_KavaOvername_CustomField
   */
  public static function singleton() {
    if (!isset(static::$instance)) {
      static::$instance = new static;
    }

    return static::$instance;
  }

  /**
   * Initialize: load all groups and fields into local cache.
   * @throws \CRM_KavaOvername_Exception if no groups/fields are found.
   */
  public function initialize() {
    if (count($this->customGroupCache) == 0) {
      $groups = civicrm_api3('CustomGroup', 'get', ['options' => ['limit' => 10000]]);
      if (!$groups['is_error'] && count($groups['values']) > 0) {
        foreach ($groups['values'] as $gid => $group) {
          $this->customGroupCache[$group['name']] = $group;
          $this->customGroupCache[$group['name']]['fields'] = [];
          $this->customGroupMapping[$gid] = $group['name'];
        }
      } else {
        throw new CRM_KavaOvername_Exception('Could not fetch list of CustomGroups in CRM_KavaOvername_CustomField.');
      }

      $fields = civicrm_api3('CustomField', 'get', ['options' => ['limit' => 10000]]);
      if (!$fields['is_error'] && count($fields['values']) > 0) {
        foreach ($fields['values'] as $fid => $field) {
          $fcgid = &$field['custom_group_id'];
          if (array_key_exists($fcgid, $this->customGroupMapping)) {
            $groupName = $this->customGroupMapping[$fcgid];
            $this->customGroupCache[$groupName]['fields'][$field['name']] = $field;
          } else {
            continue; // Field does not belong to group?
          }
        }
      } else {
        throw new CRM_KavaOvername_Exception('Could not fetch list of CustomFields in CRM_KavaOvername_CustomField.');
      }
    }

    // echo '<pre>' . print_r($this->customGroupCache, true) . '</pre>';
  }

  /**
   * Find a custom field group by name.
   * @param string $name Group name
   * @return array|bool Group if found, false if not found
   */
  public function getGroupByName($name) {
    $this->initialize();
    if (isset($this->customGroupCache[$name])) {
      return $this->customGroupCache[$name];
    }
    return FALSE;
  }

  /**
   * Find a custom field group by ID.
   * @param int $id ID
   * @return array|bool Group if found, false if not found
   */
  public function getGroupById($id) {
    $this->initialize();
    $key = $this->customGroupMapping[$id];
    if ($key && isset($this->customGroupCache[$key])) {
      return $this->customGroupCache[$key];
    }
    return FALSE;
  }

  /**
   * Get a custom group ID by name.
   * @param string $name Group name
   * @return int|bool Group ID if found, false if not found
   */
  public function getGroupId($name) {
    $group = $this->getGroupByName($name);
    if (!empty($group)) {
      return $group['id'];
    }
    return FALSE;
  }

  /**
   * Find a custom field by group name and field name.
   * @param string $groupName Group name
   * @param string $fieldName Field name
   * @return array|bool Field if found, false if not found
   */
  public function getField($groupName, $fieldName) {
    $this->initialize();
    if (isset($this->customGroupCache[$groupName]['fields'][$fieldName])) {
      return $this->customGroupCache[$groupName]['fields'][$fieldName];
    }
    return FALSE;
  }

  /**
   * Get all custom fields by group name
   * @param string $groupName Group name
   * @return array|bool Array of fields if found, false if not found
   */
  public function getFields($groupName) {
    $this->initialize();
    if (isset($this->customGroupCache[$groupName]['fields'])) {
      return $this->customGroupCache[$groupName]['fields'];
    }
    return FALSE;
  }

  /**
   * Get a custom field ID by group name and field name.
   * @param string $groupName Group name
   * @param string $fieldName Field name
   * @return int|bool Field ID if found, false if not found
   */
  public function getFieldId($groupName, $fieldName) {
    $field = $this->getField($groupName, $fieldName);
    if ($field) {
      return $field['id'];
    }
    return FALSE;
  }

  /**
   * Get the API field name by group name and field name (eg 'custom_404').
   * @param string $groupName Group name
   * @param string $fieldName Field name
   * @return string|bool Field name if found, false if not found
   */
  public function getApiFieldName($groupName, $fieldName) {
    $fieldId = $this->getFieldId($groupName, $fieldName);
    if(!$fieldId) {
      return FALSE;
    }
    return 'custom_' . $fieldId;
  }

  /**
   * Returns an option group, that is cached if possible.
   * @param string $ogName Option group name
   * @return array|mixed Option group
   */
  public function getOptionGroupByName($ogName) {
    if(!array_key_exists($ogName, $this->optionGroupCache)) {
      $this->optionGroupCache[$ogName] = civicrm_api3('OptionGroup', 'getsingle', ['name' => $ogName]);
    }
    return $this->optionGroupCache[$ogName];
  }

}