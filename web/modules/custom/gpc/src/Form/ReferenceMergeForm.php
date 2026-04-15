<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\gpc\ReferenceMerge\ReferenceMergeRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form for choosing source / duplicate and target / canonical records for the manual merge helper preview.
 */
final class ReferenceMergeForm extends FormBase {

  /**
   * Constructs the form.
   */
  public function __construct(
    protected ReferenceMergeRegistry $referenceMergeRegistry,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gpc.reference_merge_registry'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'gpc_reference_merge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $merge_type = NULL): array {
    $definition = $this->getDefinitionOr404((string) $merge_type);
    $entity_type_id = $definition['entity_type_id'];

    $form['intro'] = [
      '#markup' => '<p><strong>' . $this->t('Manual admin maintenance action.') . '</strong> ' . $this->t('Preview only. The source record is not deleted here and nothing is repointed yet.') . '</p>',
    ];

    $form['merge_type'] = [
      '#type' => 'value',
      '#value' => $merge_type,
    ];

    $form['help'] = [
      '#markup' => '<p>' . $this->t('Select the source / duplicate record and the target / canonical record for @label.', [
        '@label' => $definition['label'],
      ]) . '</p>',
    ];

    $form['source_entity_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Source / duplicate'),
      '#target_type' => $entity_type_id,
      '#selection_handler' => 'default',
      '#selection_settings' => [],
      '#required' => TRUE,
      '#description' => $this->t('This entity would keep its inbound references only in the preview step.'),
    ];

    $form['target_entity_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Target / canonical'),
      '#target_type' => $entity_type_id,
      '#selection_handler' => 'default',
      '#selection_settings' => [],
      '#required' => TRUE,
      '#description' => $this->t('This is the record that would receive references later in execution.'),
    ];

    $form['warnings'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('This is a manual admin maintenance workflow.'),
        $this->t('Unsupported references are not handled automatically.'),
        $this->t('Only known/supported references are shown in the preview.'),
      ],
    ];

    $form['actions']['submit']['#value'] = $this->t('Preview merge');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $merge_type = (string) $form_state->getValue('merge_type');
    $definition = $this->getDefinitionOr404($merge_type);
    $entity_type_id = $definition['entity_type_id'];

    $source_id = $this->extractAutocompleteTargetId($form_state->getValue('source_entity_id'));
    $target_id = $this->extractAutocompleteTargetId($form_state->getValue('target_entity_id'));

    if ($source_id === NULL) {
      $form_state->setErrorByName('source_entity_id', $this->t('Select a source / duplicate record.'));
      return;
    }

    if ($target_id === NULL) {
      $form_state->setErrorByName('target_entity_id', $this->t('Select a target / canonical record.'));
      return;
    }

    if ((int) $source_id === (int) $target_id) {
      $form_state->setErrorByName('target_entity_id', $this->t('Source and target cannot be the same entity.'));
      return;
    }

    $source = $this->loadEntityOr404($entity_type_id, (int) $source_id);
    $target = $this->loadEntityOr404($entity_type_id, (int) $target_id);

    if ($source->getEntityTypeId() !== $entity_type_id || $target->getEntityTypeId() !== $entity_type_id) {
      $form_state->setErrorByName('merge_type', $this->t('The selected records must match the chosen merge type.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $merge_type = (string) $form_state->getValue('merge_type');
    $source_id = (int) $this->extractAutocompleteTargetId($form_state->getValue('source_entity_id'));
    $target_id = (int) $this->extractAutocompleteTargetId($form_state->getValue('target_entity_id'));

    $form_state->setRedirectUrl(Url::fromRoute('gpc.reference_merge.preview', [
      'merge_type' => $merge_type,
      'source_entity_id' => $source_id,
      'target_entity_id' => $target_id,
    ]));
  }

  /**
   * Gets one supported merge definition or throws 404.
   */
  protected function getDefinitionOr404(string $merge_type): array {
    $definition = $this->referenceMergeRegistry->getDefinition($merge_type);
    if ($definition === NULL) {
      throw new NotFoundHttpException($this->t('Unsupported merge type.'));
    }

    return $definition;
  }

  /**
   * Loads an entity by type and ID or throws a 404.
   */
  protected function loadEntityOr404(string $entity_type_id, int $entity_id): EntityInterface {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    if (!$entity instanceof EntityInterface) {
      throw new NotFoundHttpException($this->t('The selected entity could not be loaded.'));
    }

    return $entity;
  }

  /**
   * Normalizes entity autocomplete values into an entity ID.
   *
   * @param mixed $value
   *   Raw form value from an entity_autocomplete element.
   */
  protected function extractAutocompleteTargetId(mixed $value): ?int {
    if (is_array($value)) {
      $value = $value[0]['target_id'] ?? $value['target_id'] ?? NULL;
    }

    if ($value === NULL || $value === '') {
      return NULL;
    }

    return (int) $value;
  }

}
