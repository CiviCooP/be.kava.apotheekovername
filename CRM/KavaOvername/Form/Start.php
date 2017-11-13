<?php

/**
 * Class CRM_KavaOvername_Form_Start.
 * Form to configure and start process.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package be.kava.apotheekovername
 * @license AGPL-3.0
 */
class CRM_KavaOvername_Form_Start extends CRM_Core_Form {

  /**
   * Add form fields.
   */
  public function buildQuickForm() {

    $this->addEntityRef('apotheek_id', 'Apotheekuitbating', [
      'api' => [ // Alleen contacttype Apotheekuitbating, meer checks in rules below
                 'params' => ['contact_sub_type' => 'apotheekuitbating'],
      ],
    ], TRUE);

    $this->add('date', 'overnamedatum', 'Overnamedatum',
      ['minYear' => date('Y'), 'maxYear' => date('Y') + 1], TRUE); // Basis date-field want 4.6

    $this->add('text', 'name', 'Nieuwe apotheeknaam', [], TRUE);

    $this->add('text', 'btw_no', 'Nieuw BTW-nummer', [], TRUE);

    $this->add('select', 'organisatievorm', 'Organisatievorm', $this->getOrganisatieVormen(), TRUE);

    $this->addEntityRef('titularis_id', 'Nieuwe titularis', [
      'api' => [ // Alleen contacttype Apotheker toegestaan
                 'params' => ['contact_sub_type' => 'apotheker'],
      ],
    ], TRUE);

    $this->addEntityRef('eigenaar_id', 'Nieuwe eigenaar', [], TRUE);

    $this->addEntityRef('td_id', 'Tariferingsdienst', [
      'api' => [ // Organisatie, want contact.getlist ondersteunt niet meerdere subtypen
                 'params' => ['contact_type' => 'Organization'],
      ],
    ], TRUE);

    $this->addButtons([
      [
        'type'      => 'submit',
        'name'      => ts('Overname uitvoeren'),
        'isDefault' => TRUE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Set default values.
   * @return array Defaults
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // Default overnamedatum is vandaag
    $defaults['overnamedatum'] = date('Y-m-d');

    // Default apotheekuitbating is $_GET['cid'] if set
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, NULL, 'GET');
    $defaults['apotheek_id'] = !empty($cid) ? $cid : NULL;
    $this->assign('apotheekId', $defaults['apotheek_id']);

    // Try to set default organisatievorm and tariferingsdienst
    $cf = CRM_KavaGeneric_CustomField::singleton();
    try {
      $organisatieVormField = $cf->getApiFieldName('contact_organisation', 'Organisatievorm');
      $contact = civicrm_api3('Contact', 'getsingle', [
        'id' => $defaults['apotheek_id'],
        'return' => $organisatieVormField,
      ]);
      $defaults['organisatievorm'] = $contact[$organisatieVormField];

      $relationshipType1 = $cf->getRelationshipTypeByAbName('heeft als tariferingsdienst');
      $relationshipType2 = $cf->getRelationshipTypeByAbName('heeft als tariferingsdienst (&lt;&gt; KAVA)');
      $relationship = civicrm_api3('Relationship', 'getsingle', [
        'contact_id_a' => $defaults['apotheek_id'],
        'relationship_type_id' => ['IN' => [$relationshipType1['id'], $relationshipType2['id']]],
      ]);
      $defaults['td_id'] = $relationship['contact_id_b'];

    } catch(CiviCRM_API3_Exception $e) {}

    return $defaults;
  }

  /**
   * Add custom validation function.
   */
  public function addRules() {
    $this->addFormRule(['CRM_KavaOvername_Form_Start', 'checkValid']);
  }

  /**
   * Get organisatievormen options, used in form builder above.
   * @return array
   */
  private function getOrganisatieVormen()
  {
    $cf = CRM_KavaGeneric_CustomField::singleton();
    $field = $cf->getField('contact_organisation', 'Organisatievorm');
    $options = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => $field['option_group_id'],
    ]);

    $retOptions = ['' => ' '];
    foreach($options['values'] as $option) {
      $retOptions[$option['value']] = $option['label'];
    }

    return $retOptions;
  }

  /**
   * Perform custom form validation: check date and contact types.
   * @param array $values Form values
   * @return array|bool True or array of errors
   */
  public static function checkValid($values) {
    $errors = [];

    // Check overnamedatum
    if (empty($values['overnamedatum']) || !is_array($values['overnamedatum'])) {
      $errors['overnamedatum'] = ts('Vul een overnamedatum in.');
    }

    // Check contact types
    if (!CRM_KavaOvername_Utils::hasContactSubType($values['apotheek_id'], 'apotheekuitbating')) {
      $errors['apotheek_id'] = ts('Contact is niet van het type Apotheekuitbating.');
    }
    if (!CRM_KavaOvername_Utils::hasContactSubType($values['titularis_id'], 'apotheker')) {
      $errors['titularis_id'] = ts('Contact is niet van het type Apotheker.');
    }
    if (!CRM_KavaOvername_Utils::contactIdExists($values['eigenaar_id'])) {
      $errors['eigenaar_id'] = ts('Contact bestaat niet.');
    }

    // Check of apotheek niet al overgenomen is
    if (!CRM_KavaOvername_Utils::canBeTakenOver($values['apotheek_id'])) {
      $errors['apotheek_id'] = ts('Deze apotheek is al eerder overgenomen. Alleen de apotheekuitbating met het hoogste overnamecijfer kan worden overgenomen.');
    }

    // Return errors, or true if no errors found
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process form: add overname to task queue and redirect to queue runner.
   */
  public function postProcess() {
    $values = $this->exportValues();

    $overnameDatumTimeStamp = strtotime($values['overnamedatum']['Y'] . '-' . $values['overnamedatum']['M'] . '-' . $values['overnamedatum']['d']);
    $values['overnamedatum'] = date('Ymd', $overnameDatumTimeStamp);
    $values['overnamedatum_end'] = date('Ymd', $overnameDatumTimeStamp - 86400);

    // Set variables
    $title = ts('Overname uitvoeren voor contact %1', [1 => $values['apotheek_id']]);
    $redirectUrl = CRM_Utils_System::url('civicrm/contact/view', ['cid' => $values['apotheek_id'], 'reset' => 1]);
    $taskData = [$values['apotheek_id'], $values['overnamedatum'], $values['overnamedatum_end'], $values['titularis_id'], $values['eigenaar_id'], $values['name'], $values['btw_no'], $values['organisatievorm'], $values['td_id']];

    // Add new task to queue
    $queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql', 'name' => 'be.kava.apotheekovername', 'reset' => TRUE,
    ]);
    $task = new CRM_Queue_Task(['CRM_KavaOvername_Task_OvernameTask', 'executeFromQueue'], $taskData, $title);
    $queue->createItem($task);

    // Create and redirect to queue runner
    $runner = new CRM_Queue_Runner([
      'title'     => ts('Overname uitvoeren'),
      'queue'     => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT, // Abort and keep task in queue
      'onEnd'     => ['CRM_KavaOvername_Task_OvernameTask', 'onEnd'],
      'onEndUrl'  => $redirectUrl,
    ]);
    $runner->runAllViaWeb();
  }

  /**
   * Get the fields/elements defined in this form.
   * @return array (string)
   */
  public function getRenderableElementNames() {
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
