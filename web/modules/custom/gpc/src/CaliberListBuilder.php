<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\gpc\Entity\Caliber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the caliber listing page.
 */
class CaliberListBuilder extends EntityListBuilder {

  /**
   * Constructs a caliber list builder.
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
      'label' => $this->t('Caliber name'),
      'nickname' => $this->t('Nickname'),
      'primer_type' => $this->t('Primer type'),
      'bullet_diameter' => $this->t('Bullet diameter'),
      'case_length' => $this->t('Case length'),
      'max_overall_length' => $this->t('Max overall length'),
      'changed' => $this->t('Updated'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if ($entity->access('update', NULL, TRUE)->isAllowed()) {
      $row['label']['data'] = Link::fromTextAndUrl((string) $entity->label(), $entity->toUrl('edit-form'))->toRenderable();
    }
    else {
      $row['label']['data'] = [
        '#plain_text' => (string) $entity->label(),
      ];
    }
    $row['nickname'] = $entity->get('nickname')->value ?? '';
    $primer_type = $entity->get('primer_type')->value ?? '';
    $row['primer_type'] = Caliber::primerTypeOptions()[$primer_type] ?? $primer_type;
    $row['bullet_diameter'] = $this->formatMeasurement($entity, 'bullet_diameter');
    $row['case_length'] = $this->formatMeasurement($entity, 'case_length');
    $row['max_overall_length'] = $this->formatMeasurement($entity, 'max_overall_length');
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
    return $this->t('Calibers');
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
