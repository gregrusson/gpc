<?php

declare(strict_types=1);

namespace Drupal\gpc\Access;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Allows the GPC overview route when the user can reach any GPC admin tool.
 */
final class GpcAdminOverviewAccessCheck implements AccessInterface {

  /**
   * Routes that should make the overview page visible in navigation.
   *
   * @var string[]
   */
  private const TOOL_ROUTE_NAMES = [
    'entity.gpc_caliber.collection',
    'entity.gpc_component.collection',
    'gpc.caliber_review_queue',
    'gpc.component_review_queue',
    'gpc.reference_merge.landing',
  ];

  /**
   * Constructs a new GPC admin overview access check.
   */
  public function __construct(
    private readonly AccessManagerInterface $accessManager,
  ) {
  }

  /**
   * Checks access to the GPC overview route.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, ?Request $request = NULL): AccessResultInterface {
    $result = AccessResult::allowedIfHasPermission($account, 'access administration pages');

    foreach (self::TOOL_ROUTE_NAMES as $route_name) {
      $result = $result->orIf($this->accessManager->checkNamedRoute($route_name, [], $account, TRUE));
    }

    return $result;
  }

}
