<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers access to the manual merge admin UI.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
final class ReferenceMergeAccessTest extends BrowserTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * Tests that authorized users can view the merge landing page and others cannot.
   */
  public function testMergeUiPermissionIsRequired(): void {
    $admin = $this->drupalCreateUser([
      'administer gpc reference merges',
    ]);
    $this->drupalLogin($admin);

    $this->drupalGet('/admin/content/gpc/reference-merge');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Manual admin maintenance only.');
    $this->assertSession()->pageTextContains('Caliber');
    $this->assertSession()->pageTextContains('Component');

    $this->drupalLogout();

    $anonymous = $this->drupalCreateUser([]);
    $this->drupalLogin($anonymous);

    $this->drupalGet('/admin/content/gpc/reference-merge');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/content/gpc/reference-merge/caliber');
    $this->assertSession()->statusCodeEquals(403);
  }

}
