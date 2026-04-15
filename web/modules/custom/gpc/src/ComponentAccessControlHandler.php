<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control for component entities.
 */
class ComponentAccessControlHandler extends EntityAccessControlHandler {

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
      'update', 'delete' => 'administer gpc components',
      default => NULL,
    };

    if ($permission !== NULL) {
      return AccessResult::allowedIfHasPermission($account, $permission)
        ->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
