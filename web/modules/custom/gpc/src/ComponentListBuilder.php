<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
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
      'weight' => $this->t('Bullet weight'),
      'diameter' => $this->t('Bullet diameter'),
      'case_length' => $this->t('Case length'),
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
    $row['weight'] = $entity->get('weight')->value ?? '';
    $row['diameter'] = $this->formatMeasurement($entity, 'diameter');
    $row['case_length'] = $this->formatMeasurement($entity, 'case_length');
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

}
