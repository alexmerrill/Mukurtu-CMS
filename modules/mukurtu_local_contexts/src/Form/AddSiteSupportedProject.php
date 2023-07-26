<?php

namespace Drupal\mukurtu_local_contexts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mukurtu_local_contexts\LocalContextsProject;
use Drupal\mukurtu_local_contexts\LocalContextsSupportedProjectManager;

/**
 * Provides a Local Contexts form.
 */
class AddSiteSupportedProject extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mukurtu_local_contexts_add_site_supported_project';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Project'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (mb_strlen($form_state->getValue('project_id')) != 36) {
      $form_state->setErrorByName('name', $this->t('ID must be in valid UUID format'));
      return;
    }

    $id = mb_strtolower($form_state->getValue('project_id'));
    $project = new LocalContextsProject($id);
    if (!$project->isValid()) {
      $form_state->setErrorByName('name', $this->t('Could not find the project ID on the Local Contexts Hub.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $supportedProjectManager = new LocalContextsSupportedProjectManager();

    $id = mb_strtolower($form_state->getValue('project_id'));
    $supportedProjectManager->addSiteProject($id);

    $this->messenger()->addStatus($this->t('The project has been added.'));
    $form_state->setRedirect('mukurtu_local_contexts.manage_site_supported_projects');
  }

}
