<?php

declare(strict_types=1);

namespace Drupal\gpc\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Recipe selection handler that searches by recipe code and nickname.
 */
#[EntityReferenceSelection(
  id: 'gpc_recipe',
  label: new TranslatableMarkup('GPC recipe search'),
  group: 'default',
  weight: 1,
  entity_types: ['gpc_recipe'],
)]
class RecipeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery(NULL, $match_operator);

    if (!isset($match) || $match === '') {
      return $query;
    }

    $entity_type = $this->entityTypeManager->getDefinition($this->getConfiguration()['target_type']);
    $label_key = $entity_type->getKey('label');
    if ($label_key === NULL) {
      return $query;
    }

    $or = $query->orConditionGroup()
      ->condition($label_key, $match, $match_operator)
      ->condition('nickname', $match, $match_operator);
    $query->condition($or);

    return $query;
  }

}
