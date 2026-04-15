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
use Drupal\gpc\CaliberAccessControlHandler;
use Drupal\gpc\CaliberListBuilder;
use Drupal\gpc\Form\CaliberForm;

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
    'access' => CaliberAccessControlHandler::class,
    'list_builder' => CaliberListBuilder::class,
    'form' => [
      'add' => CaliberForm::class,
      'edit' => CaliberForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'default' => CaliberForm::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'canonical' => '/gpc/calibers/{gpc_caliber}',
    'collection' => '/admin/content/gpc/calibers',
    'add-form' => '/admin/content/gpc/calibers/add',
    'edit-form' => '/admin/content/gpc/calibers/{gpc_caliber}/edit',
    'delete-form' => '/admin/content/gpc/calibers/{gpc_caliber}/delete',
  ],
  collection_permission: 'view gpc calibers',
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
    if ($this->isNew() && $this->get('submitted_by')->isEmpty() && \Drupal::currentUser()->isAuthenticated()) {
      $this->set('submitted_by', \Drupal::currentUser()->id());
    }
    if ($this->isNew() && $this->get('review_status')->isEmpty()) {
      $this->set('review_status', \Drupal::currentUser()->hasPermission('review gpc calibers') || \Drupal::currentUser()->hasPermission('administer gpc calibers') ? 'approved' : 'pending');
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
      ->setDescription(t('The internal numeric ID for this caliber record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Caliber name'))
      ->setDescription(t('The human-readable display name of the caliber.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('A unique immutable internal identifier used for imports and integrations.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE);

    $fields['nickname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nickname'))
      ->setDescription(t('An optional abbreviation or shorthand for the caliber name.'))
      ->setSetting('max_length', 255);

    $fields['bullet_diameter'] = static::buildLengthMeasurementField(
      'Bullet diameter',
      'The bullet diameter. Enter inches or millimeters.'
    );

    $fields['case_length'] = static::buildLengthMeasurementField(
      'Case length',
      'The case length. Enter inches or millimeters.'
    );

    $fields['primer_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Primer type'))
      ->setDescription(t('The reloading primer family used by this caliber.'))
      ->setSettings([
        'allowed_values' => static::primerTypeOptions(),
      ]);

    $fields['neck_diameter'] = static::buildLengthMeasurementField(
      'Neck diameter',
      'The neck diameter. Enter inches or millimeters.'
    );

    $fields['shoulder_diameter'] = static::buildLengthMeasurementField(
      'Shoulder diameter',
      'The shoulder diameter. Enter inches or millimeters.'
    );

    $fields['base_diameter'] = static::buildLengthMeasurementField(
      'Base diameter',
      'The base diameter. Enter inches or millimeters.'
    );

    $fields['rim_diameter'] = static::buildLengthMeasurementField(
      'Rim diameter',
      'The rim diameter. Enter inches or millimeters.'
    );

    $fields['max_overall_length'] = static::buildLengthMeasurementField(
      'Max overall length',
      'The maximum overall length. Enter inches or millimeters.'
    );

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Optional notes for this caliber.'));

    $fields['submitted_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Submitted by'))
      ->setDescription(t('The user who submitted this shared record.'))
      ->setSetting('target_type', 'user');

    $fields['review_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Review status'))
      ->setDescription(t('Tracks whether the record has been reviewed.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSettings([
        'allowed_values' => static::reviewStatusOptions(),
      ]);

    $fields['review_notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Review notes'))
      ->setDescription(t('Internal moderation or correction notes.'));

    $fields['duplicate_of'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Duplicate of'))
      ->setDescription(t('If this record duplicates another caliber, point to the canonical record.'))
      ->setSetting('target_type', 'gpc_caliber');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that this caliber was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that this caliber was last updated.'));

    return $fields;
  }

  /**
   * Returns the allowed primer type values.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The allowed values keyed by machine name.
   */
  public static function primerTypeOptions(): array {
    return [
      'small_pistol' => t('Small pistol'),
      'small_pistol_magnum' => t('Small pistol magnum'),
      'small_rifle' => t('Small rifle'),
      'small_rifle_magnum' => t('Small rifle magnum'),
      'large_pistol' => t('Large pistol'),
      'large_pistol_magnum' => t('Large pistol magnum'),
      'large_rifle' => t('Large rifle'),
      'large_rifle_magnum' => t('Large rifle magnum'),
      'rimfire' => t('Rimfire'),
      'shotshell_209' => t('Shotshell / 209'),
    ];
  }

  /**
   * Returns the allowed review status values.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The allowed review states.
   */
  public static function reviewStatusOptions(): array {
    return [
      'pending' => t('Pending review'),
      'approved' => t('Approved'),
      'needs_work' => t('Needs correction'),
      'duplicate' => t('Duplicate'),
    ];
  }

  /**
   * Builds a length measurement field stored via Physical.
   */
  protected static function buildLengthMeasurementField(string $label, string $description): BaseFieldDefinition {
    return BaseFieldDefinition::create('physical_measurement')
      ->setLabel(t($label))
      ->setDescription(t($description))
      ->setSettings([
        'measurement_type' => 'length',
      ])
      ->setDisplayOptions('form', [
        'type' => 'physical_measurement_default',
        'settings' => [
          'default_unit' => 'in',
          'allow_unit_change' => TRUE,
          'available_units' => [
            'in',
            'mm',
          ],
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'physical_measurement_default',
        'settings' => [
          'output_unit' => 'in',
        ],
      ]);
  }

}
