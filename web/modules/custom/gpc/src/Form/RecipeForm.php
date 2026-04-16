<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Entity\Component;
use Drupal\gpc\Entity\Recipe;
use Drupal\gpc\Utility\QuickAddHelper;
use Drupal\physical\Calculator;

/**
 * Form controller for recipe add/edit forms.
 */
class RecipeForm extends GpcEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipe code'),
      '#default_value' => $entity->label() ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('The primary human-facing identifier for the recipe.'),
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
        '#description' => $this->t('A unique internal identifier derived from the recipe code.'),
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

    $form['nickname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nickname'),
      '#default_value' => $entity->get('nickname')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional nickname or alternate label.'),
    ];

    $form['caliber'] = $this->buildAutocompleteField($entity, 'caliber', $this->t('Caliber'), 'gpc_caliber', TRUE, NULL, 'entity.gpc_caliber.add_form', [], $this->t('Add caliber'));
    $form['bullet_component'] = $this->buildAutocompleteField($entity, 'bullet_component', $this->t('Bullet component'), 'gpc_component', TRUE, $this->t('Expected type: bullet.'), 'entity.gpc_component.add_form', ['component_type' => 'bullet'], $this->t('Add bullet'), 'bullet');
    $form['powder_component'] = $this->buildAutocompleteField($entity, 'powder_component', $this->t('Powder component'), 'gpc_component', TRUE, $this->t('Expected type: powder.'), 'entity.gpc_component.add_form', ['component_type' => 'powder'], $this->t('Add powder'), 'powder');
    $form['primer_component'] = $this->buildAutocompleteField($entity, 'primer_component', $this->t('Primer component'), 'gpc_component', TRUE, $this->t('Expected type: primer.'), 'entity.gpc_component.add_form', ['component_type' => 'primer'], $this->t('Add primer'), 'primer');
    $form['brass_component'] = $this->buildAutocompleteField($entity, 'brass_component', $this->t('Brass component'), 'gpc_component', FALSE, $this->t('Expected type: brass if set.'), 'entity.gpc_component.add_form', ['component_type' => 'brass'], $this->t('Add brass'), 'brass');

    $form['powder_charge_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Powder charge weight'),
      '#default_value' => $entity->get('powder_charge_weight')->value ?? '',
      '#required' => TRUE,
      '#step' => 0.001,
      '#min' => 0,
      '#description' => $this->t('Enter the powder charge weight in grains.'),
    ];

    $form['overall_length'] = $this->buildLengthMeasurementElement(
      $entity,
      'overall_length',
      $this->t('Overall length'),
      $this->t('Enter the cartridge overall length in inches or millimeters.')
    );

    $form['crimp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Crimped'),
      '#default_value' => !empty($entity->get('crimp')->value),
      '#description' => $this->t('Check this box if the recipe is crimped.'),
    ];

    $form['estimated_round_cost'] = [
      '#type' => 'number',
      '#title' => $this->t('Estimated round cost'),
      '#default_value' => $entity->get('estimated_round_cost')->value ?? '',
      '#step' => 0.0001,
      '#min' => 0,
      '#description' => $this->t('Optional estimated cost per round.'),
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
      ? $this->t('Created the %label recipe.', ['%label' => $entity->label()])
      : $this->t('Updated the %label recipe.', ['%label' => $entity->label()]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $recipe_code = trim((string) ($form_state->getValue('label') ?? ''));
    if ($recipe_code === '') {
      $form_state->setErrorByName('label', $this->t('Recipe code is required.'));
    }

    foreach (Recipe::componentFieldTypes() as $field_name => $required_type) {
      $target_id = $form_state->getValue([$field_name, 0, 'target_id']);
      if (!$target_id) {
        continue;
      }

      $component = \Drupal::entityTypeManager()->getStorage('gpc_component')->load($target_id);
      if (!$component instanceof Component) {
        $form_state->setErrorByName($field_name, $this->t('The selected component is not valid.'));
        continue;
      }

      $actual_type = $component->bundle();
      if ($actual_type !== $required_type) {
        $form_state->setErrorByName($field_name, $this->t('The selected component must be a @type component.', [
          '@type' => Component::bundleLabel($required_type),
        ]));
      }
    }
  }

  /**
   * Builds one autocomplete field, optionally with a quick-add action.
   */
  protected function buildAutocompleteField(EntityInterface $entity, string $field_name, string|\Stringable $title, string $target_type, bool $required, string|\Stringable|null $description = NULL, ?string $quick_add_route_name = NULL, array $quick_add_route_parameters = [], string|\Stringable|null $quick_add_link_text = NULL, ?string $quick_add_bundle = NULL): array {
    $default_value = NULL;
    $target_id = $entity->get($field_name)->first()?->target_id ?? NULL;
    if ($target_id) {
      $default_value = \Drupal::entityTypeManager()->getStorage($target_type)->load($target_id);
    }

    $element = [
      '#type' => 'entity_autocomplete',
      '#title' => $title,
      '#target_type' => $target_type,
      '#default_value' => $default_value,
      '#required' => $required,
      '#selection_handler' => 'default',
      '#selection_settings' => [],
      '#description' => $description ?? ($required
        ? $this->t('Select a referenced entity.')
        : $this->t('Optional reference.')),
    ];

    if ($quick_add_route_name !== NULL) {
      $quick_add_link = QuickAddHelper::buildQuickAddLinkIfAllowed(
        $target_type,
        $quick_add_bundle,
        $quick_add_route_name,
        $quick_add_route_parameters,
        $quick_add_link_text ?? $this->t('Add item'),
        QuickAddHelper::buildTargetSelector($field_name),
        $this->currentUser(),
      );
      if ($quick_add_link !== '') {
        $element['#suffix'] = $quick_add_link;
        $element['#attached']['library'][] = 'core/drupal.dialog.ajax';
      }
    }

    return $element;
  }

  /**
   * Checks whether a machine name already exists.
   */
  public static function machineNameExists(string $machine_name): bool {
    $storage = \Drupal::entityTypeManager()->getStorage('gpc_recipe');
    return (bool) $storage->loadByProperties(['machine_name' => $machine_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\gpc\Entity\Recipe $entity */
    $entity = parent::buildEntity($form, $form_state);

    $recipe_code = trim((string) ($form_state->getValue('label') ?? ''));
    if ($recipe_code !== '') {
      $entity->set('label', $recipe_code);
    }

    $nickname = trim((string) ($form_state->getValue('nickname') ?? ''));
    $entity->set('nickname', $nickname === '' ? NULL : $nickname);

    return $entity;
  }

  /**
   * Builds a physical length measurement element for recipe OAL.
   */
  protected function buildLengthMeasurementElement(EntityInterface $entity, string $field_name, string|\Stringable $title, string|\Stringable $description): array {
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
      '#required' => TRUE,
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
   * Validates that the recipe overall length is non-negative.
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
