<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\gpc\ReferenceDiscovery\ReferenceDiscoveryInterface;
use Drupal\gpc\ReferenceMerge\ReferenceMergeExecutionInterface;
use Drupal\gpc\ReferenceMerge\ReferenceMergeRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Confirmation form that executes the selected manual merge helper action.
 */
final class ReferenceMergeExecuteForm extends FormBase {

  /**
   * Constructs the form.
   */
  public function __construct(
    protected ReferenceMergeRegistry $referenceMergeRegistry,
    protected ReferenceDiscoveryInterface $referenceDiscovery,
    protected ReferenceMergeExecutionInterface $referenceMergeExecution,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected UuidInterface $uuid,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gpc.reference_merge_registry'),
      $container->get('gpc.reference_discovery'),
      $container->get('gpc.reference_merge_execution'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('uuid'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'gpc_reference_merge_execute_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $merge_type = NULL, ?string $source_entity_id = NULL, ?string $target_entity_id = NULL): array {
    $definition = $this->getDefinitionOr404((string) $merge_type);
    $entity_type_id = $definition['entity_type_id'];
    $source_entity = $this->loadEntityOr404($entity_type_id, (int) $source_entity_id);
    $target_entity = $this->loadEntityOr404($entity_type_id, (int) $target_entity_id);

    if ((int) $source_entity->id() === (int) $target_entity->id()) {
      throw new NotFoundHttpException((string) $this->t('Source and target must be different entities.'));
    }

    $report = $this->referenceDiscovery->discover($entity_type_id, (int) $source_entity->id());

    $form['merge_type'] = [
      '#type' => 'value',
      '#value' => $merge_type,
    ];
    $form['source_entity_id'] = [
      '#type' => 'value',
      '#value' => (int) $source_entity->id(),
    ];
    $form['target_entity_id'] = [
      '#type' => 'value',
      '#value' => (int) $target_entity->id(),
    ];
    $form['result_token'] = [
      '#type' => 'value',
      '#value' => $this->getResultToken($form_state),
    ];

    $form['warning'] = [
      '#markup' => '<p><strong>' . $this->t('Manual admin maintenance action.') . '</strong> ' . $this->t('This confirmation step will repoint only known/supported inbound references. Unsupported references will remain untouched.') . '</p>',
    ];
    $form['summary'] = [
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
    ];
    $form['counts'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Known supported references to inspect: @count', ['@count' => $report['totals']['overall']]),
        $this->t('Supported source definitions registered: @count', ['@count' => count($report['supported_sources'])]),
      ],
    ];
    $form['preview'] = $this->buildPreview($report['references']);

    $form['actions']['submit']['#value'] = $this->t('Confirm and execute merge');
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to preview'),
      '#url' => Url::fromRoute('gpc.reference_merge.preview', [
        'merge_type' => $merge_type,
        'source_entity_id' => $source_entity->id(),
        'target_entity_id' => $target_entity->id(),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $merge_type = (string) $form_state->getValue('merge_type');
    $source_entity_id = (int) $form_state->getValue('source_entity_id');
    $target_entity_id = (int) $form_state->getValue('target_entity_id');
    $result_token = (string) $form_state->getValue('result_token');

    $result = $this->referenceMergeExecution->execute($merge_type, $source_entity_id, $target_entity_id, $this->currentUser());
    $this->tempStoreFactory->get('gpc.reference_merge_results')->set($result_token, $result);

    $form_state->setRedirectUrl(Url::fromRoute('gpc.reference_merge.result', [
      'result_token' => $result_token,
    ]));
  }

  /**
   * Gets one supported merge definition or throws 404.
   */
  protected function getDefinitionOr404(string $merge_type): array {
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
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    if (!$entity instanceof EntityInterface) {
      throw new NotFoundHttpException((string) $this->t('The selected entity could not be loaded.'));
    }

    return $entity;
  }

  /**
   * Returns a unique result token for the current form lifecycle.
   */
  protected function getResultToken(FormStateInterface $form_state): string {
    $token = $form_state->getTemporaryValue('result_token');
    if (is_string($token) && $token !== '') {
      return $token;
    }

    $token = $this->uuid->generate();
    $form_state->setTemporaryValue('result_token', $token);

    return $token;
  }

  /**
   * Builds a compact preview for the confirmation form.
   *
   * @param list<array<string, mixed>> $references
   *   Flat discovery results.
   */
  protected function buildPreview(array $references): array {
    $grouped = [];
    foreach ($references as $reference) {
      $grouped[(string) $reference['referencing_entity_type_id']][(string) $reference['field_name']][] = $reference;
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gpc-reference-merge-execute-preview'],
      ],
    ];

    foreach ($grouped as $entity_type_id => $fields) {
      $build[$entity_type_id] = [
        '#type' => 'details',
        '#title' => $this->t('@type references', ['@type' => $entity_type_id]),
        '#open' => TRUE,
      ];

      foreach ($fields as $field_name => $field_references) {
        $rows = [];
        foreach ($field_references as $reference) {
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
            '@count' => count($field_references),
          ]),
          '#open' => FALSE,
          'table' => [
            '#theme' => 'table',
            '#header' => [
              $this->t('Bundle'),
              $this->t('Entity ID'),
              $this->t('Label'),
            ],
            '#rows' => $rows,
          ],
        ];
      }
    }

    return $build;
  }

}
