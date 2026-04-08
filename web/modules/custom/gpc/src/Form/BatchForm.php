<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for batch add/edit forms.
 */
class BatchForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $entity->label() ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('The human-readable name shown in admin screens.'),
    ];

    if ($entity->isNew()) {
      $form['machine_name'] = [
        '#type' => 'machine_name',
        '#title' => $this->t('Machine name'),
        '#default_value' => $entity->get('machine_name')->value ?? '',
        '#required' => TRUE,
        '#maxlength' => 128,
        '#machine_name' => [
          'exists' => [static::class, 'machineNameExists'],
          'source' => ['label'],
        ],
        '#description' => $this->t('A unique internal identifier.'),
      ];
    }
    else {
      $form['machine_name'] = [
        '#type' => 'item',
        '#title' => $this->t('Machine name'),
        '#markup' => $entity->get('machine_name')->value ?? '',
        '#description' => $this->t('This value is fixed after creation.'),
      ];
    }

    $form['recipe'] = $this->buildAutocompleteField($entity, 'recipe', $this->t('Recipe'), 'gpc_recipe', TRUE);

    $form['batch_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date produced'),
      '#default_value' => $entity->isNew()
        ? date('Y-m-d')
        : ($entity->get('batch_date')->value ? date('Y-m-d', (int) $entity->get('batch_date')->value) : ''),
      '#required' => TRUE,
      '#description' => $this->t('The date the batch was produced.'),
    ];

    $form['quantity_produced'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity produced'),
      '#default_value' => $entity->get('quantity_produced')->value ?? '',
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('Enter the number of rounds or units produced.'),
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#default_value' => $entity->get('notes')->value ?? '',
      '#rows' => 6,
      '#description' => $this->t('Optional internal notes and context.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\gpc\Entity\Batch $entity */
    $entity = parent::buildEntity($form, $form_state);

    $batch_date = $form_state->getValue('batch_date');
    if (is_string($batch_date) && $batch_date !== '') {
      $timestamp = strtotime($batch_date . ' 00:00:00');
      if ($timestamp !== FALSE) {
        $entity->set('batch_date', $timestamp);
      }
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $is_new = $entity->isNew();
    $status = $entity->save();

    $this->messenger()->addStatus($is_new
      ? $this->t('Created the %label batch.', ['%label' => $entity->label()])
      : $this->t('Updated the %label batch.', ['%label' => $entity->label()]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

  /**
   * Builds one autocomplete field.
   */
  protected function buildAutocompleteField(EntityInterface $entity, string $field_name, string $title, string $target_type, bool $required): array {
    $default_value = NULL;
    $target_id = $entity->get($field_name)->first()?->target_id ?? NULL;
    if ($target_id) {
      $default_value = \Drupal::entityTypeManager()->getStorage($target_type)->load($target_id);
    }

    return [
      '#type' => 'entity_autocomplete',
      '#title' => $title,
      '#target_type' => $target_type,
      '#default_value' => $default_value,
      '#required' => $required,
      '#selection_handler' => 'default',
      '#selection_settings' => [],
      '#description' => $required
        ? $this->t('Select a referenced entity.')
        : $this->t('Optional reference.'),
    ];
  }

  /**
   * Checks whether a machine name already exists.
   */
  public static function machineNameExists(string $machine_name): bool {
    $storage = \Drupal::entityTypeManager()->getStorage('gpc_batch');
    return (bool) $storage->loadByProperties(['machine_name' => $machine_name]);
  }

}
