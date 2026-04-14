<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for firearm add/edit forms.
 */
class FirearmForm extends GpcEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Firearm name'),
      '#default_value' => $entity->label() ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('The display name for this firearm. Use manufacturer and model where possible.'),
    ];

    $form['caliber'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Caliber'),
      '#target_type' => 'gpc_caliber',
      '#default_value' => $entity->get('caliber')->entity ?? NULL,
      '#required' => TRUE,
      '#selection_handler' => 'default',
      '#selection_settings' => [],
      '#description' => $this->t('Select the caliber associated with this firearm.'),
    ];

    $form['manufacturer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Manufacturer'),
      '#default_value' => $entity->get('manufacturer')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional manufacturer or brand used in the display name.'),
    ];

    $form['model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Model'),
      '#default_value' => $entity->get('model')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional model or series name used in the display name.'),
    ];

    $form['serial_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Serial number'),
      '#default_value' => $entity->get('serial_number')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional serial number or identifying code.'),
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
      ? $this->t('Created the %label firearm.', ['%label' => $entity->label()])
      : $this->t('Updated the %label firearm.', ['%label' => $entity->label()]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

}
