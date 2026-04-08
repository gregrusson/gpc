<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\gpc\Entity\Batch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the batch listing page.
 */
class BatchListBuilder extends EntityListBuilder {

  /**
   * Constructs a batch list builder.
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
    $row['label'] = Link::fromTextAndUrl((string) $entity->label(), $entity->toUrl('edit-form'))->toRenderable();
    $row['machine_name'] = $entity->get('machine_name')->value ?? '';
    $row['recipe'] = $entity->get('recipe')->first()?->entity?->label() ?? '';
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

}
