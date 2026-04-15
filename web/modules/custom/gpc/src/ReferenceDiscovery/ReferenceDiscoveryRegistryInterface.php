<?php

declare(strict_types=1);

namespace Drupal\gpc\ReferenceDiscovery;

/**
 * Exposes the intentionally supported reference-discovery map.
 */
interface ReferenceDiscoveryRegistryInterface {

  /**
   * Returns the supported inbound reference definitions for a target entity type.
   *
   * Only references declared in this registry are discoverable. Future or
   * unsupported fields must be added here intentionally before the merge flow
   * can rely on them.
   *
   * @return array<int, array{source_entity_type: string, field_name: string, source_bundles?: array<int, string>, label?: string}>
   *   A list of source definitions for the given target entity type.
   */
  public function getSourceDefinitions(string $target_entity_type_id): array;

  /**
   * Returns whether the target entity type is supported by the registry.
   */
  public function hasTargetEntityType(string $target_entity_type_id): bool;

}
