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
   * @param int $apotheek_id
   * @param int $overnamedatum
   * @param int $titularis_id
   * @param int $eigenaar_id
   * @return bool
   */
  public static function executeFromQueue(CRM_Queue_TaskContext $ctx, $apotheek_id, $overnamedatum, $titularis_id, $eigenaar_id) {
    $instance = static::instance();
    $ctx->log->warning('Test naar task context log.');
    return $instance->doOvername($apotheek_id, $overnamedatum, $titularis_id, $eigenaar_id);
  }

  /**
   * Show message when task is complete.
   * @param CRM_Queue_TaskContext $ctx
   */
  public static function onEnd(CRM_Queue_TaskContext $ctx) {
    CRM_Core_Session::setStatus('Overname apotheek uitgevoerd.', '', 'success');
  }

  /**
   * Voer een overname uit.
   * @param int $apotheek_id Apotheekuitbating (contact id)
   * @param int $overnamedatum Overnamedatum (timestamp)
   * @param int $titularis_id Nieuwe titularis (contact id)
   * @param int $eigenaar_id Nieuwe eigenaar (contact id)
   * @return bool
   */
  public function doOvername($apotheek_id, $overnamedatum, $titularis_id, $eigenaar_id) {

    $transaction = new CRM_Core_Transaction;

    // TODO 1. Kopieer apotheekuitbating naar een nieuw contact, inclusief alle basisvelden

    // TODO 2. Verhoog overnamecijfer in gegevens met 1 en vul overnamedatum bij custom data in.

    // TODO 3. Maak nieuwe relationships titularis/eigenaar aan met de ingevulde contacten.

    // TODO 4. Maak evt overige actieve memberships/relationships aan bij nieuw contact per overnamedatum.
    // TODO (Excl titularis/eigenaar; volmacht apo enz niet overzetten; startdatum abonnement apotheekkrant behouden)

    // TODO 5. Beëindig alle memberships/relationships bij oude contact per overnamedatum.

    // TODO 6. Geef gebruiker gelegenheid bestaande/nieuwe contact te checken (add links to status message).

    $transaction->commit();
    return TRUE;
  }

}
