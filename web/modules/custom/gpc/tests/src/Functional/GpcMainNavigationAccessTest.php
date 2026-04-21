<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers main-navigation exposure for the GPC admin tools.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
final class GpcMainNavigationAccessTest extends BrowserTestBase {

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
   * Tests that a limited GPC user sees the matching main-nav link.
   */
  public function testCaliberAccessShowsTheMainNavGpcSection(): void {
    $user = $this->drupalCreateUser([
      'view gpc calibers',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu');
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu a[href="/admin/gpc"]');
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu a[href="/admin/gpc/calibers"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/components"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/reference-merge"]');

    $this->drupalGet('/admin/gpc');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Calibers');
    $this->assertSession()->pageTextNotContains('Components');

    $this->drupalGet('/admin/gpc/calibers');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/admin/gpc/components');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that users without GPC access do not see the main-nav entry.
   */
  public function testUsersWithoutGpcAccessDoNotSeeTheSection(): void {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/calibers"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/components"]');

    $this->drupalGet('/admin/gpc');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/gpc/calibers');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that a component-only user still reaches the shared GPC overview.
   */
  public function testComponentAccessStillUnlocksTheParentMenu(): void {
    $user = $this->drupalCreateUser([
      'view gpc components',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/dashboard');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu');
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu a[href="/admin/gpc"]');
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu a[href="/admin/gpc/components"]');
    $this->assertSession()->elementNotExists('css', '#block-olivero-main-menu a[href="/admin/gpc/calibers"]');

    $this->drupalGet('/admin/gpc');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Components');
    $this->assertSession()->pageTextNotContains('Calibers');

    $this->drupalGet('/admin/gpc/components');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/admin/gpc/reference-merge');
    $this->assertSession()->statusCodeEquals(403);
  }

}
