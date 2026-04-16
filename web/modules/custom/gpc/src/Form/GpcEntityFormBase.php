<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gpc\Utility\QuickAddHelper;

/**
 * Base form for GPC content entity forms.
 */
abstract class GpcEntityFormBase extends EntityForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (isset($actions['submit']) && QuickAddHelper::extractTargetSelector($form_state) !== NULL) {
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxSubmit',
      ];
    }

    return $actions;
  }

  /**
   * Submit form #ajax callback for quick-add modal submissions.
   *
   * This mirrors Drupal's standard AJAX form pattern: validation failures
   * replace the current form in-place, while successful submissions return the
   * response already prepared by the entity form's save handler.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    if ($form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;

      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
      return $response;
    }

    $response = $form_state->getResponse();
    if ($response instanceof AjaxResponse) {
      return $response;
    }

    return new AjaxResponse();
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      if ($entity instanceof \Drupal\Core\Entity\FieldableEntityInterface && !$entity->hasField($key)) {
        continue;
      }
      $entity->set($key, $value);
    }
  }

}
