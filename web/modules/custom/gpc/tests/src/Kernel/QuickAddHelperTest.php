<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\gpc\Utility\QuickAddHelper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies the shared quick-add helper output.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class QuickAddHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'options',
    'datetime',
    'physical',
    'gpc',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('gpc_caliber');
  }

  /**
   * Ensures the helper produces the expected quick-add response payload.
   */
  public function testBuildQuickAddAjaxResponse(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_caliber');
    $caliber = $storage->create([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_quick_add',
    ]);
    $caliber->save();

    $response = QuickAddHelper::buildQuickAddAjaxResponse($caliber, '#edit-caliber');
    $commands = $response->getCommands();

    $this->assertCount(2, $commands);
    $this->assertSame('closeDialog', $commands[0]['command']);
    $this->assertSame('#drupal-modal', $commands[0]['selector']);
    $this->assertSame('gpcQuickAddSetAutocompleteValue', $commands[1]['command']);
    $this->assertSame('#edit-caliber', $commands[1]['selector']);
    $this->assertSame('9mm Luger (' . $caliber->id() . ')', $commands[1]['value']);
  }

  /**
   * Ensures the helper generates the top-level target selector format.
   */
  public function testBuildTargetSelector(): void {
    $this->assertSame('#edit-caliber', QuickAddHelper::buildTargetSelector('caliber'));
    $this->assertSame('#edit-bullet-component', QuickAddHelper::buildTargetSelector('bullet_component'));
  }

}
