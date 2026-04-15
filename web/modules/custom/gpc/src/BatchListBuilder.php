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
 * Builds the batch listing page.
 */
class BatchListBuilder extends EntityListBuilder {

  /**
   * Constructs a batch list builder.
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
      'label' => $this->t('Batch code'),
      'machine_name' => $this->t('Machine name'),
      'recipe' => $this->t('Recipe'),
      'batch_date' => $this->t('Date produced'),
      'quantity_produced' => $this->t('Quantity'),
      'changed' => $this->t('Updated'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'] = Link::fromTextAndUrl($this->buildDisplayLabel($entity), $entity->toUrl('edit-form'))->toRenderable();
    $row['machine_name'] = $entity->get('machine_name')->value ?? '';
    $row['recipe'] = $this->buildRecipeLabel($entity);
    $row['batch_date'] = $entity->get('batch_date')->value
      ? $this->dateFormatter->format((int) $entity->get('batch_date')->value, 'short')
      : '';
    $row['quantity_produced'] = $entity->get('quantity_produced')->value ?? '';
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
    return $this->t('Batches');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery(): QueryInterface {
    $query = parent::getEntityListQuery();

    if (!$this->currentUser->hasPermission('administer gpc batches')) {
      $query->condition('uid', $this->currentUser->id());
    }

    return $query;
  }

  /**
   * Builds the preferred batch display label.
   */
  protected function buildDisplayLabel(EntityInterface $entity): string {
    $recipe_label = $this->buildRecipeLabel($entity);
    $batch_code = trim((string) $entity->label());

    if ($recipe_label === '') {
      return $batch_code;
    }

    if ($batch_code === '') {
      return $recipe_label;
    }

    return $recipe_label . ' / ' . $batch_code;
  }

  /**
   * Builds the recipe display label used in batch listings.
   */
  protected function buildRecipeLabel(EntityInterface $entity): string {
    return $entity->get('recipe')->first()?->entity?->label() ?? '';
  }

}
