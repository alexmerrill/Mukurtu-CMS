<?php

namespace Drupal\mukurtu_rights\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\mukurtu_rights\LocalContextLabelInterface;

/**
 * Defines the LocalContextLabel entity.
 *
 * @ingroup lclabel
 *
 * @ContentEntityType(
 *   id = "lclabel",
 *   label = @Translation("Local Contexts Label"),
 *   base_table = "lclabel",
 *   entity_keys = {
 *     "id" = "lid",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class LocalContextLabel extends ContentEntityBase implements LocalContextLabelInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['lid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('LID'))
      ->setDescription(t("The labels's unique ID."))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The label UUID.'))
      ->setReadOnly(TRUE);

    $fields['project_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Project UUID'))
      ->setDescription(t('The unique ID of the owning project.'));

    $fields['project_title'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Project Title'))
      ->setDescription(t('The title of the owning project.'));

    $fields['name'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Label Title'))
      ->setDescription(t('The label title.'));

    $fields['label_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label Type'))
      ->setDescription(t('The label type.'));

    $fields['text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Label Text'))
      ->setDescription(t('The label text.'));

    $fields['image_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Label Image URL'))
      ->setDescription(t('The label image URL.'));

    $fields['community'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Community'))
      ->setDescription(t('The label community.'));

    $fields['hub_created'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Hub Creation Time'))
      ->setDescription(t('When the label was created on the Local Contexts Hub.'));

    $fields['hub_updated'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Hub Updated Time'))
      ->setDescription(t('When the label was last updated on the Local Contexts Hub.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp when the label was created locally.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp when the label was last changed locally.'));

    return $fields;
  }

}
