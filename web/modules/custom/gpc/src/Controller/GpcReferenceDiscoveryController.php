<?php

declare(strict_types=1);

namespace Drupal\gpc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gpc\ReferenceDiscovery\ReferenceDiscoveryInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read-only admin report for supported reference discovery.
 */
final class GpcReferenceDiscoveryController extends ControllerBase {

  /**
   * Constructs the controller.
   */
  public function __construct(
    protected ReferenceDiscoveryInterface $referenceDiscovery,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gpc.reference_discovery'),
    );
  }

  /**
   * Builds a report page for one supported target entity.
   */
  public function build(string $target_entity_type_id, string $target_entity_id): array {
    try {
      $report = $this->referenceDiscovery->discover($target_entity_type_id, (int) $target_entity_id);
    }
    catch (InvalidArgumentException $exception) {
      throw new NotFoundHttpException($exception->getMessage(), $exception);
    }

    $rows = [];
    foreach ($report['references'] as $reference) {
      $rows[] = [
        $reference['referencing_entity_type_id'],
        $reference['referencing_bundle'] ?? '',
        $reference['field_name'],
        (string) $reference['referencing_entity_id'],
        $reference['referencing_entity_label'] ?? '',
      ];
    }

    $summary_items = [
      $this->t('Overall references: @count', ['@count' => $report['totals']['overall']]),
    ];

    foreach ($report['totals']['by_entity_type'] as $entity_type_id => $count) {
      $summary_items[] = $this->t('@type: @count', [
        '@type' => $entity_type_id,
        '@count' => $count,
      ]);
    }

    $field_summary_items = [];
    foreach ($report['totals']['by_entity_type_and_field'] as $field_key => $count) {
      $field_summary_items[] = $this->t('@field: @count', [
        '@field' => $field_key,
        '@count' => $count,
      ]);
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gpc-reference-discovery'],
      ],
      'title' => [
        '#markup' => '<h1>' . $this->t('Reference discovery for @type @id', [
          '@type' => $report['target']['entity_type_id'],
          '@id' => $report['target']['entity_id'],
        ]) . '</h1>',
      ],
      'target' => [
        '#markup' => '<p>' . $this->t('Target label: @label', [
          '@label' => $report['target']['label'] ?? $this->t('(no label)'),
        ]) . '</p>',
      ],
      'summary' => [
        '#theme' => 'item_list',
        '#items' => $summary_items,
      ],
      'field_summary' => [
        '#theme' => 'item_list',
        '#items' => $field_summary_items,
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Entity type'),
          $this->t('Bundle'),
          $this->t('Field'),
          $this->t('Entity ID'),
          $this->t('Label'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No supported inbound references were found.'),
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
