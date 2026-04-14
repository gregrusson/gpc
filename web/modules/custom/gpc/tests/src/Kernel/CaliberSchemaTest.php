<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies Caliber schema updates and storage behavior.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class CaliberSchemaTest extends KernelTestBase {

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
    'gpc',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('gpc_caliber');

    require_once DRUPAL_ROOT . '/modules/custom/gpc/gpc.install';
  }

  /**
   * Ensures the Caliber update hook can rebuild the table and save entities.
   */
  public function testCaliberSchemaCanBeRebuiltAndEntityCanBeSaved(): void {
    $database = $this->container->get('database');
    $schema = $database->schema();

    $this->assertTrue($schema->tableExists('gpc_caliber'));

    $schema->dropTable('gpc_caliber');
    $this->assertFalse($schema->tableExists('gpc_caliber'));

    \gpc_update_10010();

    $this->assertTrue($schema->tableExists('gpc_caliber'));

    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_caliber');
    $caliber = $storage->create([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger',
      'bullet_diameter' => '0.355',
      'case_length' => '0.754',
      'primer_type' => 'boxer',
      'notes' => 'Common pistol caliber.',
    ]);
    $caliber->save();

    $loaded = $storage->load($caliber->id());
    $this->assertNotNull($loaded);
    $this->assertSame('9mm_luger', $loaded->get('machine_name')->value);
    $this->assertSame('9mm Luger', $loaded->label());
    $this->assertSame('0.355', $loaded->get('bullet_diameter')->value);
    $this->assertSame('0.754', $loaded->get('case_length')->value);
    $this->assertSame('boxer', $loaded->get('primer_type')->value);
  }

}
