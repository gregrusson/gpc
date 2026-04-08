<?php

declare(strict_types=1);

namespace Drupal\gpc\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\gpc\CaliberListBuilder;
use Drupal\gpc\Form\CaliberForm;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\gpc\Routing\CaliberHtmlRouteProvider;

/**
 * Defines the caliber entity class.
 */
#[ContentEntityType(
  id: 'gpc_caliber',
  label: new TranslatableMarkup('Caliber'),
  label_collection: new TranslatableMarkup('Calibers'),
  label_singular: new TranslatableMarkup('caliber'),
  label_plural: new TranslatableMarkup('calibers'),
  label_count: [
    'singular' => '@count caliber',
    'plural' => '@count calibers',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'list_builder' => CaliberListBuilder::class,
    'form' => [
      'add' => CaliberForm::class,
      'edit' => CaliberForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'default' => CaliberForm::class,
    ],
    'route_provider' => [
      'html' => CaliberHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/gpc/calibers',
    'add-form' => '/admin/content/gpc/calibers/add',
    'edit-form' => '/admin/content/gpc/calibers/{gpc_caliber}/edit',
    'delete-form' => '/admin/content/gpc/calibers/{gpc_caliber}/delete',
  ],
  admin_permission: 'administer gpc calibers',
  base_table: 'gpc_caliber',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  translatable: FALSE,
)]
class Caliber extends ContentEntityBase implements EntityChangedInterface {

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
    elseif (isset($this->original) && $this->original instanceof self) {
      $original_machine_name = $this->original->get('machine_name')->value ?? NULL;
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
      ->setDescription(t('The internal numeric ID for this caliber record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The human-readable name of the caliber.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('A unique immutable internal identifier.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Optional notes for this caliber.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that this caliber was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that this caliber was last updated.'));

    return $fields;
  }

}
