<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Entity\Caliber;

/**
 * Form controller for caliber add/edit forms.
 */
class CaliberForm extends GpcEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caliber name'),
      '#default_value' => $entity->label() ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('The canonical display name used in lists, references, and lookups.'),
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

    $form['bullet_diameter'] = [
      '#type' => 'number',
      '#title' => $this->t('Bullet diameter'),
      '#default_value' => $entity->get('bullet_diameter')->value ?? '',
      '#step' => 0.001,
      '#min' => 0,
      '#description' => $this->t('Optional bullet diameter in inches.'),
    ];

    $form['case_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Case length'),
      '#default_value' => $entity->get('case_length')->value ?? '',
      '#step' => 0.001,
      '#min' => 0,
      '#description' => $this->t('Optional case length in inches.'),
    ];

    $form['primer_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Primer type'),
      '#default_value' => $entity->get('primer_type')->value ?? '',
      '#options' => Caliber::primerTypeOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#description' => $this->t('Optional primer family used by this caliber.'),
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
      ? $this->t('Created the %label caliber.', ['%label' => $entity->label()])
      : $this->t('Updated the %label caliber.', ['%label' => $entity->label()]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach (['bullet_diameter' => 'Bullet diameter', 'case_length' => 'Case length'] as $field_name => $label) {
      $value = $form_state->getValue($field_name);
      if ($value === '' || $value === NULL) {
        continue;
      }

      if (!is_numeric($value) || (float) $value < 0) {
        $form_state->setErrorByName($field_name, $this->t('@field must be zero or greater.', ['@field' => $label]));
      }
    }
  }

  /**
   * Checks whether a machine name already exists.
   */
  public static function machineNameExists(string $machine_name): bool {
    $storage = \Drupal::entityTypeManager()->getStorage('gpc_caliber');
    return (bool) $storage->loadByProperties(['machine_name' => $machine_name]);
  }

}
