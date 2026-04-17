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
use Drupal\gpc\RecipeAccessControlHandler;
use Drupal\gpc\Form\RecipeForm;
use Drupal\gpc\RecipeListBuilder;
use Drupal\gpc\Routing\RecipeHtmlRouteProvider;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the recipe entity class.
 */
#[ContentEntityType(
  id: 'gpc_recipe',
  label: new TranslatableMarkup('Recipe'),
  label_collection: new TranslatableMarkup('Recipes'),
  label_singular: new TranslatableMarkup('recipe'),
  label_plural: new TranslatableMarkup('recipes'),
  label_count: [
    'singular' => '@count recipe',
    'plural' => '@count recipes',
  ],
  handlers: [
    'access' => RecipeAccessControlHandler::class,
    'list_builder' => RecipeListBuilder::class,
    'form' => [
      'add' => RecipeForm::class,
      'edit' => RecipeForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'default' => RecipeForm::class,
    ],
    'route_provider' => [
      'html' => RecipeHtmlRouteProvider::class,
    ],
  ],
  links: [
    'canonical' => '/recipes/{gpc_recipe}',
    'collection' => '/recipes',
    'add-form' => '/recipes/add',
    'edit-form' => '/recipes/{gpc_recipe}/edit',
    'delete-form' => '/recipes/{gpc_recipe}/delete',
  ],
  collection_permission: 'view gpc recipes',
  admin_permission: 'administer gpc recipes',
  base_table: 'gpc_recipe',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'uuid' => 'uuid',
  ],
  translatable: FALSE,
)]
class Recipe extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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

    $recipe_code = trim((string) ($this->get('label')->value ?? ''));
    if ($recipe_code !== '') {
      $this->set('label', $recipe_code);
    }

    $nickname = trim((string) ($this->get('nickname')->value ?? ''));
    $this->set('nickname', $nickname === '' ? NULL : $nickname);

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
      ->setDescription(t('The internal numeric ID for this recipe record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipe code'))
      ->setDescription(t('The primary human-facing identifier for the recipe.'))
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
      ->setDescription(t('The user who owns this recipe.'));

    $fields['nickname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nickname'))
      ->setDescription(t('Optional nickname or alternate label for this recipe.'))
      ->setSetting('max_length', 255);

    $fields['caliber'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Caliber'))
      ->setDescription(t('The caliber this recipe is built around.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_caliber');

    $fields['bullet_component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bullet component'))
      ->setDescription(t('The bullet component used in this recipe. The selected component should have type "bullet".'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_component');

    $fields['powder_component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Powder component'))
      ->setDescription(t('The powder component used in this recipe. The selected component should have type "powder".'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_component');

    $fields['powder_charge_weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Powder charge weight'))
      ->setDescription(t('The powder charge weight for the recipe.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 3);

    $fields['primer_component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Primer component'))
      ->setDescription(t('The primer component used in this recipe. The selected component should have type "primer".'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_component');

    $fields['brass_component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Brass component'))
      ->setDescription(t('Optional brass component used in this recipe. If set, the selected component should have type "brass".'))
      ->setSetting('target_type', 'gpc_component');

    $fields['overall_length'] = BaseFieldDefinition::create('physical_measurement')
      ->setLabel(t('Overall length'))
      ->setDescription(t('The cartridge overall length for this recipe. Enter inches or millimeters.'))
      ->setRequired(TRUE)
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

    $fields['crimp'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Crimped'))
      ->setDescription(t('Check this box if the recipe is crimped.'))
      ->setSettings([
        'on_label' => 'Yes',
        'off_label' => 'No',
      ])
      ->setDefaultValue(FALSE);

    $fields['estimated_round_cost'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Estimated round cost'))
      ->setDescription(t('Optional estimated cost per round for this recipe.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Optional notes for this recipe.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that this recipe was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that this recipe was last updated.'));

    return $fields;
  }

  /**
   * Returns the required component type for each recipe component field.
   *
   * @return array<string, string>
   *   Map of recipe field name to allowed component type.
   */
  public static function componentFieldTypes(): array {
    return [
      'bullet_component' => 'bullet',
      'powder_component' => 'powder',
      'primer_component' => 'primer',
      'brass_component' => 'brass',
    ];
  }

}
