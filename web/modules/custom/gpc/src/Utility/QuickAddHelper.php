<?php

declare(strict_types=1);

namespace Drupal\gpc\Utility;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\gpc\Ajax\QuickAddSetAutocompleteValueCommand;

/**
 * Shared helpers for modal quick-add workflows.
 */
final class QuickAddHelper {

  public const QUICK_ADD_QUERY_KEY = 'gpc_quick_add';

  public const TARGET_SELECTOR_KEY = 'gpc_quick_add_target';

  public const QUICK_ADD_LIBRARY = 'gpc/quick_add';

  /**
   * Builds a renderable quick-add action link for an autocomplete field.
   */
  public static function buildQuickAddLink(
    string $route_name,
    array $route_parameters,
    string|TranslatableMarkup $link_text,
    string $target_selector,
    array $query = [],
  ): string {
    $url = Url::fromRoute($route_name, $route_parameters, [
      'query' => $query + [
        static::QUICK_ADD_QUERY_KEY => '1',
        static::TARGET_SELECTOR_KEY => $target_selector,
      ],
      'attributes' => [
        'class' => [
          'button',
          'button--small',
          'use-ajax',
          'gpc-quick-add-link',
        ],
        'data-dialog-type' => 'modal',
      ],
    ]);

    return '<div class="gpc-quick-add-action">' . Link::fromTextAndUrl($link_text, $url)->toString() . '</div>';
  }

  /**
   * Builds a quick-add action link if create access is granted.
   *
   * Returns an empty string when the current account cannot create the target
   * entity or bundle.
   */
  public static function buildQuickAddLinkIfAllowed(
    string $entity_type_id,
    ?string $bundle,
    string $route_name,
    array $route_parameters,
    string|TranslatableMarkup $link_text,
    string $target_selector,
    ?AccountInterface $account = NULL,
    array $query = [],
  ): string {
    if (!static::canCreateEntity($entity_type_id, $bundle, $account)) {
      return '';
    }

    return static::buildQuickAddLink($route_name, $route_parameters, $link_text, $target_selector, $query);
  }

  /**
   * Checks whether the current account may create the referenced entity.
   */
  public static function canCreateEntity(string $entity_type_id, ?string $bundle = NULL, ?AccountInterface $account = NULL): bool {
    return \Drupal::entityTypeManager()
      ->getAccessControlHandler($entity_type_id)
      ->createAccess($bundle, $account, [], TRUE)
      ->isAllowed();
  }

  /**
   * Adds the hidden quick-add target metadata to a form.
   */
  public static function buildQuickAddMetadata(string $target_selector): array {
    return [
      static::TARGET_SELECTOR_KEY => [
        '#type' => 'value',
        '#value' => $target_selector,
      ],
    ];
  }

  /**
   * Returns TRUE when the current form submission is a quick-add AJAX request.
   */
  public static function isQuickAddAjaxRequest(FormStateInterface $form_state): bool {
    $request = \Drupal::requestStack()->getCurrentRequest();
    if (!$request || !$request->isXmlHttpRequest()) {
      return FALSE;
    }

    return static::extractTargetSelector($form_state) !== NULL;
  }

  /**
   * Gets the quick-add target selector from the current form state.
   */
  public static function extractTargetSelector(FormStateInterface $form_state): ?string {
    $value = $form_state->getValue(static::TARGET_SELECTOR_KEY);
    if (is_string($value) && $value !== '') {
      return $value;
    }

    $request_value = \Drupal::requestStack()->getCurrentRequest()?->query->get(static::TARGET_SELECTOR_KEY);
    if (is_string($request_value) && $request_value !== '') {
      return $request_value;
    }

    return NULL;
  }

  /**
   * Builds the AJAX response that restores the parent autocomplete value.
   *
   * The response also surfaces a short status message so users know whether
   * the new shared reference is still pending moderation.
   */
  public static function buildQuickAddAjaxResponse(EntityInterface $entity, ?string $target_selector = NULL, string|TranslatableMarkup|null $entity_label = NULL): AjaxResponse {
    $response = new AjaxResponse();

    if ($target_selector !== NULL && $target_selector !== '') {
      $response->addCommand(new QuickAddSetAutocompleteValueCommand($target_selector, static::buildEntityAutocompleteValue($entity)));
    }

    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new MessageCommand(static::buildQuickAddStatusMessage($entity, $entity_label)));

    return $response;
  }

  /**
   * Builds the success message shown after a quick-add save.
   */
  public static function buildQuickAddStatusMessage(EntityInterface $entity, string|TranslatableMarkup|null $entity_label = NULL): TranslatableMarkup {
    $entity_label ??= mb_strtolower((string) $entity->getEntityType()->getSingularLabel());

    if ($entity->hasField('review_status') && $entity->get('review_status')->value === 'pending') {
      return new TranslatableMarkup('Created the %label %type. It is pending review.', [
        '%label' => $entity->label(),
        '%type' => $entity_label,
      ]);
    }

    return new TranslatableMarkup('Created the %label %type.', [
      '%label' => $entity->label(),
      '%type' => $entity_label,
    ]);
  }

  /**
   * Formats an entity reference value the same way entity_autocomplete expects.
   */
  public static function buildEntityAutocompleteValue(EntityInterface $entity): string {
    return sprintf('%s (%s)', $entity->label(), $entity->id());
  }

  /**
   * Builds a standard quick-add target selector for a top-level form element.
   */
  public static function buildTargetSelector(string $field_name): string {
    return '#edit-' . strtr($field_name, '_', '-');
  }

}
