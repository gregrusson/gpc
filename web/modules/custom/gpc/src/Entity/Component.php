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
use Drupal\gpc\ComponentAccessControlHandler;
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
    'access' => ComponentAccessControlHandler::class,
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
    'canonical' => '/components/{gpc_component}',
    'collection' => '/admin/gpc/components',
    'add-page' => '/admin/gpc/components/add',
    'add-form' => '/admin/gpc/components/add/{component_type}',
    'edit-form' => '/admin/gpc/components/{gpc_component}/edit',
    'delete-form' => '/admin/gpc/components/{gpc_component}/delete',
  ],
  collection_permission: 'view gpc components',
  admin_permission: 'administer gpc components',
  base_table: 'gpc_component',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'bundle' => 'component_type',
    'uuid' => 'uuid',
  ],
  bundle_label: new TranslatableMarkup('Component type'),
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
    $bundle = $this->bundle();

    if ($bundle !== '' && ($this->get('component_type')->isEmpty() || $this->get('component_type')->value !== $bundle)) {
      $this->set('component_type', $bundle);
    }

    if ($this->isNew() && $this->get('created')->isEmpty()) {
      $this->set('created', $request_time);
    }
    if ($this->isNew() && $this->get('submitted_by')->isEmpty() && \Drupal::currentUser()->isAuthenticated()) {
      $this->set('submitted_by', \Drupal::currentUser()->id());
    }
    if ($this->isNew() && $this->get('review_status')->isEmpty()) {
      $this->set('review_status', \Drupal::currentUser()->hasPermission('review gpc components') || \Drupal::currentUser()->hasPermission('administer gpc components') ? 'approved' : 'pending');
    }
    elseif (($original = $this->getOriginal()) instanceof self) {
      $original_machine_name = $original->get('machine_name')->value ?? NULL;
      if ($original_machine_name !== NULL) {
        $this->set('machine_name', $original_machine_name);
      }
    }

    $upc = $this->get('upc')->value ?? NULL;
    if (is_string($upc) && $upc !== '') {
      $normalized_upc = static::normalizeUpc($upc);
      if ($normalized_upc !== $upc) {
        $this->set('upc', $normalized_upc);
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
      ->setLabel(t('Component name'))
      ->setDescription(t('The product or model name shown in admin screens and recipe references.'))
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
      ->setDescription(t('The broad component bundle used for filtering and recipe use.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
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

    $fields['weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Bullet weight'))
      ->setDescription(t('The bullet weight in grains.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 3);

    $fields['diameter'] = static::buildLengthMeasurementField(
      'Bullet diameter',
      'The bullet diameter. Enter inches or millimeters.'
    );

    $fields['length'] = static::buildLengthMeasurementField(
      'Bullet length',
      'The bullet length. Enter inches or millimeters.'
    );

    $fields['sectional_density'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Sectional density'))
      ->setDescription(t('The sectional density as a unitless decimal.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4);

    $fields['ballistic_coefficient_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Ballistic coefficient value'))
      ->setDescription(t('The ballistic coefficient numeric value associated with the selected drag model.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4);

    $fields['ballistic_coefficient_model'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Ballistic coefficient model'))
      ->setDescription(t('Select the drag model used by the ballistic coefficient value.'))
      ->setSettings([
        'allowed_values' => static::ballisticCoefficientModelOptions(),
      ]);

    $fields['primer_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Primer type'))
      ->setDescription(t('The primer family or format.'))
      ->setSettings([
        'allowed_values' => static::primerTypeOptions(),
      ]);

    $fields['case_length'] = static::buildLengthMeasurementField(
      'Case length',
      'The case length. Enter inches or millimeters.'
    );

    $fields['upc'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UPC'))
      ->setDescription(t('Optional UPC or similar product code. Store digits only so leading zeroes are preserved and searching stays reliable.'))
      ->setSetting('max_length', 14)
      ->setSetting('is_ascii', TRUE);

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
      ->setDescription(t('If this record duplicates another component, point to the canonical record.'))
      ->setSetting('target_type', 'gpc_component');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that this component was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that this component was last updated.'));

    return $fields;
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
   * Returns the allowed ballistic coefficient model values.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The allowed values keyed by the stored value.
   */
  public static function ballisticCoefficientModelOptions(): array {
    return [
      'G1' => t('G1'),
      'G7' => t('G7'),
    ];
  }

  /**
   * Normalizes a UPC-like identifier to digits only.
   */
  public static function normalizeUpc(string $upc): string {
    return preg_replace('/[\s-]+/', '', trim($upc)) ?? '';
  }

  /**
   * Returns the supported component bundle IDs.
   *
   * @return string[]
   *   The supported bundle IDs.
   */
  public static function supportedBundles(): array {
    return [
      'bullet',
      'powder',
      'primer',
      'brass',
    ];
  }

  /**
   * Returns the bundle labels keyed by bundle ID.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The supported bundle labels keyed by machine name.
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

  /**
   * Returns the label for a supported bundle ID.
   */
  public static function bundleLabel(string $bundle): string {
    return (string) (static::componentTypeOptions()[$bundle] ?? $bundle);
  }

  /**
   * Returns the allowed primer type values for primer bundles.
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

}
