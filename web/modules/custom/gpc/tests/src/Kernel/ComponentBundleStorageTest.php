<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\physical\LengthUnit;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies Component bundle field storage and persistence.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class ComponentBundleStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'physical',
    'gpc',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('gpc_component');
  }

  /**
   * Ensures the supported Component bundles persist their shared and specific fields.
   */
  public function testComponentBundlesPersistSharedAndSpecificFields(): void {
    $definitions = $this->container->get('entity_field.manager')->getFieldStorageDefinitions('gpc_component');
    $this->assertSame('decimal', $definitions['weight']->getType());
    $this->assertSame('physical_measurement', $definitions['diameter']->getType());
    $this->assertSame('length', $definitions['diameter']->getSetting('measurement_type'));
    $this->assertSame('physical_measurement', $definitions['case_length']->getType());
    $this->assertSame('length', $definitions['case_length']->getSetting('measurement_type'));

    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_component');

    $bullet = $storage->create([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj',
      'component_type' => 'bullet',
      'manufacturer' => 'Example Co.',
      'weight' => '147.500',
      'diameter' => [
        'number' => '0.3550',
        'unit' => LengthUnit::INCH,
      ],
      'sectional_density' => '0.1670',
      'notes' => 'Practice bullet.',
    ]);
    $bullet->save();

    $primer = $storage->create([
      'label' => 'CCI 300',
      'machine_name' => 'cci_300',
      'component_type' => 'primer',
      'manufacturer' => 'CCI',
      'primer_type' => 'large_pistol',
      'notes' => 'Standard primer.',
    ]);
    $primer->save();

    $brass = $storage->create([
      'label' => 'Starline 10mm',
      'machine_name' => 'starline_10mm',
      'component_type' => 'brass',
      'manufacturer' => 'Starline',
      'case_length' => [
        'number' => '31.750',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'notes' => 'Once-fired brass.',
    ]);
    $brass->save();

    $loaded_bullet = $storage->load($bullet->id());
    $this->assertNotNull($loaded_bullet);
    $this->assertSame('bullet', $loaded_bullet->bundle());
    $this->assertSame('Example Co.', $loaded_bullet->get('manufacturer')->value);
    $this->assertSame('147.500', $loaded_bullet->get('weight')->value);
    $this->assertSame('0.355000', $loaded_bullet->get('diameter')->number);
    $this->assertSame('in', $loaded_bullet->get('diameter')->unit);
    $this->assertSame('0.1670', $loaded_bullet->get('sectional_density')->value);
    $this->assertSame('Practice bullet.', $loaded_bullet->get('notes')->value);

    $loaded_primer = $storage->load($primer->id());
    $this->assertNotNull($loaded_primer);
    $this->assertSame('primer', $loaded_primer->bundle());
    $this->assertSame('CCI', $loaded_primer->get('manufacturer')->value);
    $this->assertSame('large_pistol', $loaded_primer->get('primer_type')->value);
    $this->assertSame('Standard primer.', $loaded_primer->get('notes')->value);

    $loaded_brass = $storage->load($brass->id());
    $this->assertNotNull($loaded_brass);
    $this->assertSame('brass', $loaded_brass->bundle());
    $this->assertSame('Starline', $loaded_brass->get('manufacturer')->value);
    $this->assertSame('31.750000', $loaded_brass->get('case_length')->number);
    $this->assertSame('mm', $loaded_brass->get('case_length')->unit);
    $this->assertSame('Once-fired brass.', $loaded_brass->get('notes')->value);
  }

}
