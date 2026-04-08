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
use Drupal\gpc\ComponentListBuilder;
use Drupal\gpc\Form\ComponentForm;
use Drupal\gpc\Routing\ComponentHtmlRouteProvider;

/**
 * Defines the component entity class.
 */
#[ContentEntityType(
  id: 'gpc_component',
  label: new TranslatableMarkup('Component'),
  label_collection: new TranslatableMarkup('Components'),
  label_singular: new TranslatableMarkup('component'),
  label_plural: new TranslatableMarkup('components'),
  label_count: [
    'singular' => '@count component',
    'plural' => '@count components',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'list_builder' => ComponentListBuilder::class,
    'form' => [
      'add' => ComponentForm::class,
      'edit' => ComponentForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'default' => ComponentForm::class,
    ],
    'route_provider' => [
      'html' => ComponentHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/gpc/components',
    'add-form' => '/admin/content/gpc/components/add',
    'edit-form' => '/admin/content/gpc/components/{gpc_component}/edit',
    'delete-form' => '/admin/content/gpc/components/{gpc_component}/delete',
  ],
  admin_permission: 'administer gpc components',
  base_table: 'gpc_component',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  translatable: FALSE,
)]
class Component extends ContentEntityBase implements EntityChangedInterface {

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
      ->setDescription(t('The internal numeric ID for this component record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The human-readable name of the component definition.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('A unique immutable internal identifier.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE);

    $fields['component_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Component type'))
      ->setDescription(t('The broad component category used for practical filtering and recipe use.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => static::componentTypeOptions(),
      ]);

    $fields['manufacturer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Manufacturer'))
      ->setDescription(t('Optional manufacturer or brand.'))
      ->setSetting('max_length', 255);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Optional notes for this component.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that this component was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that this component was last updated.'));

    return $fields;
  }

  /**
   * Returns the allowed component type values.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The allowed values keyed by machine name.
   */
  public static function componentTypeOptions(): array {
    return [
      'bullet' => t('Bullet'),
      'powder' => t('Powder'),
      'primer' => t('Primer'),
      'brass' => t('Brass'),
      'other' => t('Other'),
    ];
  }

}
