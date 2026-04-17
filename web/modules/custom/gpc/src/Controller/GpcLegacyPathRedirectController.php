<?php

declare(strict_types=1);

namespace Drupal\gpc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirects legacy /gpc URLs to the cleaned public routes.
 */
final class GpcLegacyPathRedirectController extends ControllerBase {

  /**
   * Returns a redirect response to the target route.
   */
  private function redirectToRoute(string $route_name, array $route_parameters = []): RedirectResponse {
    return new RedirectResponse(Url::fromRoute($route_name, $route_parameters)->toString(), 301);
  }

  public function dashboard(): RedirectResponse {
    return $this->redirectToRoute('gpc.dashboard');
  }

  public function firearmCollection(): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_firearm.collection');
  }

  public function firearmAdd(): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_firearm.add_form');
  }

  public function firearmCanonical(string $gpc_firearm): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_firearm.canonical', ['gpc_firearm' => $gpc_firearm]);
  }

  public function firearmEdit(string $gpc_firearm): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_firearm.edit_form', ['gpc_firearm' => $gpc_firearm]);
  }

  public function firearmDelete(string $gpc_firearm): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_firearm.delete_form', ['gpc_firearm' => $gpc_firearm]);
  }

  public function recipeCollection(): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_recipe.collection');
  }

  public function recipeAdd(): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_recipe.add_form');
  }

  public function recipeCanonical(string $gpc_recipe): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_recipe.canonical', ['gpc_recipe' => $gpc_recipe]);
  }

  public function recipeEdit(string $gpc_recipe): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_recipe.edit_form', ['gpc_recipe' => $gpc_recipe]);
  }

  public function recipeDelete(string $gpc_recipe): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_recipe.delete_form', ['gpc_recipe' => $gpc_recipe]);
  }

  public function batchCollection(): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_batch.collection');
  }

  public function batchAdd(): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_batch.add_form');
  }

  public function batchCanonical(string $gpc_batch): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_batch.canonical', ['gpc_batch' => $gpc_batch]);
  }

  public function batchEdit(string $gpc_batch): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_batch.edit_form', ['gpc_batch' => $gpc_batch]);
  }

  public function batchDelete(string $gpc_batch): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_batch.delete_form', ['gpc_batch' => $gpc_batch]);
  }

  public function caliberCanonical(string $gpc_caliber): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_caliber.canonical', ['gpc_caliber' => $gpc_caliber]);
  }

  public function componentCanonical(string $gpc_component): RedirectResponse {
    return $this->redirectToRoute('entity.gpc_component.canonical', ['gpc_component' => $gpc_component]);
  }

}
