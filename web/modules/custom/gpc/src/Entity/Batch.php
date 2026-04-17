<?php

declare(strict_types=1);

namespace Drupal\gpc\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\gpc\BatchAccessControlHandler;
use Drupal\gpc\BatchListBuilder;
use Drupal\gpc\Form\BatchForm;
use Drupal\gpc\Routing\BatchHtmlRouteProvider;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

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
    'access' => BatchAccessControlHandler::class,
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
    'canonical' => '/batches/{gpc_batch}',
    'collection' => '/batches',
    'add-form' => '/batches/add',
    'edit-form' => '/batches/{gpc_batch}/edit',
    'delete-form' => '/batches/{gpc_batch}/delete',
  ],
  collection_permission: 'view gpc batches',
  admin_permission: 'administer gpc batches',
  base_table: 'gpc_batch',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'uuid' => 'uuid',
  ],
  translatable: FALSE,
)]
class Batch extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    $request_time = \Drupal::time()->getRequestTime();

    if ($this->isNew() && $this->get('uid')->isEmpty()) {
      $this->set('uid', \Drupal::currentUser()->id());
    }

    $batch_code = trim((string) ($this->get('label')->value ?? ''));
    if ($batch_code !== '') {
      $this->set('label', $batch_code);
    }

    if ($this->isNew() && $this->get('created')->isEmpty()) {
      $this->set('created', $request_time);
    }
    elseif (($original = $this->getOriginal()) instanceof self) {
      $original_machine_name = $original->get('machine_name')->value ?? NULL;
      if ($original_machine_name !== NULL) {
        $this->set('machine_name', $original_machine_name);
      }
    }

    $this->setChangedTime($request_time);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The internal numeric ID for this batch record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Batch code'))
      ->setDescription(t('The primary human-facing identifier for the batch.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('A unique immutable internal identifier.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE);

    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']
      ->setLabel(t('Owner'))
      ->setDescription(t('The user who owns this batch.'));

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
