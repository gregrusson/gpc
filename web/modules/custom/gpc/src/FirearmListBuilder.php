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
use Drupal\gpc\Entity\Firearm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the firearm listing page.
 */
class FirearmListBuilder extends EntityListBuilder {

  /**
   * Constructs a firearm list builder.
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
      'label' => $this->t('Firearm'),
      'type' => $this->t('Type'),
      'caliber' => $this->t('Caliber'),
      'manufacturer' => $this->t('Manufacturer'),
      'model' => $this->t('Model'),
      'changed' => $this->t('Changed'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $display_label = $this->buildDisplayLabel($entity);
    $row['label']['data'] = Link::fromTextAndUrl($display_label, $entity->toUrl('edit-form'))->toRenderable();
    $type = $entity->get('type')->value ?? '';
    $row['type'] = Firearm::firearmTypeOptions()[$type] ?? $type;
    $row['caliber'] = $entity->get('caliber')->first()?->entity?->label() ?? '';
    $row['manufacturer'] = $entity->get('manufacturer')->value ?? '';
    $row['model'] = $entity->get('model')->value ?? '';
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
    return $this->t('Firearms');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery(): QueryInterface {
    $query = parent::getEntityListQuery();

    if (!$this->currentUser->hasPermission('administer gpc firearms')) {
      $query->condition('uid', $this->currentUser->id());
    }

    return $query;
  }

  /**
   * Builds the preferred firearm display label.
   */
  protected function buildDisplayLabel(EntityInterface $entity): string {
    $manufacturer = trim((string) ($entity->get('manufacturer')->value ?? ''));
    $model = trim((string) ($entity->get('model')->value ?? ''));
    $parts = array_values(array_filter([$manufacturer, $model], static fn (string $value): bool => $value !== ''));

    if ($parts !== []) {
      return implode(' ', $parts);
    }

    return (string) $entity->label();
  }

}
