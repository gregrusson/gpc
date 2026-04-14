<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\physical\LengthUnit;
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
    'physical',
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
      'bullet_diameter' => [
        'number' => '9.02',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'case_length' => [
        'number' => '19.15',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'primer_type' => 'small_pistol',
      'neck_diameter' => [
        'number' => '9.65',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'shoulder_diameter' => [
        'number' => '9.93',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'base_diameter' => [
        'number' => '9.93',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'rim_diameter' => [
        'number' => '9.96',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'max_overall_length' => [
        'number' => '29.69',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'notes' => 'Common pistol caliber.',
    ]);
    $caliber->save();

    $loaded = $storage->load($caliber->id());
    $this->assertNotNull($loaded);
    $this->assertSame('9mm_luger', $loaded->get('machine_name')->value);
    $this->assertSame('9mm Luger', $loaded->label());
    $this->assertSame('9mm', $loaded->get('nickname')->value);
    $this->assertSame('9.020000', $loaded->get('bullet_diameter')->number);
    $this->assertSame('mm', $loaded->get('bullet_diameter')->unit);
    $this->assertSame('19.150000', $loaded->get('case_length')->number);
    $this->assertSame('mm', $loaded->get('case_length')->unit);
    $this->assertSame('small_pistol', $loaded->get('primer_type')->value);
    $this->assertSame('9.650000', $loaded->get('neck_diameter')->number);
    $this->assertSame('mm', $loaded->get('neck_diameter')->unit);
    $this->assertSame('9.930000', $loaded->get('shoulder_diameter')->number);
    $this->assertSame('mm', $loaded->get('shoulder_diameter')->unit);
    $this->assertSame('9.930000', $loaded->get('base_diameter')->number);
    $this->assertSame('mm', $loaded->get('base_diameter')->unit);
    $this->assertSame('9.960000', $loaded->get('rim_diameter')->number);
    $this->assertSame('mm', $loaded->get('rim_diameter')->unit);
    $this->assertSame('29.690000', $loaded->get('max_overall_length')->number);
    $this->assertSame('mm', $loaded->get('max_overall_length')->unit);
  }

}
