<?php

declare(strict_types=1);

namespace Drupal\gpc\ReferenceMerge;

use Drupal\Core\Session\AccountInterface;

/**
 * Executes the limited, confirmed manual merge helper.
 */
interface ReferenceMergeExecutionInterface {

  /**
   * Executes one confirmed manual merge.
   *
   * Only references registered in the discovery layer are repointed.
   * Unsupported or future references remain untouched.
   *
   * @return array<string, mixed>
   *   Structured result data for a result page.
   */
  public function execute(string $merge_type, int $source_entity_id, int $target_entity_id, ?AccountInterface $actor = NULL): array;

}
