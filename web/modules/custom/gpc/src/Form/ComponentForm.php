<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Entity\Component;
use Drupal\physical\Calculator;

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
    $bundle = $entity->bundle();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Component name'),
      '#default_value' => $entity->label() ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('The product or model name shown in admin screens and recipe references.'),
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
      '#type' => 'item',
      '#title' => $this->t('Component type'),
      '#markup' => Component::bundleLabel($bundle ?? ''),
      '#description' => $this->t('The bundle is chosen from the add page and cannot be changed here.'),
    ];

    $form['manufacturer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Manufacturer'),
      '#default_value' => $entity->get('manufacturer')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional manufacturer or brand.'),
    ];

    $form['upc'] = $this->buildUpcFieldElement($entity);

    if ($bundle === 'bullet') {
      $form['weight'] = $this->buildDecimalFieldElement($entity, 'weight', $this->t('Bullet weight'), $this->t('Enter the bullet weight in grains.'));
      $form['diameter'] = $this->buildLengthMeasurementElement($entity, 'diameter', $this->t('Bullet diameter'), $this->t('Enter the bullet diameter in inches or millimeters.'));
      $form['length'] = $this->buildLengthMeasurementElement($entity, 'length', $this->t('Bullet length'), $this->t('Enter the bullet length in inches or millimeters.'));
      $form['sectional_density'] = $this->buildDecimalFieldElement($entity, 'sectional_density', $this->t('Sectional density'), $this->t('Enter the sectional density as a unitless decimal.'));
      $form['ballistic_coefficient_value'] = $this->buildDecimalFieldElement($entity, 'ballistic_coefficient_value', $this->t('Ballistic coefficient value'), $this->t('Enter the ballistic coefficient value.'));
      $form['ballistic_coefficient_model'] = [
        '#type' => 'select',
        '#title' => $this->t('Ballistic coefficient model'),
        '#default_value' => $entity->get('ballistic_coefficient_model')->value ?? '',
        '#options' => Component::ballisticCoefficientModelOptions(),
        '#empty_option' => $this->t('- Select -'),
        '#description' => $this->t('Select the drag model used by the ballistic coefficient value.'),
      ];
    }
    elseif ($bundle === 'primer') {
      $form['primer_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Primer type'),
        '#default_value' => $entity->get('primer_type')->value ?? '',
        '#required' => TRUE,
        '#options' => Component::primerTypeOptions(),
        '#empty_option' => $this->t('- Select -'),
        '#description' => $this->t('Select the primer family or format.'),
      ];
    }
    elseif ($bundle === 'brass') {
      $form['case_length'] = $this->buildLengthMeasurementElement($entity, 'case_length', $this->t('Case length'), $this->t('Enter the case length in inches or millimeters.'));
    }

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

  /**
   * Builds a decimal textfield for bundle-specific component data.
   */
  protected function buildDecimalFieldElement($entity, string $field_name, string|\Stringable $title, string|\Stringable $description): array {
    $default_value = $entity->get($field_name)->value ?? '';

    return [
      '#type' => 'number',
      '#title' => $title,
      '#default_value' => $default_value,
      '#min' => 0,
      '#step' => 0.0001,
      '#description' => $description,
      '#element_validate' => [
        [$this, 'validateNonNegativeDecimalElement'],
      ],
    ];
  }

  /**
   * Builds a UPC textfield for component records.
   */
  protected function buildUpcFieldElement($entity): array {
    $default_value = $entity->get('upc')->value ?? '';

    return [
      '#type' => 'textfield',
      '#title' => $this->t('UPC'),
      '#default_value' => $default_value,
      '#maxlength' => 32,
      '#description' => $this->t('Optional UPC or similar product code. Digits, spaces, and hyphens are accepted; digits are stored so leading zeroes are preserved.'),
      '#element_validate' => [
        [$this, 'validateUpcElement'],
      ],
    ];
  }

  /**
   * Builds a physical length measurement element for component units.
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
   * Validates that a numeric component field is not negative.
   */
  public function validateNonNegativeDecimalElement(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $value = $form_state->getValue($element['#parents']);
    if ($value === NULL || $value === '') {
      return;
    }

    if (!is_numeric($value) || (float) $value < 0) {
      $form_state->setError($element, $this->t('%title must be a non-negative number.', [
        '%title' => $element['#title'],
      ]));
    }
  }

  /**
   * Validates that a physical component measurement is not negative.
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

  /**
   * Validates and normalizes a UPC field.
   */
  public function validateUpcElement(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $value = (string) ($form_state->getValue($element['#parents']) ?? '');
    $value = trim($value);
    if ($value === '') {
      return;
    }

    $normalized = Component::normalizeUpc($value);
    if ($normalized === '' || !preg_match('/^\d{8,14}$/', $normalized)) {
      $form_state->setError($element, $this->t('%title must contain 8 to 14 digits.', [
        '%title' => $element['#title'],
      ]));
      return;
    }

    $form_state->setValueForElement($element, $normalized);
  }

}
