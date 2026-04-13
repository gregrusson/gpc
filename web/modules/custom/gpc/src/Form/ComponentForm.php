<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Entity\Component;

/**
 * Form controller for component add/edit forms.
 */
class ComponentForm extends GpcEntityFormBase {

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

    $form['component_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Component type'),
      '#default_value' => $entity->get('component_type')->value ?? 'other',
      '#required' => TRUE,
      '#options' => Component::componentTypeOptions(),
      '#description' => $this->t('The broad category used for filtering and recipe structure.'),
    ];

    $form['manufacturer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Manufacturer'),
      '#default_value' => $entity->get('manufacturer')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional brand or manufacturer name.'),
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
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $is_new = $entity->isNew();
    $status = $entity->save();

    $this->messenger()->addStatus($is_new
      ? $this->t('Created the %label component.', ['%label' => $entity->label()])
      : $this->t('Updated the %label component.', ['%label' => $entity->label()]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

  /**
   * Checks whether a machine name already exists.
   */
  public static function machineNameExists(string $machine_name): bool {
    $storage = \Drupal::entityTypeManager()->getStorage('gpc_component');
    return (bool) $storage->loadByProperties(['machine_name' => $machine_name]);
  }

}
