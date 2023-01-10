<?php

namespace Drupal\mukurtu_import\Form;

use Drupal\mukurtu_import\Form\ImportBaseForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Provides a Mukurtu Import Results form.
 */
class ImportResultsForm extends ImportBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mukurtu_import_results';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->buildTable($form, $form_state, 'node');
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start a new import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->reset();
    $form_state->setRedirect('mukurtu_import.file_upload');
  }

  protected function buildTable(array &$form, FormStateInterface $form_state, $entity_type_id) {
    $message = $this->getImportRevisionMessage();

    $media_block = [
      '#type' => 'view',
      '#name' => 'mukurtu_import_results_media',
      '#display_id' => 'results',
      '#embed' => TRUE,
      '#arguments' => [$message->render()],
    ];
    $form['media_results'] = $media_block;


    $content_block = [
      '#type' => 'view',
      '#name' => 'mukurtu_import_results_content',
      '#display_id' => 'results',
      '#embed' => TRUE,
      '#arguments' => [$message->render()],
    ];
    $form['content_results'] = $content_block;
  }

}
