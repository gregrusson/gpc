<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Entity\Component;
use Drupal\gpc\Entity\Recipe;

/**
 * Form controller for recipe add/edit forms.
 */
class RecipeForm extends EntityForm {

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
      $form['id'] = [
        '#type' => 'machine_name',
        '#title' => $this->t('Machine name'),
        '#default_value' => $entity->id() ?? '',
        '#required' => TRUE,
        '#maxlength' => 128,
        '#machine_name' => [
          'exists' => [Recipe::class, 'load'],
          'source' => ['label'],
        ],
        '#description' => $this->t('A unique internal identifier.'),
      ];
    }
    else {
      $form['id'] = [
        '#type' => 'item',
        '#title' => $this->t('Machine name'),
        '#markup' => $entity->id(),
        '#description' => $this->t('This value is fixed after creation.'),
      ];
    }

    $form['caliber'] = $this->buildAutocompleteField($entity, 'caliber', $this->t('Caliber'), 'gpc_caliber', TRUE);
    $form['bullet_component'] = $this->buildAutocompleteField($entity, 'bullet_component', $this->t('Bullet component'), 'gpc_component', TRUE);
    $form['powder_component'] = $this->buildAutocompleteField($entity, 'powder_component', $this->t('Powder component'), 'gpc_component', TRUE);
    $form['primer_component'] = $this->buildAutocompleteField($entity, 'primer_component', $this->t('Primer component'), 'gpc_component', TRUE);
    $form['brass_component'] = $this->buildAutocompleteField($entity, 'brass_component', $this->t('Brass component'), 'gpc_component', FALSE);

    $form['powder_charge_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Powder charge weight'),
      '#default_value' => $entity->get('powder_charge_weight')->value ?? '',
      '#required' => TRUE,
      '#step' => 0.001,
      '#min' => 0,
      '#description' => $this->t('Enter the powder charge weight in grains.'),
    ];

    $form['overall_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Overall length'),
      '#default_value' => $entity->get('overall_length')->value ?? '',
      '#required' => TRUE,
      '#step' => 0.001,
      '#min' => 0,
      '#description' => $this->t('Enter the cartridge overall length.'),
    ];

    $form['crimp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Crimp'),
      '#default_value' => $entity->get('crimp')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional plain-text description of the crimp setting.'),
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

}
