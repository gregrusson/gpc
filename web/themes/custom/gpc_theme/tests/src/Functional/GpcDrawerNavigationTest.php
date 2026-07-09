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
   * Tests that a user with GPC tool access sees the bottom admin utility.
   */
  public function testDrawerRendersGpcAdminUtilityForAuthorizedUsers(): void {
    $user = $this->drupalCreateUser([
      'view gpc calibers',
      'view gpc components',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#main-content.gpc-main');
    $this->assertSession()->elementNotExists('css', '.gpc-main__content');
    $this->assertSession()->elementExists('css', '#gpc-drawer');
    $this->assertSession()->elementExists('css', '#gpc-drawer .gpc-app-drawer__main');
    $this->assertSession()->elementExists('css', '#gpc-drawer .gpc-app-drawer__utility');
    $this->assertSession()->elementExists('xpath', '//aside[@id="gpc-drawer"]//div[contains(@class,"gpc-app-drawer__main")]/following-sibling::div[contains(@class,"gpc-app-drawer__utility")]');
    $this->assertSession()->elementExists('css', '#gpc-drawer .gpc-app-drawer__utility a[href="/admin/gpc"]');
    $this->assertSession()->linkExists('GPC Admin');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/calibers"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/components"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/calibers/review"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/components/review"]');
  }

  /**
   * Tests that unauthorized users do not see the admin utility link.
   */
  public function testDrawerHidesGpcAdminUtilityWithoutPermission(): void {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#main-content.gpc-main');
    $this->assertSession()->elementNotExists('css', '.gpc-main__content');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer .gpc-app-drawer__utility');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer .gpc-app-drawer__main + .gpc-app-drawer__utility');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/calibers"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/components"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/calibers/review"]');
    $this->assertSession()->elementNotExists('css', '#gpc-drawer a[href="/admin/gpc/components/review"]');
  }

}
