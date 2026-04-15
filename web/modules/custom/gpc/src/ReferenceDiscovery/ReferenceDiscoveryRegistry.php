<?php

declare(strict_types=1);

namespace Drupal\gpc\ReferenceDiscovery;

/**
 * Registry-backed map of supported inbound references for the manual merge helper.
 *
 * This does not attempt to infer references dynamically. Only the explicitly
 * registered Caliber and Component reference fields are considered discoverable
 * for the limited manual merge helper.
 */
final class ReferenceDiscoveryRegistry implements ReferenceDiscoveryRegistryInterface {

  /**
   * Constructs the registry.
   *
   * @param array<string, array<int, array<string, mixed>>> $supported_reference_sources
   *   A configuration-driven map of target entity type to supported sources.
   */
  public function __construct(
    protected array $supportedReferenceSources,
  ) {
    $this->supportedReferenceSources = $this->normalizeSources($supportedReferenceSources);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceDefinitions(string $target_entity_type_id): array {
    return $this->supportedReferenceSources[$target_entity_type_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasTargetEntityType(string $target_entity_type_id): bool {
    return array_key_exists($target_entity_type_id, $this->supportedReferenceSources);
  }

  /**
   * Normalizes registry input so the discovery service can rely on one shape.
   *
   * @param array<string, array<int, array<string, mixed>>> $supported_reference_sources
   *   The raw registry configuration.
   *
   * @return array<string, array<int, array{source_entity_type: string, field_name: string, source_bundles?: array<int, string>, label?: string}>>
   *   The normalized registry configuration.
   */
  protected function normalizeSources(array $supported_reference_sources): array {
    $normalized = [];

    foreach ($supported_reference_sources as $target_entity_type_id => $source_definitions) {
      foreach ($source_definitions as $definition) {
        if (!is_array($definition)) {
          continue;
        }

        $source_entity_type = (string) ($definition['source_entity_type'] ?? '');
        $field_name = (string) ($definition['field_name'] ?? '');
        if ($source_entity_type === '' || $field_name === '') {
          continue;
        }

        $source_bundles = [];
        if (isset($definition['source_bundles']) && is_array($definition['source_bundles'])) {
          foreach ($definition['source_bundles'] as $source_bundle) {
            if (is_string($source_bundle) && $source_bundle !== '') {
              $source_bundles[] = $source_bundle;
            }
          }
        }

        $normalized[$target_entity_type_id][] = [
          'source_entity_type' => $source_entity_type,
          'field_name' => $field_name,
          'source_bundles' => $source_bundles,
          'label' => isset($definition['label']) && is_string($definition['label']) && $definition['label'] !== '' ? $definition['label'] : NULL,
        ];
      }
    }

    return $normalized;
  }

}
