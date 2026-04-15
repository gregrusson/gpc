<?php

declare(strict_types=1);

namespace Drupal\gpc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Builds the logged-in GPC dashboard.
 */
class GpcDashboardController extends ControllerBase {

  /**
   * Builds the dashboard page.
   */
  public function build(): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gpc-dashboard'],
      ],
      'intro' => [
        '#markup' => '<p>' . $this->t('Use the links below to work with your firearms, recipes, and batches.'). '</p>',
      ],
      'sections' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['gpc-dashboard__sections'],
        ],
        'firearms' => $this->buildSection(
          $this->t('Firearms'),
          $this->t('Manage your owned firearm records.'),
          'entity.gpc_firearm.collection',
          'entity.gpc_firearm.add_form',
          'create gpc firearms'
        ),
        'recipes' => $this->buildSection(
          $this->t('Recipes'),
          $this->t('Manage your reusable recipe records.'),
          'entity.gpc_recipe.collection',
          'entity.gpc_recipe.add_form',
          'create gpc recipes'
        ),
        'batches' => $this->buildSection(
          $this->t('Batches'),
          $this->t('Review produced batches derived from recipes.'),
          'entity.gpc_batch.collection',
          'entity.gpc_batch.add_form',
          'create gpc batches'
        ),
      ],
    ];
  }

  /**
   * Builds one dashboard section.
   */
  protected function buildSection(string|\Stringable $title, string|\Stringable $description, string $collection_route, string $add_route, string $create_permission): array {
    $items = [
      Link::fromTextAndUrl($this->t('View %title', ['%title' => $title]), Url::fromRoute($collection_route)),
    ];

    if ($this->currentUser()->hasPermission($create_permission)) {
      $items[] = Link::fromTextAndUrl($this->t('Add %title', ['%title' => $title]), Url::fromRoute($add_route));
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gpc-dashboard__section'],
      ],
      'title' => [
        '#markup' => '<h2>' . $title . '</h2>',
      ],
      'description' => [
        '#markup' => '<p>' . $description . '</p>',
      ],
      'links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

}
