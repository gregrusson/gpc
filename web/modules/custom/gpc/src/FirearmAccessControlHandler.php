<?php

declare(strict_types=1);

namespace Drupal\gpc;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Access control for firearm entities.
 */
class FirearmAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create gpc firearms', 'administer gpc firearms'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer gpc firearms')) {
      return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($entity);
    }

    if (!$entity instanceof EntityOwnerInterface) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();
    $access = AccessResult::allowedIf($is_owner)
      ->cachePerUser()
      ->addCacheableDependency($entity);

    return match ($operation) {
      'view', 'view label' => $access,
      'update' => $access->andIf(AccessResult::allowedIfHasPermission($account, 'edit gpc firearms')),
      'delete' => $access->andIf(AccessResult::allowedIfHasPermission($account, 'delete gpc firearms')),
      default => AccessResult::neutral()->addCacheableDependency($entity),
    };
  }

}
