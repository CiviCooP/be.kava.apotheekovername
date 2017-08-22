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

    $this->addEntityRef('apotheek_id', 'Apotheekuitbating', [], TRUE);

    $this->add('date', 'overnamedatum', 'Overnamedatum',
      ['minYear' => date('Y'), 'maxYear' => date('Y') + 1], TRUE); // Basis date-field want 4.6

    $this->addEntityRef('titularis_id', 'Nieuwe titularis', ['create' => TRUE], TRUE);

    $this->addEntityRef('eigenaar_id', 'Nieuwe eigenaar', ['create' => TRUE], TRUE);

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
    $defaults['overnamedatum'] = date('Ymd');

    // Default apotheekuitbating is $_GET['cid'] if set
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, NULL, 'GET');
    $defaults['apotheek_id'] = !empty($cid) ? $cid : NULL;
    $this->assign('apotheekId', $defaults['apotheek_id']);

    return $defaults;
  }

  /**
   * Add custom validation function.
   */
  public function addRules() {
    $this->addFormRule(['CRM_KavaOvername_Form_Start', 'checkValid']);
  }

  /**
   * Perform custom form validation: check date and contact types.
   * @param array $values Form values
   * @return array|bool True or array of errors
   */
  public function checkValid($values) {
    $errors = [];

    // Check overnamedatum
    if(empty($values['overnamedatum']) || !is_array($values['overnamedatum'])) {
      $errors['overnamedatum'] = ts('Vul een overnamedatum in.');
    } else {
      $overnamedatum = strtotime($values['overnamedatum']['Y'] . '-' . $values['overnamedatum']['M'] . '-' . $values['overnamedatum']['d']);
      if(time() > ($overnamedatum + 86400)) {
        $errors['overnamedatum'] = ts('De overnamedatum moet in de toekomst liggen.');
      }
    }

    // Check contact types
    if(!CRM_KavaOvername_Utils::hasContactSubType($values['apotheek_id'], 'apotheekuitbating')) {
      $errors['apotheek_id'] = ts('Contact is niet van het type Apotheekuitbating.');
    }
    if(!CRM_KavaOvername_Utils::hasContactSubType($values['titularis_id'], 'apotheker')) {
      $errors['titularis_id'] = ts('Contact is niet van het type Apotheker.');
    }
    if(!CRM_KavaOvername_Utils::contactIdExists($values['eigenaar_id'])) {
      $errors['eigenaar_id'] = ts('Contact bestaat niet.');
    }

    // Return errors, or true if no errors found
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process form: add overname to task queue and redirect to queue runner.
   */
  public function postProcess() {
    $values = $this->exportValues();
    $values['overnamedatum'] = date('Ymd', strtotime($values['overnamedatum']['Y'] . '-' . $values['overnamedatum']['M'] . '-' . $values['overnamedatum']['d']));

    // Set variables
    $title = ts('Overname uitvoeren voor contact %1', [1 => $values['apotheek_id']]);
    $redirectUrl = CRM_Utils_System::url('civicrm/contact/view', ['cid' => $values['apotheek_id'], 'reset' => 1]);
    $taskData = [$values['apotheek_id'], $values['overnamedatum'], $values['titularis_id'], $values['eigenaar_id']];

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
