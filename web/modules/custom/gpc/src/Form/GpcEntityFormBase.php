<?php

declare(strict_types=1);

namespace Drupal\gpc\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for GPC content entity forms.
 */
abstract class GpcEntityFormBase extends EntityForm {

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

