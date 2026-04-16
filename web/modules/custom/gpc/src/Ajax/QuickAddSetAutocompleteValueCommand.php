<?php

declare(strict_types=1);

namespace Drupal\gpc\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command that updates an autocomplete field after a quick add.
 */
final class QuickAddSetAutocompleteValueCommand implements CommandInterface {

  public function __construct(
    protected string $selector,
    protected string $value,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'gpcQuickAddSetAutocompleteValue',
      'selector' => $this->selector,
      'value' => $this->value,
    ];
  }

}
