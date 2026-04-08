<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Entity\Caliber;

/**
 * Form controller for caliber add/edit forms.
 */
class CaliberForm extends EntityForm {

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
          'exists' => [Caliber::class, 'load'],
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

}
