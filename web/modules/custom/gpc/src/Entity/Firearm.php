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
use Drupal\gpc\FirearmListBuilder;
use Drupal\gpc\Form\FirearmForm;
use Drupal\gpc\Routing\FirearmHtmlRouteProvider;

/**
 * Defines the firearm entity class.
 */
#[ContentEntityType(
  id: 'gpc_firearm',
  label: new TranslatableMarkup('Firearm'),
  label_collection: new TranslatableMarkup('Firearms'),
  label_singular: new TranslatableMarkup('firearm'),
  label_plural: new TranslatableMarkup('firearms'),
  label_count: [
    'singular' => '@count firearm',
    'plural' => '@count firearms',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'list_builder' => FirearmListBuilder::class,
    'form' => [
      'add' => FirearmForm::class,
      'edit' => FirearmForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'default' => FirearmForm::class,
    ],
    'route_provider' => [
      'html' => FirearmHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/gpc/firearms',
    'add-form' => '/admin/content/gpc/firearms/add',
    'edit-form' => '/admin/content/gpc/firearms/{gpc_firearm}/edit',
    'delete-form' => '/admin/content/gpc/firearms/{gpc_firearm}/delete',
  ],
  admin_permission: 'administer gpc firearms',
  base_table: 'gpc_firearm',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  translatable: FALSE,
)]
class Firearm extends ContentEntityBase implements EntityChangedInterface {

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
      ->setDescription(t('The internal numeric ID for this firearm record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The human-readable name of the firearm.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('A unique immutable internal identifier.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE);

    $fields['caliber'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Caliber'))
      ->setDescription(t('The caliber associated with this firearm.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_caliber');

    $fields['manufacturer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Manufacturer'))
      ->setDescription(t('Optional manufacturer or brand.'))
      ->setSetting('max_length', 255);

    $fields['model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model'))
      ->setDescription(t('Optional model or series name.'))
      ->setSetting('max_length', 255);

    $fields['serial_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Serial number'))
      ->setDescription(t('Optional serial number or identifying code.'))
      ->setSetting('max_length', 255);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Optional notes for this firearm.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that this firearm was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that this firearm was last updated.'));

    return $fields;
  }

}
