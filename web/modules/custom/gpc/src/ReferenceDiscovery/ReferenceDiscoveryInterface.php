<?php

declare(strict_types=1);

namespace Drupal\gpc\ReferenceDiscovery;

/**
 * Discovers known inbound references for the manual merge helper targets.
 */
interface ReferenceDiscoveryInterface {

  /**
   * Returns a structured reference report for a supported target entity.
   *
   * The report only includes intentionally registered source fields. This is
   * read-only discovery for the manual merge helper and does not repoint or
   * merge any entities.
   *
   * @return array{
   *   target: array{entity_type_id: string, entity_id: int, label: string|null},
   *   references: list<array{
   *     referencing_entity_type_id: string,
   *     referencing_bundle: string|null,
   *     field_name: string,
   *     referencing_entity_id: int,
   *     referencing_entity_label: string|null
   *   }>,
   *   totals: array{
   *     overall: int,
   *     by_entity_type: array<string, int>,
   *     by_entity_type_and_field: array<string, int>
   *   },
   *   supported_sources: list<array{
   *     source_entity_type: string,
   *     field_name: string,
   *     source_bundles: array<int, string>,
   *     label: string|null
   *   }>
   * }
   */
  public function discover(string $target_entity_type_id, int|string $target_entity_id): array;

}
