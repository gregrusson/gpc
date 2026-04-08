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
use Drupal\gpc\Form\RecipeForm;
use Drupal\gpc\RecipeListBuilder;
use Drupal\gpc\Routing\RecipeHtmlRouteProvider;

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
    'access' => EntityAccessControlHandler::class,
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
    'collection' => '/admin/content/gpc/recipes',
    'add-form' => '/admin/content/gpc/recipes/add',
    'edit-form' => '/admin/content/gpc/recipes/{gpc_recipe}/edit',
    'delete-form' => '/admin/content/gpc/recipes/{gpc_recipe}/delete',
  ],
  admin_permission: 'administer gpc recipes',
  base_table: 'gpc_recipe',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  translatable: FALSE,
)]
class Recipe extends ContentEntityBase implements EntityChangedInterface {

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
      ->setDescription(t('The human-readable name of the recipe.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['caliber'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Caliber'))
      ->setDescription(t('The caliber this recipe is built around.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_caliber');

    $fields['bullet_component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bullet component'))
      ->setDescription(t('The bullet component used in this recipe.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_component');

    $fields['powder_component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Powder component'))
      ->setDescription(t('The powder component used in this recipe.'))
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
      ->setDescription(t('The primer component used in this recipe.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'gpc_component');

    $fields['brass_component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Brass component'))
      ->setDescription(t('Optional brass component used in this recipe.'))
      ->setSetting('target_type', 'gpc_component');

    $fields['overall_length'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Overall length'))
      ->setDescription(t('The cartridge overall length for this recipe.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 3);

    $fields['crimp'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Crimp'))
      ->setDescription(t('Optional plain-text description of the crimp setting.'))
      ->setSetting('max_length', 255);

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

}
