<?php

namespace Drupal\mukurtu_import\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\mukurtu_import\MukurtuImportStrategyInterface;
use Drupal\user\UserInterface;
use Drupal\file\FileInterface;
use League\Csv\Reader;
use Exception;
/**
 * Defines the mukurtu_import_strategy entity type.
 *
 * @ConfigEntityType(
 *   id = "mukurtu_import_strategy",
 *   label = @Translation("Mukurtu Import Strategy"),
 *   label_collection = @Translation("mukurtu_import_strategies"),
 *   label_singular = @Translation("mukurtu_import_strategy"),
 *   label_plural = @Translation("mukurtu_import_strategies"),
 *   label_count = @PluralTranslation(
 *     singular = "@count mukurtu_import_strategy",
 *     plural = "@count mukurtu_import_strategies",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\mukurtu_import\MukurtuImportStrategyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mukurtu_import\Form\MukurtuImportStrategyForm",
 *       "edit" = "Drupal\mukurtu_import\Form\MukurtuImportStrategyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "mukurtu_import_strategy",
 *   admin_permission = "administer mukurtu_import_strategy",
 *   links = {
 *     "collection" = "/admin/structure/mukurtu-import-strategy",
 *     "add-form" = "/admin/structure/mukurtu-import-strategy/add",
 *     "edit-form" = "/admin/structure/mukurtu-import-strategy/{mukurtu_import_strategy}",
 *     "delete-form" = "/admin/structure/mukurtu-import-strategy/{mukurtu_import_strategy}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "uid",
 *     "label",
 *     "description",
 *     "target_entity_type_id",
 *     "target_bundle",
 *     "mapping",
 *   }
 * )
 */
class MukurtuImportStrategy extends ConfigEntityBase implements MukurtuImportStrategyInterface {

  /**
   * The mukurtu_import_strategy ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The mukurtu_import_strategy label.
   *
   * @var string
   */
  protected $label;

  /**
   * The mukurtu_import_strategy status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The mukurtu_import_strategy description.
   *
   * @var string
   */
  protected $description;

  protected $target_entity_type_id;
  protected $target_bundle;
  protected $uid;

  /**
   * [0] => Array
   *  (
   *     [target] => nid
   *     [source] => id
   *  )
   */
  protected $mapping;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  public function setTargetEntityTypeId($entity_type_id) {
    $this->target_entity_type_id = $entity_type_id;
  }

  public function getTargetEntityTypeId() {
    return $this->target_entity_type_id ?? 'node';
  }

  public function setTargetBundle($bundle) {
    $this->target_bundle = $bundle;
  }

  public function getTargetBundle() {
    return $this->target_bundle ?? NULL;
  }

  public function getMapping() {
    return $this->mapping ?? [];
  }

  public function setMapping($mapping) {
    $this->mapping = $mapping;
  }

  public function getLabel() {
    return $this->label;
  }

  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if (!$this->id()) {
      $this->id = $this->uuidGenerator()->generate();
    }

    if (!$this->uid) {
      $this->uid = 1;
    }
    parent::save();
  }

  public function getOwner() {
    return $this->entityTypeManager()->getStorage('user')->load($this->getOwnerId());
  }

  public function setOwner(UserInterface $account) {
    $this->uid = $account->id();
    return $this;
  }

  public function getOwnerId() {
    return $this->uid ?: 1;
  }

  public function setOwnerId($uid) {
    $this->uid = $uid;
    return $this;
  }

  public function applies(FileInterface $file) {
    $headers = $this->getCSVHeaders($file);
    $mapping = $this->getMapping();
    $diff = array_diff(array_column($mapping, 'source'), $headers);
    if (empty($diff)) {
      return TRUE;
    }
    return FALSE;
  }

  protected function getCSVHeaders(FileInterface $file) {
    try {
      $csv = Reader::createFromPath($file->getFileUri(), 'r');
    } catch (Exception $e) {
      return [];
    }
    $csv->setHeaderOffset(0);
    return $csv->getHeader();
  }

  protected function getDefinitionId(FileInterface $file) {
    return $this->getOwnerId() . "_" . $file->id() . "_" . $this->getTargetEntityTypeId() . "_" . $this->getTargetBundle();
  }

  protected function getFieldDefinitions($entity_type_id, $bundle = NULL) {
    if ($bundle) {
      return \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
    }
    return \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id);
  }

  protected function getProcess() {
    $entity_type_id = $this->getTargetEntityTypeId();
    $bundle = $this->getTargetBundle();
    $mapping = $this->getMapping();

    // Get the field definitions for the target.
    $fieldDefs = $this->getFieldDefinitions($entity_type_id, $bundle);

    // This will cause collisions if there are dupes. Do we care?
    $naiveProcess = array_combine(array_column($mapping, 'target'), array_column($mapping, 'source'));

    // Remove ignored mappings. This depends on the above dupe collision behavior.
    if (isset($naiveProcess['-1'])) {
      unset($naiveProcess['-1']);
    }

    // @todo Add process plugins as appropriate for the target field type.
    foreach ($naiveProcess as $target => $source) {
      $fieldDef = $fieldDefs[$target] ?? NULL;
      if (!$fieldDef) {
        continue;
      }
      //dpm("$target is {$fieldDef->getType()}");

      if ($fieldDef->getType() == 'entity_reference') {
        $refType = $fieldDef->getSetting('target_type');

        if ($refType == 'taxonomy_term') {
          $targetBundles = $fieldDef->getSetting('handler_settings')['target_bundles'] ?? [];
          $newSource = [];
          $newSource[] = [
            'plugin' => 'explode',
            'delimiter' => ';',
          ];
          $newSource[] = [
            'plugin' => 'entity_lookup',//$fieldDef->getSetting('handler_settings')['auto_create'] ? 'entity_generate' : 'entity_lookup',
            'source' => $source,
            'value_key' => 'name',
            'bundle_key' => 'vid',
            'bundle' => array_keys($targetBundles),
            'entity_type' => $fieldDef->getSetting('target_type'),
            'ignore_case' => TRUE,
            //'operator' => 'STARTS_WITH',
          ];
          $naiveProcess[$target] = $newSource;
        }
      }
    }
dpm($naiveProcess);
    return $naiveProcess;
  }

  /**
   * Get the fields that are allowed to be altered for existing entities.
   *
   * @return mixed
   */
  protected function getOverwriteProperties() {
    $entity_type_id = $this->getTargetEntityTypeId();
    $bundle = $this->getTargetBundle();
    $mapping = $this->getMapping();
    $targets = array_column($mapping, 'target');

    // Get the field definitions for the target.
    $fieldDefs = $this->getFieldDefinitions($entity_type_id, $bundle);

    $writableFields = [];
    foreach ($fieldDefs as $fieldName => $fieldDef) {
      if (in_array($fieldName,['default_langcode'])) {
        continue;
      }

      // Sometimes there are fields that are marked as writable but the migrate
      // system will fail to import if they are specified as overwritable. Here
      // we get around this by only specifying what is absolutely necessary for
      // the given import input.
      if (!$fieldDef->isReadOnly() && in_array($fieldName, $targets)) {
        $writableFields[] = $fieldName;
      }
    }

    return $writableFields;
  }

  /**
   * Generate a Migrate API definition for a given file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The import input file.
   * @return mixed
   *   The migration definition array
   */
  public function toDefinition(FileInterface $file) {
    $entity_type_id = $this->getTargetEntityTypeId();
    $bundle = $this->getTargetBundle();
    $definition = [
      'id' => $this->getDefinitionId($file),
      'migration_tags' => ['importtest'],
      'source' => [
        'plugin' => 'csv',
        'path' => $file->getFileUri(),
        //'ids' => ['id', 'title'],
        //'ids' => ['title'],
        'ids' => ['id'],
        //'ids' => ['uuid'],
        'track_changes' => TRUE,
      ],
      'process' => $this->getProcess(),
      'destination' => [
        'plugin' => "entity:$entity_type_id",
        'default_bundle' => $bundle,
        'overwrite_properties' => $this->getOverwriteProperties(),
        'validate' => TRUE,
      ],
    ];

    return $definition;
  }

  public function mappedFieldsCount(FileInterface $file) {
    $fileHeaders = $this->getCSVHeaders($file);
    $process = $this->getMapping();
    // Compare the import config to the headers.
    $mappingHeaders = array_column($process, 'source');
    $diff = array_diff($fileHeaders, $mappingHeaders);
    $mappedCount = count($fileHeaders) - count($diff);
    return $mappedCount;
  }

}