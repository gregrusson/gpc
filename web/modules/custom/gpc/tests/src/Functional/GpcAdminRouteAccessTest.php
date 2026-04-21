<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers access to the GPC admin route without exposing a nested main menu.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
final class GpcAdminRouteAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
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
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:main', [
      'region' => 'primary_menu',
      'label_display' => FALSE,
    ]);
  }

  /**
   * Tests that the main navigation no longer renders a nested GPC subtree.
   */
  public function testMainNavigationDoesNotRenderGpcSubtree(): void {
    $user = $this->drupalCreateUser([
      'view gpc calibers',
      'view gpc components',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/calibers"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/components"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/calibers/review"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/components/review"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/reference-merge"]');

    $this->drupalGet('/admin/gpc');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/admin/gpc/calibers');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/admin/gpc/components');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the admin route still denies access when permissions are absent.
   */
  public function testAdminRouteDeniedWithoutPermission(): void {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/gpc');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/gpc/calibers');
    $this->assertSession()->statusCodeEquals(403);
  }

}
