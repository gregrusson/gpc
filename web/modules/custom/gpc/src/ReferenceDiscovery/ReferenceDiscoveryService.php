<?php

declare(strict_types=1);

namespace Drupal\gpc\ReferenceDiscovery;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use InvalidArgumentException;

/**
 * Discovers inbound references from the explicitly supported source map.
 */
final class ReferenceDiscoveryService implements ReferenceDiscoveryInterface {

  /**
   * Constructs the discovery service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ReferenceDiscoveryRegistryInterface $registry,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function discover(string $target_entity_type_id, int|string $target_entity_id): array {
    $target = $this->loadTargetEntity($target_entity_type_id, $target_entity_id);
    $source_definitions = $this->registry->getSourceDefinitions($target_entity_type_id);

    $report = [
      'target' => [
        'entity_type_id' => $target_entity_type_id,
        'entity_id' => (int) $target_entity_id,
        'label' => method_exists($target, 'label') ? $target->label() : NULL,
      ],
      'references' => [],
      'totals' => [
        'overall' => 0,
        'by_entity_type' => [],
        'by_entity_type_and_field' => [],
      ],
      'supported_sources' => $source_definitions,
    ];

    foreach ($source_definitions as $source_definition) {
      $report = $this->discoverFromSourceDefinition($target_entity_id, $source_definition, $report);
    }

    return $report;
  }

  /**
   * Loads and validates the target entity.
   */
  protected function loadTargetEntity(string $target_entity_type_id, int|string $target_entity_id): EntityInterface {
    if (!$this->registry->hasTargetEntityType($target_entity_type_id)) {
      throw new InvalidArgumentException(sprintf('Unsupported reference discovery target entity type "%s".', $target_entity_type_id));
    }

    if (!$this->entityTypeManager->hasDefinition($target_entity_type_id)) {
      throw new InvalidArgumentException(sprintf('Unknown entity type "%s".', $target_entity_type_id));
    }

    $storage = $this->entityTypeManager->getStorage($target_entity_type_id);
    $entity = $storage->load($target_entity_id);
    if (!$entity instanceof EntityInterface) {
      throw new InvalidArgumentException(sprintf('The %s entity %s could not be loaded.', $target_entity_type_id, (string) $target_entity_id));
    }

    return $entity;
  }

  /**
   * Discovers references for one source definition and appends them to report.
   *
   * @param array{
   *   target: array{entity_type_id: string, entity_id: int, label: string|null},
   *   references: list<array{referencing_entity_type_id: string, referencing_bundle: string|null, field_name: string, referencing_entity_id: int, referencing_entity_label: string|null}>,
   *   totals: array{overall: int, by_entity_type: array<string, int>, by_entity_type_and_field: array<string, int>},
   *   supported_sources: list<array{source_entity_type: string, field_name: string, source_bundles: array<int, string>, label: string|null}>
   * } $report
   *
   * @return array{
   *   target: array{entity_type_id: string, entity_id: int, label: string|null},
   *   references: list<array{referencing_entity_type_id: string, referencing_bundle: string|null, field_name: string, referencing_entity_id: int, referencing_entity_label: string|null}>,
   *   totals: array{overall: int, by_entity_type: array<string, int>, by_entity_type_and_field: array<string, int>},
   *   supported_sources: list<array{source_entity_type: string, field_name: string, source_bundles: array<int, string>, label: string|null}>
   * }
   */
  protected function discoverFromSourceDefinition(int|string $target_entity_id, array $source_definition, array $report): array {
    $source_entity_type_id = (string) $source_definition['source_entity_type'];
    $field_name = (string) $source_definition['field_name'];
    $source_bundles = $source_definition['source_bundles'] ?? [];

    if (!$this->entityTypeManager->hasDefinition($source_entity_type_id)) {
      throw new InvalidArgumentException(sprintf('Unknown source entity type "%s" in reference discovery registry.', $source_entity_type_id));
    }

    $source_entity_type = $this->entityTypeManager->getDefinition($source_entity_type_id);
    $storage = $this->entityTypeManager->getStorage($source_entity_type_id);
    $query = $storage->getQuery()->accessCheck(FALSE)->condition($field_name, $target_entity_id);

    if ($source_bundles !== []) {
      $bundle_key = $source_entity_type->getKey('bundle');
      if ($bundle_key === NULL) {
        return $report;
      }

      $query->condition($bundle_key, $source_bundles, 'IN');
    }

    $ids = $query->execute();
    if ($ids === []) {
      return $report;
    }

    $entities = $storage->loadMultiple($ids);
    foreach ($entities as $entity) {
      if (!$entity instanceof EntityInterface) {
        continue;
      }

      $report['references'][] = [
        'referencing_entity_type_id' => $source_entity_type_id,
        'referencing_bundle' => method_exists($entity, 'bundle') ? $entity->bundle() : NULL,
        'field_name' => $field_name,
        'referencing_entity_id' => (int) $entity->id(),
        'referencing_entity_label' => method_exists($entity, 'label') ? $entity->label() : NULL,
      ];

      $report['totals']['overall']++;
      $report['totals']['by_entity_type'][$source_entity_type_id] = ($report['totals']['by_entity_type'][$source_entity_type_id] ?? 0) + 1;
      $field_key = $source_entity_type_id . '.' . $field_name;
      $report['totals']['by_entity_type_and_field'][$field_key] = ($report['totals']['by_entity_type_and_field'][$field_key] ?? 0) + 1;
    }

    return $report;
  }

}
