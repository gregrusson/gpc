<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc_theme\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers the frontend drawer navigation rendered by gpc_theme.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
final class GpcDrawerNavigationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gpc',
    'field',
    'filter',
    'options',
    'physical',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'gpc_theme';

  /**
   * Tests that a user with GPC tool access sees nested drawer items.
   */
  public function testDrawerRendersNestedGpcChildren(): void {
    $user = $this->drupalCreateUser([
      'view gpc calibers',
      'review gpc calibers',
      'view gpc components',
      'review gpc components',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#gpc-drawer');
    $this->assertSession()->elementExists('css', '#gpc-drawer a[href="/admin/gpc"]');
    $this->assertSession()->elementExists('css', '#gpc-drawer li.menu-item--expanded');
    $this->assertSession()->elementExists('css', '#gpc-drawer ul.gpc-menu--subtree a[href="/admin/gpc/calibers"]');
    $this->assertSession()->elementExists('css', '#gpc-drawer ul.gpc-menu--subtree a[href="/admin/gpc/components"]');
    $this->assertSession()->elementExists('css', '#gpc-drawer ul.gpc-menu--subtree a[href="/admin/gpc/calibers/review"]');
    $this->assertSession()->elementExists('css', '#gpc-drawer ul.gpc-menu--subtree a[href="/admin/gpc/components/review"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/reference-merge"]');
  }

  /**
   * Tests that unauthorized users do not see GPC child links in the drawer.
   */
  public function testDrawerHidesGpcChildrenWithoutPermission(): void {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/calibers"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/components"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/calibers/review"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/components/review"]');
  }

}
