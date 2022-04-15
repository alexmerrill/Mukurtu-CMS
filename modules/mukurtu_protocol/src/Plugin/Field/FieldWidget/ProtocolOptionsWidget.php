<?php

namespace Drupal\mukurtu_protocol\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'protocol_options' widget.
 *
 * @FieldWidget(
 *   id = "protocol_options",
 *   label = @Translation("Cultural Protocol Options Select"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class ProtocolOptionsWidget extends OptionsWidgetBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getSelectedOptions(FieldItemListInterface $items) {
    // We need to check against a flat list of options.
    $options = $this->getOptions($items->getEntity());

    $flat_options = [];
    foreach ($options as $community) {
      foreach ($community['#options'] as $id => $protocol) {
        $flat_options[$id] = $id;
      }
    }

    $selected_options = [];
    foreach ($items as $item) {
      $value = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$value])) {
        $selected_options[] = $value;
      }
    }

    return $selected_options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\mukurtu_protocol\Entity\ProtocolControlInterface $entity */
    $entity = $items->getEntity();
    $options = $this->getOptions($entity);
    $selected = $this->getSelectedOptions($items);

    // Build protocol selection.
    $communities = [];
    $communities['description'] = [
      '#type' => 'item',
      '#description' => $this->getFilteredDescription(),
    ];
    $delta = 0;

    foreach ($options as $communityID => $communityOption) {
      // Build the community headers.
      $communities[$delta] = [
        '#type' => 'item',
        '#title' => $communityOption['#title'],
      ];

      foreach ($communityOption['#options'] as $id => $protocolOption) {
        // If a protocol is in multiple communities, show the
        // communities in the checkbox description.
        $description = "";
        if (count($protocolOption['#communities']) > 1) {
          $description = implode(', ', $protocolOption['#communities']);
        }

        // Build the protocol checkboxes.
        $communities[$delta]['protocols'][$id] = [
          '#type' => 'checkbox',
          '#title' => $protocolOption['#title'],
          '#description' => $description,
          '#return_value' => $id,
          '#default_value' => in_array($id, $selected),
          '#ajax' => [
            'disable-refocus' => TRUE,
            'callback' => [get_called_class(), 'updateProtocolSummaryCallback'],
            'wrapper' => 'protocol-summary',
          ],
        ];
      }

      $delta++;
    }

    // Build the initial protocol summary.
    // This shows the currently selected protocols.
    $summary = "";
    $protocolIDs = $entity->getProtocols();
    if (!empty($protocolIDs)) {
      $protocols = $this->entityTypeManager->getStorage('protocol')->loadMultiple($protocolIDs);
      $summary = self::buildProtocolSummaryLabel($protocols);
    }

    return $element += [
      'protocols' => [
        '#type' => 'details',
        '#title' => ['#markup' => $this->t('Cultural Protocol Selection') . '<div id="protocol-summary">' . $summary . '</div>'],
        '#open' => $entity->isNew(),
        'communities' => $communities,
      ],
    ];
  }

  /**
   * Ajax callback to update the protocol summary.
   */
  public static function updateProtocolSummaryCallback(array &$form, FormStateInterface $form_state) {
    $protocols = [];
    $pc = $form_state->getValue('field_protocol_control');
    if ($pc && isset($pc[0])) {
      // Build the list of currently selected protocol IDs from the form.
      foreach ($pc[0]['field_protocols'] as $key => $value) {
        if (is_numeric($key)) {
          $protocols[] = $value['target_id'];
        }
      }
    }

    // Load the protocols and capture their names.
    $names = [];
    if (!empty($protocols)) {
      $protocols = \Drupal::entityTypeManager()->getStorage('protocol')->loadMultiple($protocols);
      $names = array_map(fn($e) => "<em>{$e->getName()}</em>", $protocols);
    }
    $summary = implode(', ', $names);
    $summary = self::buildProtocolSummaryLabel($protocols);

    return ['#markup' => '<div id="protocol-summary">' . $summary . '</div>'];
  }

  /**
   * Build a protocol summary label.
   *
   * @param Drupal\mukurtu_protocol\Entity\ProtocolInterface[] $protocols
   *   The protocols.
   *
   * @return string
   *   The return markup.
   */
  public static function buildProtocolSummaryLabel(array $protocols) {
    $summary = "";
    if (!empty($protocols)) {
      $names = array_map(fn($e) => "<em>{$e->getName()}</em>", $protocols);
      $summary = implode(', ', $names);
    }
    return '<div id="protocol-summary">' . $summary . '</div>';
  }

  /**
   * {@inheritDoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      // Limit the settable options for the current user account.
      $provider = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getOptionsProvider($this->column, $entity);

      $values = $provider->getSettableValues();

      /** @var \Drupal\mukurtu_protocol\Entity\ProtocolInterface[] $protocols */
      $protocols = $this->entityTypeManager->getStorage('protocol')->loadMultiple($values);

      $multipleCommunities['#title'] = $this->t('Multiple Communities');
      $multipleCommunities['#id'] = 'protocols-with-multiple-communities';
      foreach ($protocols as $protocol) {
        $communities = $protocol->getCommunities();
        $communityNames = array_map(fn($e) => $e->getName(), $communities);

        if (count($communities) > 1) {
          $multipleCommunities['#options'][$protocol->id()]['#title'] = $protocol->getName();
          $multipleCommunities['#options'][$protocol->id()]['#communities'] = $communityNames;
        }
        else {
          $community = reset($communities);
          $options[$community->id()]['#title'] = $community->getName();
          $options[$community->id()]['#id'] = $community->id();
          $options[$community->id()]['#options'][$protocol->id()]['#title'] = $protocol->getName();
          $options[$community->id()]['#options'][$protocol->id()]['#communities'] = $communityNames;
        }
      }

      // Put multiple communities at the bottom of the list.
      $options['multiple'] = $multipleCommunities;

      $module_handler = \Drupal::moduleHandler();
      $context = [
        'fieldDefinition' => $this->fieldDefinition,
        'entity' => $entity,
      ];
      $module_handler->alter('options_list', $options, $context);

      array_walk_recursive($options, [$this, 'sanitizeLabel']);

      $this->options = $options;
    }
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $values = [];
    $ids = [];
    foreach ($element['protocols']['communities'] as $community) {
      if (!empty($community['protocols'])) {
        foreach ($community['protocols'] as $id => $protocol) {
          if (is_numeric($id) && $protocol['#value'] && !isset($ids[$protocol['#value']])) {
            $values[]['target_id'] = $protocol['#value'];

            // Track IDs for dedup.
            $ids[$protocol['#value']] = $protocol['#value'];
          }
        }
      }
    }

    if ($element['#required'] && empty($values)) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    $form_state->setValueForElement($element, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return t('N/A');
    }
  }

}
