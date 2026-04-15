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
use Drupal\gpc\Entity\Component;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the component listing page.
 */
class ComponentListBuilder extends EntityListBuilder {

  /**
   * Constructs a component list builder.
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
      'label' => $this->t('Component name'),
      'machine_name' => $this->t('Machine name'),
      'component_type' => $this->t('Component type'),
      'manufacturer' => $this->t('Manufacturer'),
      'upc' => $this->t('UPC'),
      'review_status' => $this->t('Review status'),
      'submitted_by' => $this->t('Submitted by'),
      'duplicate_of' => $this->t('Duplicate of'),
      'weight' => $this->t('Bullet weight'),
      'diameter' => $this->t('Bullet diameter'),
      'length' => $this->t('Bullet length'),
      'case_length' => $this->t('Case length'),
      'sectional_density' => $this->t('Sectional density'),
      'ballistic_coefficient_value' => $this->t('Ballistic coefficient value'),
      'ballistic_coefficient_model' => $this->t('Ballistic coefficient model'),
      'changed' => $this->t('Updated'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'] = Link::fromTextAndUrl((string) $entity->label(), $entity->toUrl('edit-form'))->toRenderable();
    $row['machine_name'] = $entity->get('machine_name')->value ?? '';
    $bundle = $entity->bundle();
    $row['component_type'] = $bundle ? Component::bundleLabel($bundle) : '';
    $row['manufacturer'] = $entity->get('manufacturer')->value ?? '';
    $row['upc'] = $entity->get('upc')->value ?? '';
    $row['review_status'] = Component::reviewStatusOptions()[$entity->get('review_status')->value ?? ''] ?? '';
    $row['submitted_by'] = $this->formatUserReference($entity->get('submitted_by')->target_id ?? NULL);
    $row['duplicate_of'] = $this->formatDuplicateReference($entity->get('duplicate_of')->target_id ?? NULL, 'gpc_component');
    $row['weight'] = $entity->get('weight')->value ?? '';
    $row['diameter'] = $this->formatMeasurement($entity, 'diameter');
    $row['length'] = $this->formatMeasurement($entity, 'length');
    $row['case_length'] = $this->formatMeasurement($entity, 'case_length');
    $row['sectional_density'] = $entity->get('sectional_density')->value ?? '';
    $row['ballistic_coefficient_value'] = $entity->get('ballistic_coefficient_value')->value ?? '';
    $row['ballistic_coefficient_model'] = $entity->get('ballistic_coefficient_model')->value ?? '';
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
    return $this->t('Components');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery(): QueryInterface {
    $query = parent::getEntityListQuery();
    $review_state = \Drupal::request()->query->get('review_state');

    if ($review_state === 'queue') {
      $query->condition('review_status', 'approved', '<>');
    }
    elseif (in_array($review_state, array_keys(Component::reviewStatusOptions()), TRUE)) {
      $query->condition('review_status', $review_state);
    }

    return $query;
  }

  /**
   * Formats a physical measurement for table output.
   */
  protected function formatMeasurement(EntityInterface $entity, string $field_name): string {
    $item = $entity->get($field_name)->first();
    if ($item === NULL || $item->isEmpty()) {
      return '';
    }

    $measurement = $item->toMeasurement();
    if ($measurement->getUnit() !== 'in') {
      $measurement = $measurement->convert('in');
    }

    return (string) $measurement;
  }

  /**
   * Formats a submitted-by user reference.
   */
  protected function formatUserReference(int|string|null $uid): string {
    if ($uid === NULL || $uid === '') {
      return '';
    }

    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    return $user?->label() ?? (string) $uid;
  }

  /**
   * Formats a duplicate reference.
   */
  protected function formatDuplicateReference(int|string|null $entity_id, string $entity_type_id): string {
    if ($entity_id === NULL || $entity_id === '') {
      return '';
    }

    $entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
    return $entity?->label() ?? (string) $entity_id;
  }

}
