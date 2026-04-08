<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the recipe listing page.
 */
class RecipeListBuilder extends EntityListBuilder {

  /**
   * Constructs a recipe list builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, protected DateFormatterInterface $dateFormatter) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Title'),
      'caliber' => $this->t('Caliber'),
      'bullet_component' => $this->t('Bullet'),
      'powder_component' => $this->t('Powder'),
      'powder_charge_weight' => $this->t('Charge'),
      'estimated_round_cost' => $this->t('Cost'),
      'changed' => $this->t('Updated'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = Link::fromTextAndUrl((string) $entity->label(), $entity->toUrl('edit-form'))->toRenderable();
    $row['caliber'] = $entity->get('caliber')->first()?->entity?->label() ?? '';
    $row['bullet_component'] = $entity->get('bullet_component')->first()?->entity?->label() ?? '';
    $row['powder_component'] = $entity->get('powder_component')->first()?->entity?->label() ?? '';
    $row['powder_charge_weight'] = $entity->get('powder_charge_weight')->value ?? '';
    $row['estimated_round_cost'] = $entity->get('estimated_round_cost')->value ?? '';
    $row['changed'] = $entity->getChangedTime()
      ? $this->dateFormatter->format($entity->getChangedTime(), 'short')
      : '';
    $row['operations']['data'] = $this->buildOperations($entity);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t('Recipes');
  }

}
