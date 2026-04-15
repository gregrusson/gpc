<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control for caliber entities.
 */
class CaliberAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIf($account->isAuthenticated())
      ->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'delete' && $entity->isNew()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if (in_array($operation, ['view', 'view label'], TRUE)) {
      return AccessResult::allowedIf($account->isAuthenticated())
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }

    $permission = match ($operation) {
      'update' => 'review gpc calibers',
      'delete' => 'delete gpc calibers',
      default => NULL,
    };

    if ($permission !== NULL) {
      return AccessResult::allowedIfHasPermission($account, $permission)
        ->orIf(AccessResult::allowedIfHasPermission($account, 'edit gpc calibers'))
        ->orIf(AccessResult::allowedIfHasPermission($account, 'administer gpc calibers'));
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
