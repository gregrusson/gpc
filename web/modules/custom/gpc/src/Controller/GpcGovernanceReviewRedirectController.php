<?php

declare(strict_types=1);

namespace Drupal\gpc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirects governance menu items to filtered review queues.
 */
class GpcGovernanceReviewRedirectController extends ControllerBase {

  /**
   * Redirects to the caliber review queue.
   */
  public function caliber(): RedirectResponse {
    return new RedirectResponse(Url::fromRoute('entity.gpc_caliber.collection', [], [
      'query' => [
        'review_state' => 'queue',
      ],
    ])->toString());
  }

  /**
   * Redirects to the component review queue.
   */
  public function component(): RedirectResponse {
    return new RedirectResponse(Url::fromRoute('entity.gpc_component.collection', [], [
      'query' => [
        'review_state' => 'queue',
      ],
    ])->toString());
  }

}
