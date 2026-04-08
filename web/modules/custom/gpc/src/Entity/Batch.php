<?php

declare(strict_types=1);

namespace Drupal\gpc\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\gpc\BatchListBuilder;
use Drupal\gpc\Form\BatchForm;
use Drupal\gpc\Routing\BatchHtmlRouteProvider;

/**
 * Defines the batch entity class.
 */
#[ContentEntityType(
  id: 'gpc_batch',
  label: new TranslatableMarkup('Batch'),
  label_collection: new TranslatableMarkup('Batches'),
  label_singular: new TranslatableMarkup('batch'),
  label_plural: new TranslatableMarkup('batches'),
  label_count: [
    'singular' => '@count batch',
    'plural' => '@count batches',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'list_builder' => BatchListBuilder::class,
    'form' => [
      'add' => BatchForm::class,
      'edit' => BatchForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'default' => BatchForm::class,
    ],
    'route_provider' => [
      'html' => BatchHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/gpc/batches',
    'add-form' => '/admin/content/gpc/batches/add',
    'edit-form' => '/admin/content/gpc/batches/{gpc_batch}/edit',
    'delete-form' => '/admin/content/gpc/batches/{gpc_batch}/delete',
  ],
  admin_permission: 'administer gpc batches',
  base_table: 'gpc_batch',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  translatable: FALSE,
)]
class Batch extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    $request_time = \Drupal::time()->getRequestTime();

    if ($this->isNew() && $this->get('created')->isEmpty()) {
      $this->set('created', $request_time);
    }

    $this->setChangedTime($request_time);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('A unique machine name for internal references.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The human-readable name of the batch.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['recipe'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipe'))
      ->setDescription(t('The recipe used to produce this batch.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_recipe');

    $fields['batch_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date produced'))
      ->setDescription(t('The date this batch was produced.'))
      ->setRequired(TRUE);

    $fields['quantity_produced'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quantity produced'))
      ->setDescription(t('The number of rounds or units produced in this batch.'))
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Optional notes for this batch.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that this batch record was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that this batch record was last updated.'));

    return $fields;
  }

}
