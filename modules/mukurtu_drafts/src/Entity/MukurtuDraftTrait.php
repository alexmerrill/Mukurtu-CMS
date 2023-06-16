<?php

namespace Drupal\mukurtu_drafts\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides a trait for draft status.
 */
trait MukurtuDraftTrait
{

  /**
   * Returns an array of base field definitions for draft status.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to add the draft status field to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   */
  public static function draftBaseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    $field = [];
    $field['draft'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Draft'))
      ->setDescription(t('Mark this item as a draft.'))
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function isDraft()
  {
    return (bool) $this->get('draft');
  }

  /**
   * {@inheritdoc}
   */
  public function setDraft()
  {
    $this->set('draft', TRUE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetDraft()
  {
    $this->set('draft', FALSE);

    return $this;
  }
}
