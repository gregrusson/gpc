<?php

declare(strict_types=1);

namespace Drupal\gpc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\gpc\ReferenceDiscovery\ReferenceDiscoveryInterface;
use Drupal\gpc\ReferenceMerge\ReferenceMergeRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the admin-only reference merge preview workflow.
 */
final class GpcReferenceMergeController extends ControllerBase {

  /**
   * Constructs the controller.
   */
  public function __construct(
    protected ReferenceDiscoveryInterface $referenceDiscovery,
    protected ReferenceMergeRegistry $referenceMergeRegistry,
    protected EntityTypeManagerInterface $entityTypeManagerService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gpc.reference_discovery'),
      $container->get('gpc.reference_merge_registry'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Landing page for the manual merge helper.
   */
  public function landing(): array {
    $items = [];
    foreach ($this->referenceMergeRegistry->getDefinitions() as $merge_type => $definition) {
      $items[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['gpc-reference-merge-landing__option'],
        ],
        'link' => Link::fromTextAndUrl($definition['label'], Url::fromRoute('gpc.reference_merge.form', [
          'merge_type' => $merge_type,
        ]))->toRenderable(),
        'description' => [
          '#markup' => '<p>' . $this->t('@description', ['@description' => $definition['description']]) . '</p>',
        ],
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gpc-reference-merge-landing'],
      ],
      'warning' => [
        '#markup' => '<p><strong>' . $this->t('Manual admin maintenance only.') . '</strong> ' . $this->t('This tool previews supported inbound references for a potential merge. Nothing is deleted or repointed here.') . '</p>',
      ],
      'options' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['gpc-reference-merge-landing__options'],
        ],
        'items' => $items,
      ],
    ];
  }

  /**
   * Builds the preview page for one proposed merge.
   */
  public function preview(string $merge_type, string $source_entity_id, string $target_entity_id): array {
    $definition = $this->getMergeDefinitionOr404($merge_type);
    $entity_type_id = $definition['entity_type_id'];
    $source_entity = $this->loadEntityOr404($entity_type_id, (int) $source_entity_id);
    $target_entity = $this->loadEntityOr404($entity_type_id, (int) $target_entity_id);

    if ((int) $source_entity->id() === (int) $target_entity->id()) {
      throw new NotFoundHttpException((string) $this->t('Source and target must be different entities.'));
    }

    $report = $this->referenceDiscovery->discover($entity_type_id, (int) $source_entity->id());
    $grouped_references = $this->groupReferences($report['references']);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gpc-reference-merge-preview'],
      ],
      'warning' => [
        '#markup' => '<p><strong>' . $this->t('Manual admin maintenance action.') . '</strong> ' . $this->t('This is a preview only. The source record is not deleted here, and only known/supported references are shown.') . '</p>',
      ],
      'summary' => [
        '#theme' => 'table',
        '#header' => [
          $this->t('Role'),
          $this->t('Entity'),
          $this->t('ID'),
        ],
        '#rows' => [
          [
            $this->t('Source / duplicate'),
            $source_entity->label(),
            (string) $source_entity->id(),
          ],
          [
            $this->t('Target / canonical'),
            $target_entity->label(),
            (string) $target_entity->id(),
          ],
        ],
      ],
      'totals_intro' => [
        '#markup' => '<p>' . $this->t('Grouped counts show the known supported references that would be repointed later.') . '</p>',
      ],
      'overall' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Overall references that would be repointed: @count', ['@count' => $report['totals']['overall']]),
          $this->t('Supported source fields registered: @count', ['@count' => count($report['supported_sources'])]),
        ],
      ],
      'entity_type_totals' => $this->buildTotalsList($report['totals']['by_entity_type']),
      'field_totals' => $this->buildTotalsList($report['totals']['by_entity_type_and_field']),
      'groups' => $this->buildReferenceGroups($grouped_references),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Loads a supported merge type definition or throws a 404.
   */
  protected function getMergeDefinitionOr404(string $merge_type): array {
    $definition = $this->referenceMergeRegistry->getDefinition($merge_type);
    if ($definition === NULL) {
      throw new NotFoundHttpException((string) $this->t('Unsupported merge type.'));
    }

    return $definition;
  }

  /**
   * Loads an entity by type and ID or throws a 404.
   */
  protected function loadEntityOr404(string $entity_type_id, int $entity_id): EntityInterface {
    $entity = $this->entityTypeManagerService->getStorage($entity_type_id)->load($entity_id);

    if (!$entity instanceof EntityInterface) {
      throw new NotFoundHttpException((string) $this->t('The selected entity could not be loaded.'));
    }

    return $entity;
  }

  /**
   * Groups discovery results by entity type and field.
   *
   * @param list<array<string, mixed>> $references
   *   Flat discovery results from the discovery service.
   *
   * @return array<string, array<string, array<int, array<string, mixed>>>>
   *   Grouped results.
   */
  protected function groupReferences(array $references): array {
    $grouped = [];
    foreach ($references as $reference) {
      $entity_type_id = (string) $reference['referencing_entity_type_id'];
      $field_name = (string) $reference['field_name'];
      $grouped[$entity_type_id][$field_name][] = $reference;
    }

    return $grouped;
  }

  /**
   * Builds a compact totals list.
   */
  protected function buildTotalsList(array $totals): array {
    $items = [];
    foreach ($totals as $key => $count) {
      $items[] = $this->t('@key: @count', [
        '@key' => (string) $key,
        '@count' => $count,
      ]);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * Builds grouped reference detail sections.
   *
   * @param array<string, array<string, array<int, array<string, mixed>>>> $grouped_references
   *   Grouped discovery results.
   */
  protected function buildReferenceGroups(array $grouped_references): array {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gpc-reference-merge-preview__groups'],
      ],
    ];

    foreach ($grouped_references as $entity_type_id => $fields) {
      $build[$entity_type_id] = [
        '#type' => 'details',
        '#title' => $this->t('@type references', ['@type' => $entity_type_id]),
        '#open' => TRUE,
      ];

      foreach ($fields as $field_name => $references) {
        $rows = [];
        foreach ($references as $reference) {
          $rows[] = [
            $reference['referencing_bundle'] ?? '',
            (string) $reference['referencing_entity_id'],
            $reference['referencing_entity_label'] ?? '',
          ];
        }

        $build[$entity_type_id][$field_name] = [
          '#type' => 'details',
          '#title' => $this->t('@field (@count)', [
            '@field' => $field_name,
            '@count' => count($references),
          ]),
          '#open' => TRUE,
          'table' => [
            '#theme' => 'table',
            '#header' => [
              $this->t('Bundle'),
              $this->t('Entity ID'),
              $this->t('Label'),
            ],
            '#rows' => $rows,
            '#empty' => $this->t('No supported references found.'),
          ],
        ];
      }
    }

    return $build;
  }

}
