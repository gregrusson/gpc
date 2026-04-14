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
      'nickname' => '9mm',
      'bullet_diameter' => '0.355',
      'case_length' => '0.754',
      'primer_type' => 'small_pistol',
      'neck_diameter' => '0.380',
      'shoulder_diameter' => '0.391',
      'base_diameter' => '0.391',
      'rim_diameter' => '0.392',
      'max_overall_length' => '1.169',
      'notes' => 'Common pistol caliber.',
    ]);
    $caliber->save();

    $loaded = $storage->load($caliber->id());
    $this->assertNotNull($loaded);
    $this->assertSame('9mm_luger', $loaded->get('machine_name')->value);
    $this->assertSame('9mm Luger', $loaded->label());
    $this->assertSame('9mm', $loaded->get('nickname')->value);
    $this->assertSame('0.355', $loaded->get('bullet_diameter')->value);
    $this->assertSame('0.754', $loaded->get('case_length')->value);
    $this->assertSame('small_pistol', $loaded->get('primer_type')->value);
    $this->assertSame('0.380', $loaded->get('neck_diameter')->value);
    $this->assertSame('0.391', $loaded->get('shoulder_diameter')->value);
    $this->assertSame('0.391', $loaded->get('base_diameter')->value);
    $this->assertSame('0.392', $loaded->get('rim_diameter')->value);
    $this->assertSame('1.169', $loaded->get('max_overall_length')->value);
  }

}
