<?php

/**
 * Class CRM_KavaOvername_Task_OvernameTask.
 * Task that executes the actual overname process.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package be.kava.apotheekovername
 * @license AGPL-3.0
 */
class CRM_KavaOvername_Task_OvernameTask {

  /**
   * @var static $instance
   */
  private static $instance;

  /**
   * @var mixed $transaction
   */
  private $transaction;

  /**
   * Get instance.
   * @return static
   */
  public static function instance() {
    if (!isset(static::$instance)) {
      static::$instance = new static;
    }

    return static::$instance;
  }

  /**
   * Run an overname task from the task queue.
   * @param CRM_Queue_TaskContext $ctx
   * @param int $apotheekId Apotheekuitbating (contact id)
   * @param int $overnamedatum Overnamedatum (timestamp)
   * @param int $titularisId Nieuwe titularis (contact id)
   * @param int $eigenaarId Nieuwe eigenaar (contact id)
   * @return bool Success
   */
  public static function executeFromQueue(CRM_Queue_TaskContext $ctx, $apotheekId, $overnamedatum, $titularisId, $eigenaarId) {
    $instance = static::instance();
    return $instance->doOvername($apotheekId, $overnamedatum, $titularisId, $eigenaarId);
  }

  /**
   * Show message when task is complete.
   * @param CRM_Queue_TaskContext $ctx
   */
  public static function onEnd(CRM_Queue_TaskContext $ctx) {
    CRM_Core_Session::setStatus('De overname is uitgevoerd. Controleer het oude en nieuwe contact. U ziet nu het oude contact.', 'Apotheekovername', 'success');
  }

  /**
   * Voer een overname uit.
   * @param int $apotheekId Apotheekuitbating (contact id)
   * @param int $overnamedatum Overnamedatum (timestamp)
   * @param int $titularisId Nieuwe titularis (contact id)
   * @param int $eigenaarId Nieuwe eigenaar (contact id)
   * @return bool Success
   * @throws CRM_KavaOvername_Exception On error
   */
  public function doOvername($apotheekId, $overnamedatum, $titularisId, $eigenaarId) {

    $this->transaction = new CRM_Core_Transaction;
    $cf = CRM_KavaOvername_CustomField::singleton();

    // -- 1. Copy apotheekuitbating to a new contact, including all basic and custom fields

    //  - 1a. Get all field names
    $fields = $this->api('Contact', 'getfields', [
      'options' => ['limit' => 0],
    ]);
    $fieldnames = [];
    foreach ($fields as $field) {
      $fieldnames[] = $field['name'];
    }

    //  - 1b. Get contact with all fields
    $contact = $this->api('Contact', 'getsingle', [
      'id'     => $apotheekId,
      'return' => implode(',', $fieldnames),
    ]);

    //  - 1c. Create new contact
    unset($contact['id']);
    unset($contact['contact_id']);
    foreach($contact as $k => $v) {
      if(empty($v) || strpos($k, '_id') !== false) {
        unset($contact[$k]);
      }
    }
    $newApotheekId = $this->api('Contact', 'create', $contact);

    //  - 1d. Rename old contact
    $this->api('Contact', 'create', [
      'id'                => $apotheekId,
      'organization_name' => $contact['organization_name'] . ' (oud)',
    ]);


    // -- 3. Update new contact information and relationships

    //  - 2a. Update overname count for new contact
    $overnameCountField = $cf->getApiFieldName('contact_apotheekuitbating', 'Overname');
    $newOvernameCount = (int) $contact[$overnameCountField] + 1;
    $this->api('Contact', 'create', [
      'id'                => $newApotheekId,
      $overnameCountField => $newOvernameCount,
    ]);

    //  - 2b. Create new titularis relationship
    $titularisRelationshipType = $this->api('RelationshipType', 'getsingle', [
      'name_a_b' => 'heeft als titularis',
      'return' => 'id',
    ]);
    $this->api('Relationship', 'create', [
      'contact_id_a' => $newApotheekId,
      'contact_id_b' => $titularisId,
      'relationship_type_id' => $titularisRelationshipType['id'],
      'start_date' => $overnamedatum,
    ]);

    //  - 2c. Create new eigenaar relationship
    $eigenaarRelationshipType = $this->api('RelationshipType', 'getsingle', [
      'name_a_b' => 'heeft als eigenaar',
      'return' => 'id',
    ]);
    $this->api('Relationship', 'create', [
      'contact_id_a' => $newApotheekId,
      'contact_id_b' => $eigenaarId,
      'relationship_type_id' => $eigenaarRelationshipType['id'],
      'start_date' => $overnamedatum,
    ]);


    // -- 3. Copy other basic data to new contact

    //  - 3a. Copy addresses
    $addresses = $this->api('Address', 'get', [
      'contact_id' => $apotheekId,
      'options' => ['limit' => 0],
    ]);
    if(count($addresses) > 0) {
      foreach($addresses as $address) {
        unset($address['id']);
        $address['contact_id'] = $newApotheekId;

        $this->api('Address', 'create', $address);
      }
    }

    //  - 3b. Copy phone numbers
    $phonenos = $this->api('Phone', 'get', [
      'contact_id' => $apotheekId,
      'options' => ['limit' => 0],
    ]);
    if(count($phonenos) > 0) {
      foreach($phonenos as $phoneno) {
        unset($phoneno['id']);
        $phoneno['contact_id'] = $newApotheekId;

        $this->api('Phone', 'create', $phoneno);
      }
    }

    //  - 3c. Copy email addresses
    $emailaddrs = $this->api('Email', 'get', [
      'contact_id' => $apotheekId,
      'options' => ['limit' => 0],
    ]);
    if(count($emailaddrs) > 0) {
      foreach($emailaddrs as $emailaddr) {
        unset($emailaddr['id']);
        $emailaddr['contact_id'] = $newApotheekId;

        $this->api('Email', 'create', $emailaddr);
      }
    }


    // -- 4. Migrate other relationships to new contact

    //  - 4a. Fetch active relationships (A -> B + B -> A)
    $currentRelationshipsA = $this->api('Relationship', 'get', [
      'contact_id_a' => $apotheekId,
      'is_active' => 1,
      'options' => ['limit' => 0],
    ]);
    $currentRelationshipsB = $this->api('Relationship', 'get', [
      'contact_id_b' => $apotheekId,
      'is_active' => 1,
      'options' => ['limit' => 0],
    ]);

    //  - 4b. Walk all relationships, add them to new contact, and end them for old contact
    $currentRelationships = array_merge($currentRelationshipsA, $currentRelationshipsB);
    foreach($currentRelationships as $relationship) {

      if($relationship['relationship_type_id'] == $titularisRelationshipType['id'] ||
         $relationship['relationship_type_id'] == $eigenaarRelationshipType['id']) {
        // Don't copy titularis + eigenaar
      } else {
        // Copy all other relationships

        $newRelationship = $relationship;
        unset($newRelationship['id']);
        $newRelationship['start_date'] = $overnamedatum;

        if($newRelationship['contact_id_a'] == $apotheekId) {
          $newRelationship['contact_id_a'] = $newApotheekId;
        } elseif($newRelationship['contact_id_b'] == $apotheekId) {
          $newRelationship['contact_id_b'] = $newApotheekId;
        }

        // Create new relationship
        $this->api('Relationship', 'create', $newRelationship);
      }

      // End old relationship
      $this->api('Relationship', 'create', [
        'id' => $relationship['id'],
        'end_date' => $overnamedatum,
        'is_active' => 0,
      ]);
    }


    // -- 5. Migrate memberships to new contact

    //  - 5a. Fetch active memberships
    $currentMemberships = $this->api('Membership', 'get', [
      'contact_id' => $apotheekId,
      'status_id' => ['IN' => ['New', 'Current', 'Grace']],
      'options' => ['limit' => 0],
    ]);

    //  - 5b. Fetch membership type ids for special cases
    $volmachtApoMembershipType = $this->api('MembershipType', 'getsingle', [
      'name' => 'Volmacht apo mbt privacy commissie',
    ]);
    $apotheekkrantMembershipType = $this->api('MembershipType', 'getsingle', [
      'name' => 'Apotheekkrant',
    ]);

    //  - 5b. Walk all memberships, add them to new contact, and add them for old contact
    foreach($currentMemberships as $membership) {

      if($membership['membership_type_id'] == $volmachtApoMembershipType['id']) {
        // Don't migrate this membership type
      } else {
        // Copy all other memberships

        $newMembership = $membership;
        unset($newMembership['id']);
        $newMembership['contact_id'] = $newApotheekId;

        // Membership start date is original start date for Apotheekkrant, overnamedatum for all others
        if($membership['membership_type_id'] == $apotheekkrantMembershipType['id']) {
          $newMembership['start_date'] = $membership['start_date'];
        } else {
          $newMembership['start_date'] = $overnamedatum;
        }

        // Add membership for new contact
        $this->api('Membership', 'create', $newMembership);
      }

      // End membership for old contact
      $this->api('Membership', 'create', [
        'id' => $membership['id'],
        'end_date' => $overnamedatum,
      ]);
    }


    // -- 6. That's all!
    //    Add link to old and new contact to status message -> doesn't seem to work...
    // throw new CRM_KavaOvername_Exception('Execution interrupted during development');

    $this->transaction->commit();
    return TRUE;
  }

  /**
   * Wrapper function to call the API.
   * @param string $entity
   * @param string $action
   * @param array $params
   * @return array
   * @throws CRM_KavaOvername_Exception
   */
  private function api($entity, $action, $params = []) {
    try {
      // echo "DEBUG API CALL: " . print_r(func_get_args(), true) . "<br>\n";
      $res = civicrm_api3($entity, $action, $params);
      if ($res['is_error']) {
        $this->transaction->rollback();
        throw new CRM_KavaOvername_Exception("API Error ({$entity}.{$action}): " . $res['error_message']);
      }
      if($action == 'create' && !empty($res['id'])) {
        return $res['id'];
      }
      elseif (isset($res['values']) && is_array($res['values'])) {
        return $res['values'];
      } else {
        return $res;
      }
    } catch (\CiviCRM_API3_Exception $e) {
      $this->transaction->rollback();
      throw new CRM_KavaOvername_Exception("API Exception ({$entity}.{$action} " . print_r($params, true) . ": " . $e->getMessage());
    }
  }

}
