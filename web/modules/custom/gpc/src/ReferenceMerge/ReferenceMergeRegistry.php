<?php

declare(strict_types=1);

namespace Drupal\gpc\ReferenceMerge;

/**
 * Registry for supported manual merge preview target types.
 *
 * This workflow is intentionally limited to explicit Caliber and Component
 * targets. Add new target definitions here intentionally when execution support
 * is added in a later step.
 */
final class ReferenceMergeRegistry {

  /**
   * Constructs the registry.
   *
   * @param array<string, array<string, string>> $targets
   *   Merge target definitions keyed by merge type.
   */
  public function __construct(
    protected array $targets,
  ) {
  }

  /**
   * Returns all supported merge type definitions.
   *
   * @return array<string, array{entity_type_id: string, label: string, description: string}>
   *   Merge target definitions keyed by merge type.
   */
  public function getDefinitions(): array {
    return $this->targets;
  }

  /**
   * Returns a single merge type definition.
   *
   * @return array{entity_type_id: string, label: string, description: string}|null
   *   The merge target definition or NULL when unsupported.
   */
  public function getDefinition(string $merge_type): ?array {
    return $this->targets[$merge_type] ?? NULL;
  }

  /**
   * Checks whether a merge type is supported.
   */
  public function hasDefinition(string $merge_type): bool {
    return array_key_exists($merge_type, $this->targets);
  }

}
