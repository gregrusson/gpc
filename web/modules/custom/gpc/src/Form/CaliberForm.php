<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Entity\Caliber;
use Drupal\physical\Calculator;
use Drupal\Component\Utility\NestedArray;

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
      '#description' => $this->t('The canonical display name used in lists, references, and shared-record views.'),
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
    $form['nickname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nickname'),
      '#default_value' => $entity->get('nickname')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional abbreviation or shorthand for the caliber name.'),
    ];

    $form['primer_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Primer type'),
      '#default_value' => $entity->get('primer_type')->value ?? '',
      '#options' => Caliber::primerTypeOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#description' => $this->t('Optional reloading primer family used by this caliber.'),
    ];

    $form['bullet_diameter'] = $this->buildLengthMeasurementElement($entity, 'bullet_diameter', $this->t('Bullet diameter'), $this->t('Optional bullet diameter in inches or millimeters.'));

    $form['neck_diameter'] = $this->buildLengthMeasurementElement($entity, 'neck_diameter', $this->t('Neck diameter'), $this->t('Optional neck diameter in inches or millimeters.'));

    $form['shoulder_diameter'] = $this->buildLengthMeasurementElement($entity, 'shoulder_diameter', $this->t('Shoulder diameter'), $this->t('Optional shoulder diameter in inches or millimeters.'));

    $form['base_diameter'] = $this->buildLengthMeasurementElement($entity, 'base_diameter', $this->t('Base diameter'), $this->t('Optional base diameter in inches or millimeters.'));

    $form['rim_diameter'] = $this->buildLengthMeasurementElement($entity, 'rim_diameter', $this->t('Rim diameter'), $this->t('Optional rim diameter in inches or millimeters.'));

    $form['case_length'] = $this->buildLengthMeasurementElement($entity, 'case_length', $this->t('Case length'), $this->t('Optional case length in inches or millimeters.'));

    $form['max_overall_length'] = $this->buildLengthMeasurementElement($entity, 'max_overall_length', $this->t('Max overall length'), $this->t('Optional maximum overall length in inches or millimeters.'));

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

    $form_state->setRedirectUrl($entity->toUrl('canonical'));

    return $status;
  }

  /**
   * Checks whether a machine name already exists.
   */
  public static function machineNameExists(string $machine_name): bool {
    $storage = \Drupal::entityTypeManager()->getStorage('gpc_caliber');
    return (bool) $storage->loadByProperties(['machine_name' => $machine_name]);
  }

  /**
   * Builds a physical length measurement element for caliber units.
   */
  protected function buildLengthMeasurementElement($entity, string $field_name, string|\Stringable $title, string|\Stringable $description): array {
    $field_item = $entity->get($field_name)->first();
    $default_value = [
      'number' => '',
      'unit' => 'in',
    ];
    if ($field_item && !$field_item->isEmpty()) {
      $default_value = [
        'number' => $field_item->number,
        'unit' => $field_item->unit ?: 'in',
      ];
    }

    return [
      '#type' => 'physical_measurement',
      '#measurement_type' => 'length',
      '#title' => $title,
      '#default_value' => $default_value,
      '#required' => FALSE,
      '#available_units' => [
        'in',
        'mm',
      ],
      '#description' => $description,
      '#element_validate' => [
        [$this, 'validateLengthMeasurementElement'],
      ],
    ];
  }

  /**
   * Validates that a caliber measurement is non-negative.
   */
  public function validateLengthMeasurementElement(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    if (!is_array($value) || !isset($value['number']) || $value['number'] === '') {
      return;
    }

    if (Calculator::compare($value['number'], '0') < 0) {
      $form_state->setError($element['number'], $this->t('%title must be higher than or equal to %min.', [
        '%title' => $element['#title'],
        '%min' => '0',
      ]));
    }
  }

}
