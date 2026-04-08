<?php

declare(strict_types=1);

namespace Drupal\gpc\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides admin HTML routes for recipe entities.
 */
class RecipeHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    if ($route instanceof Route) {
      $route->setOption('_admin_route', TRUE);
    }

    return $route;
  }

}
