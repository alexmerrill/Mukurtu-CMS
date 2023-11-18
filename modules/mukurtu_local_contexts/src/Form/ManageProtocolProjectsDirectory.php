<?php

namespace Drupal\mukurtu_local_contexts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to manage the protocol level local contexts projects directory.
 */
class ManageProtocolProjectsDirectory extends FormBase
{
  protected $protocolId;

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'mukurtu_local_contexts_manage_protocol_projects_directory';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group = NULL)
  {
    // In this context, group is the id of the group (in string form).
    $protocol = $group;
    if (!$protocol) {
      return $form;
    }
    $this->protocolId = $protocol;
    $element = 'protocol-projects-directory-' . $protocol;

    $description = $this->config('mukurtu_local_contexts.settings')->get('mukurtu_local_contexts_manage_protocol_' . $protocol . '_projects_directory_description') ?? '';
    $protocolName = \Drupal::entityTypeManager()->getStorage('protocol')->load(intval($protocol))->getName();
    $form['description'] = [
      '#title' => $this->t('Description'),
      '#description' => $this->t("Enter the description for " . $protocolName . "'s Local Contexts project directory page."),
      '#default_value' => $description,
      '#type' => 'textarea',
    ];

    $form[$element . '-submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $description = $form_state->getValue('description');
    $this->configFactory->getEditable('mukurtu_local_contexts.settings')->set('mukurtu_local_contexts_manage_protocol_' . $this->protocolId . '_projects_directory_description', $description)->save();
  }
}
