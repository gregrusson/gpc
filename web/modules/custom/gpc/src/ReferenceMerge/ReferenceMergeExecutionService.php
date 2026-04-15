<?php

declare(strict_types=1);

namespace Drupal\gpc\ReferenceMerge;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\gpc\ReferenceDiscovery\ReferenceDiscoveryInterface;
use InvalidArgumentException;

/**
 * Executes a limited repoint of known inbound references.
 */
final class ReferenceMergeExecutionService implements ReferenceMergeExecutionInterface {

  /**
   * Constructs the execution service.
   */
  public function __construct(
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ReferenceDiscoveryInterface $referenceDiscovery,
    protected ReferenceMergeRegistry $referenceMergeRegistry,
    protected AccountInterface $currentUser,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected TimeInterface $time,
    protected UuidInterface $uuid,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function execute(string $merge_type, int $source_entity_id, int $target_entity_id, ?AccountInterface $actor = NULL): array {
    $definition = $this->getDefinitionOrFail($merge_type);
    $entity_type_id = $definition['entity_type_id'];
    $source_entity = $this->loadEntityOrFail($entity_type_id, $source_entity_id);
    $target_entity = $this->loadEntityOrFail($entity_type_id, $target_entity_id);
    $actor = $actor ?? $this->currentUser;

    if ($source_entity_id === $target_entity_id) {
      throw new InvalidArgumentException('Source and target must be different entities.');
    }

    $discovery_report = $this->referenceDiscovery->discover($entity_type_id, $source_entity_id);
    $grouped_references = $this->groupDiscoveryReferences($discovery_report['references']);
    $result = $this->initializeResult($merge_type, $entity_type_id, $source_entity, $target_entity, $discovery_report, $actor);

    $transaction = $this->database->startTransaction();
    try {
      foreach ($grouped_references as $referencing_entity_type_id => $entities) {
        $storage = $this->entityTypeManager->getStorage($referencing_entity_type_id);

        foreach ($entities as $referencing_entity_id => $entity_report) {
          $entity = $storage->load($referencing_entity_id);
          if (!$entity instanceof EntityInterface) {
            throw new InvalidArgumentException(sprintf('The %s entity %s could not be loaded.', $referencing_entity_type_id, $referencing_entity_id));
          }

          $entity_changed = FALSE;
          $updated_fields = [];

          foreach ($entity_report['fields'] as $field_name => $field_references) {
            if (!$entity->hasField($field_name)) {
              $result['skipped_references'][] = [
                'entity_type_id' => $referencing_entity_type_id,
                'entity_id' => (int) $entity->id(),
                'label' => method_exists($entity, 'label') ? $entity->label() : NULL,
                'field_name' => $field_name,
                'reason' => 'field_missing',
              ];
              continue;
            }

            $field_result = $this->repointReferenceField($entity, $field_name, $source_entity_id, $target_entity_id);
            if ($field_result['status'] === 'updated') {
              $entity_changed = TRUE;
              $updated_fields[] = $field_name;
              $result['updated_field_count']++;
              $result['updated_fields'][] = [
                'entity_type_id' => $referencing_entity_type_id,
                'entity_id' => (int) $entity->id(),
                'label' => method_exists($entity, 'label') ? $entity->label() : NULL,
                'field_name' => $field_name,
                'reference_count' => $field_result['reference_count'],
              ];
            }
            else {
              $result['skipped_references'][] = [
                'entity_type_id' => $referencing_entity_type_id,
                'entity_id' => (int) $entity->id(),
                'label' => method_exists($entity, 'label') ? $entity->label() : NULL,
                'field_name' => $field_name,
                'reason' => $field_result['reason'] ?? 'no_change_required',
              ];
            }
          }

          if ($entity_changed) {
            $entity->save();
            $result['updated_entity_count']++;
            $result['updated_entities'][] = [
              'entity_type_id' => $referencing_entity_type_id,
              'entity_id' => (int) $entity->id(),
              'label' => method_exists($entity, 'label') ? $entity->label() : NULL,
              'fields' => $updated_fields,
            ];
          }
        }
      }

      $this->annotateSourceEntity($source_entity, $target_entity, $actor);
      $source_entity->save();

      $result['source_annotation'] = [
        'duplicate_of' => $target_entity_id,
        'annotated_by' => (int) $actor->id(),
        'annotated_at' => $this->time->getCurrentTime(),
      ];
      $result['status'] = 'success';

      $this->loggerFactory->get('gpc.reference_merge')->info('Manual merge executed for {source_type}:{source_id} -> {target_type}:{target_id} by uid {uid}. Updated entities: {updated_entities}; updated fields: {updated_fields}; skipped: {skipped}; failures: {failures}.', [
        'source_type' => $entity_type_id,
        'source_id' => $source_entity_id,
        'target_type' => $entity_type_id,
        'target_id' => $target_entity_id,
        'uid' => (int) $actor->id(),
        'updated_entities' => $result['updated_entity_count'],
        'updated_fields' => $result['updated_field_count'],
        'skipped' => count($result['skipped_references']),
        'failures' => count($result['failures']),
      ]);

      return $result;
    }
    catch (\Throwable $throwable) {
      $transaction->rollBack();
      $result['status'] = 'failed';
      $result['updated_entity_count'] = 0;
      $result['updated_field_count'] = 0;
      $result['updated_entities'] = [];
      $result['updated_fields'] = [];
      $result['source_annotation'] = NULL;
      $result['failures'][] = [
        'entity_type_id' => $entity_type_id,
        'entity_id' => $source_entity_id,
        'label' => method_exists($source_entity, 'label') ? $source_entity->label() : NULL,
        'field_name' => NULL,
        'message' => $throwable->getMessage(),
      ];

      $this->loggerFactory->get('gpc.reference_merge')->warning('Manual merge failed for {source_type}:{source_id} -> {target_type}:{target_id} by uid {uid}. Updated entities: {updated_entities}; updated fields: {updated_fields}; skipped: {skipped}; failures: {failures}. Error: {message}', [
        'source_type' => $entity_type_id,
        'source_id' => $source_entity_id,
        'target_type' => $entity_type_id,
        'target_id' => $target_entity_id,
        'uid' => (int) $actor->id(),
        'updated_entities' => $result['updated_entity_count'],
        'updated_fields' => $result['updated_field_count'],
        'skipped' => count($result['skipped_references']),
        'failures' => count($result['failures']),
        'message' => $throwable->getMessage(),
      ]);

      return $result;
    }
  }

  /**
   * Gets a supported merge definition or fails.
   */
  protected function getDefinitionOrFail(string $merge_type): array {
    $definition = $this->referenceMergeRegistry->getDefinition($merge_type);
    if ($definition === NULL) {
      throw new InvalidArgumentException(sprintf('Unsupported merge type "%s".', $merge_type));
    }

    return $definition;
  }

  /**
   * Loads one entity or fails.
   */
  protected function loadEntityOrFail(string $entity_type_id, int $entity_id): EntityInterface {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    if (!$entity instanceof EntityInterface) {
      throw new InvalidArgumentException(sprintf('The %s entity %s could not be loaded.', $entity_type_id, $entity_id));
    }

    return $entity;
  }

  /**
   * Groups discovery rows by entity type, entity ID, and field name.
   *
   * @param list<array<string, mixed>> $references
   *   Flat discovery rows.
   *
   * @return array<string, array<int, array{fields: array<string, list<array<string, mixed>>>}>>
   *   Grouped rows.
   */
  protected function groupDiscoveryReferences(array $references): array {
    $grouped = [];
    foreach ($references as $reference) {
      $entity_type_id = (string) $reference['referencing_entity_type_id'];
      $entity_id = (int) $reference['referencing_entity_id'];
      $field_name = (string) $reference['field_name'];
      $grouped[$entity_type_id][$entity_id]['fields'][$field_name][] = $reference;
    }

    return $grouped;
  }

  /**
   * Initializes a structured result payload.
   */
  protected function initializeResult(string $merge_type, string $entity_type_id, EntityInterface $source_entity, EntityInterface $target_entity, array $discovery_report, AccountInterface $actor): array {
    return [
      'result_id' => $this->uuid->generate(),
      'merge_type' => $merge_type,
      'entity_type_id' => $entity_type_id,
      'source' => [
        'entity_id' => (int) $source_entity->id(),
        'label' => method_exists($source_entity, 'label') ? $source_entity->label() : NULL,
      ],
      'target' => [
        'entity_id' => (int) $target_entity->id(),
        'label' => method_exists($target_entity, 'label') ? $target_entity->label() : NULL,
      ],
      'status' => 'pending',
      'actor' => [
        'uid' => (int) $actor->id(),
        'label' => $actor->getAccountName(),
      ],
      'discovered_reference_count' => (int) $discovery_report['totals']['overall'],
      'updated_entity_count' => 0,
      'updated_field_count' => 0,
      'updated_entities' => [],
      'updated_fields' => [],
      'skipped_references' => [],
      'unsupported_references' => [],
      'failures' => [],
      'source_annotation' => NULL,
    ];
  }

  /**
   * Repoints one entity reference field from source to target.
   *
   * @return array{status: string, reference_count: int, reason?: string}
   *   The result of the field update attempt.
   */
  protected function repointReferenceField(EntityInterface $entity, string $field_name, int $source_entity_id, int $target_entity_id): array {
    $current_ids = [];
    foreach ($entity->get($field_name) as $item) {
      $target_id = isset($item->target_id) ? (int) $item->target_id : NULL;
      if ($target_id !== NULL) {
        $current_ids[] = $target_id;
      }
    }

    if (!in_array($source_entity_id, $current_ids, TRUE)) {
      return [
        'status' => 'skipped',
        'reason' => 'source_not_present',
        'reference_count' => 0,
      ];
    }

    $repointed_ids = [];
    foreach ($current_ids as $current_id) {
      if ($current_id !== $source_entity_id) {
        $repointed_ids[] = $current_id;
      }
    }

    if (!in_array($target_entity_id, $repointed_ids, TRUE)) {
      $repointed_ids[] = $target_entity_id;
    }

    $repointed_ids = array_values(array_unique($repointed_ids));
    $current_ids = array_values(array_unique($current_ids));

    if ($repointed_ids === $current_ids) {
      return [
        'status' => 'skipped',
        'reason' => 'no_change_required',
        'reference_count' => 0,
      ];
    }

    $entity->set($field_name, array_map(static fn (int $reference_id): array => ['target_id' => $reference_id], $repointed_ids));

    return [
      'status' => 'updated',
      'reference_count' => count($current_ids),
    ];
  }

  /**
   * Annotates the source entity with a retention note and canonical pointer.
   */
  protected function annotateSourceEntity(EntityInterface $source_entity, EntityInterface $target_entity, AccountInterface $actor): void {
    if ($source_entity->hasField('duplicate_of')) {
      $source_entity->set('duplicate_of', $target_entity->id());
    }

    if ($source_entity->hasField('review_notes')) {
      $existing_notes = trim((string) ($source_entity->get('review_notes')->value ?? ''));
      $annotation = sprintf(
        'Superseded by %s %s during manual merge on %s by uid %d.',
        $target_entity->getEntityTypeId(),
        $target_entity->id(),
        gmdate('c', $this->time->getCurrentTime()),
        (int) $actor->id()
      );
      $source_entity->set('review_notes', $existing_notes === '' ? $annotation : $existing_notes . "\n" . $annotation);
    }
  }

}
