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
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\gpc\FirearmAccessControlHandler;
use Drupal\gpc\FirearmListBuilder;
use Drupal\gpc\Form\FirearmForm;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

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
    'plural'   => '@count firearms',
  ],
  handlers: [
    'access'         => FirearmAccessControlHandler::class,
    'list_builder'   => FirearmListBuilder::class,
    'form'           => [
      'add'     => FirearmForm::class,
      'edit'    => FirearmForm::class,
      'delete'  => ContentEntityDeleteForm::class,
      'default' => FirearmForm::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'canonical'   => '/gpc/firearms/{gpc_firearm}',
    'collection'  => '/gpc/firearms',
    'add-form'    => '/gpc/firearms/add',
    'edit-form'   => '/gpc/firearms/{gpc_firearm}/edit',
    'delete-form' => '/gpc/firearms/{gpc_firearm}/delete',
  ],
  collection_permission: 'view gpc firearms',
  admin_permission: 'administer gpc firearms',
  base_table: 'gpc_firearm',
  entity_keys: [
    'id'    => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'uuid'  => 'uuid',
  ],
  translatable: FALSE,
)]
class Firearm extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void
  {
    parent::preSave($storage);

    $request_time = \Drupal::time()->getRequestTime();

    if ($this->isNew() && $this->get('uid')->isEmpty()) {
      $this->set('uid', \Drupal::currentUser()->id());
    }

    if ($this->isNew() && $this->get('created')->isEmpty()) {
      $this->set('created', $request_time);
    }

    $this->setChangedTime($request_time);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
  {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('integer')
                                                             ->setLabel(t('ID'))
                                                             ->setDescription(t('The internal numeric ID for this firearm record.'))
                                                             ->setReadOnly(TRUE)
                                                             ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
                                          ->setLabel(t('Firearm name'))
                                          ->setDescription(t('The display name of the firearm. Use manufacturer and model where possible.'))
                                          ->setRequired(TRUE)
                                          ->setSetting('max_length', 255);

    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']
      ->setLabel(t('Owner'))
      ->setDescription(t('The user who owns this firearm.'));

    $fields['type'] = BaseFieldDefinition::create('list_string')
                                                 ->setLabel(t('Firearm type'))
                                                 ->setDescription(t('The type of firearm.'))
                                                 ->setSettings([
                                                                 'allowed_values' => static::firearmTypeOptions(),
                                                               ]);

    $fields['caliber'] = BaseFieldDefinition::create('entity_reference')
                                            ->setLabel(t('Caliber'))
                                            ->setDescription(t('The caliber associated with this firearm.'))
                                            ->setRequired(TRUE)
                                            ->setSetting('target_type', 'gpc_caliber');

    $fields['manufacturer'] = BaseFieldDefinition::create('string')
                                                 ->setLabel(t('Manufacturer'))
                                                 ->setDescription(t('Optional manufacturer or brand used in the firearm display label.'))
                                                 ->setSetting('max_length', 255);

    $fields['model'] = BaseFieldDefinition::create('string')
                                          ->setLabel(t('Model'))
                                          ->setDescription(t('Optional model or series name used in the firearm display label.'))
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

  /**
   * Returns the allowed firearm type values.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The allowed values keyed by machine name.
   */
  public static function firearmTypeOptions(): array
  {
    return [
      'pistol'       => t('Pistol'),
      'revolver'     => t('Revolver'),
      'rifle'        => t('Rifle'),
      'lever_action' => t('Lever Action'),
      'shotgun'      => t('Shotgun'),
    ];
  }

}
