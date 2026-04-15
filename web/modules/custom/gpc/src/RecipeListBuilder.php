<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the recipe listing page.
 */
class RecipeListBuilder extends EntityListBuilder {

  /**
   * Constructs a recipe list builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, protected DateFormatterInterface $dateFormatter, protected AccountProxyInterface $currentUser) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Recipe code'),
      'nickname' => $this->t('Nickname'),
      'caliber' => $this->t('Caliber'),
      'bullet_component' => $this->t('Bullet'),
      'powder_component' => $this->t('Powder'),
      'powder_charge_weight' => $this->t('Charge'),
      'overall_length' => $this->t('OAL'),
      'estimated_round_cost' => $this->t('Cost'),
      'changed' => $this->t('Changed'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'] = Link::fromTextAndUrl($this->buildDisplayLabel($entity), $entity->toUrl('edit-form'))->toRenderable();
    $row['nickname'] = $entity->get('nickname')->value ?? '';
    $row['caliber'] = $entity->get('caliber')->first()?->entity?->label() ?? '';
    $row['bullet_component'] = $entity->get('bullet_component')->first()?->entity?->label() ?? '';
    $row['powder_component'] = $entity->get('powder_component')->first()?->entity?->label() ?? '';
    $row['powder_charge_weight'] = $entity->get('powder_charge_weight')->value ?? '';
    $row['overall_length'] = $entity->get('overall_length')->value ?? '';
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

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery(): QueryInterface {
    $query = parent::getEntityListQuery();

    if (!$this->currentUser->hasPermission('administer gpc recipes')) {
      $query->condition('uid', $this->currentUser->id());
    }

    return $query;
  }

  /**
   * Builds the preferred recipe display label.
   */
  protected function buildDisplayLabel(EntityInterface $entity): string {
    $recipe_code = trim((string) $entity->label());
    $nickname = trim((string) ($entity->get('nickname')->value ?? ''));

    if ($recipe_code === '') {
      return $nickname !== '' ? $nickname : '';
    }

    if ($nickname === '') {
      return $recipe_code;
    }

    return $recipe_code . ' (' . $nickname . ')';
  }

}
