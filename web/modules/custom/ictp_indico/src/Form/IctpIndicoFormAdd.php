<?php

namespace Drupal\ictp_indico\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a form for add a event entity.
 *
 * @ingroup ictp_indico
 */
class IctpIndicoFormAdd extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'ictp_indico_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<p>Inserisci la data di inizio e fine per l\'importazione</p>';
    $form['start'] = [
      '#type' => 'date',
      '#title' => 'Start date',
      '#required' => TRUE,
      '#default_value' => 0,
    ];
    $form['end'] = [
      '#type' => 'date',
      '#title' => 'End date',
      '#required' => TRUE,
      '#default_value' => 0,
    ];

    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => 'Procedi',
      ),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $start = $form_state->getValue('start');
    $end = $form_state->getValue('end');
    $start = str_replace('-', '/', $start);
    $end = str_replace('-', '/', $end);
    $batch = [
      'title' => t('Import events from indico'),
      'operations' => [],
      'init_message' => t('Import events from indico is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.'),
    ];

    $category_indico = '2l132,2l131,2l130,2l133,10,11&start_date=' . $start . '&end_date=' . $end;
    $from = INDICO_URL . END_POINT . $category_indico;
    $indico_events = file_get_contents($from);
    $indico_array =  json_decode($indico_events, TRUE);
    $indico_items = $indico_array['results'];
    foreach ($indico_items as $item) {
      $batch['operations'][] = [
        ['\Drupal\ictp_indico\Controller\IctpIndicoController', 'archive'],
        [$item]
      ];
    }

    batch_set($batch);

    \Drupal::messenger()->addMessage(count($indico_items) . ' events imported from ' . $start . ' to ' .$end);
    $form_state->setRebuild(TRUE);
  }
}
