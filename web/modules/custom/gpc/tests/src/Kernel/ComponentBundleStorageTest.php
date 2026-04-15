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
    $this->assertSame('physical_measurement', $definitions['length']->getType());
    $this->assertSame('length', $definitions['length']->getSetting('measurement_type'));
    $this->assertSame('physical_measurement', $definitions['case_length']->getType());
    $this->assertSame('length', $definitions['case_length']->getSetting('measurement_type'));
    $this->assertSame('decimal', $definitions['ballistic_coefficient_value']->getType());
    $this->assertSame(4, $definitions['ballistic_coefficient_value']->getSetting('scale'));
    $this->assertSame('list_string', $definitions['ballistic_coefficient_model']->getType());
    $this->assertSame([
      'G1',
      'G7',
    ], array_keys($definitions['ballistic_coefficient_model']->getSetting('allowed_values')));
    $this->assertSame('string', $definitions['upc']->getType());
    $this->assertSame(14, $definitions['upc']->getSetting('max_length'));

    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_component');

    $bullet = $storage->create([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj',
      'component_type' => 'bullet',
      'manufacturer' => 'Example Co.',
      'upc' => '000123456789',
      'weight' => '147.500',
      'diameter' => [
        'number' => '0.3550',
        'unit' => LengthUnit::INCH,
      ],
      'length' => [
        'number' => '0.5750',
        'unit' => LengthUnit::INCH,
      ],
      'sectional_density' => '0.1670',
      'ballistic_coefficient_value' => '0.4350',
      'ballistic_coefficient_model' => 'G7',
      'notes' => 'Practice bullet.',
    ]);
    $bullet->save();

    $primer = $storage->create([
      'label' => 'CCI 300',
      'machine_name' => 'cci_300',
      'component_type' => 'primer',
      'manufacturer' => 'CCI',
      'upc' => '00091918003003',
      'primer_type' => 'large_pistol',
      'notes' => 'Standard primer.',
    ]);
    $primer->save();

    $brass = $storage->create([
      'label' => 'Starline 10mm',
      'machine_name' => 'starline_10mm',
      'component_type' => 'brass',
      'manufacturer' => 'Starline',
      'upc' => '00622404310125',
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
    $this->assertSame('000123456789', $loaded_bullet->get('upc')->value);
    $this->assertSame('147.500', $loaded_bullet->get('weight')->value);
    $this->assertSame('0.355000', $loaded_bullet->get('diameter')->number);
    $this->assertSame('in', $loaded_bullet->get('diameter')->unit);
    $this->assertSame('0.575000', $loaded_bullet->get('length')->number);
    $this->assertSame('in', $loaded_bullet->get('length')->unit);
    $this->assertSame('0.1670', $loaded_bullet->get('sectional_density')->value);
    $this->assertSame('0.4350', $loaded_bullet->get('ballistic_coefficient_value')->value);
    $this->assertSame('G7', $loaded_bullet->get('ballistic_coefficient_model')->value);
    $this->assertSame('Practice bullet.', $loaded_bullet->get('notes')->value);

    $loaded_primer = $storage->load($primer->id());
    $this->assertNotNull($loaded_primer);
    $this->assertSame('primer', $loaded_primer->bundle());
    $this->assertSame('CCI', $loaded_primer->get('manufacturer')->value);
    $this->assertSame('00091918003003', $loaded_primer->get('upc')->value);
    $this->assertSame('large_pistol', $loaded_primer->get('primer_type')->value);
    $this->assertSame('Standard primer.', $loaded_primer->get('notes')->value);

    $loaded_brass = $storage->load($brass->id());
    $this->assertNotNull($loaded_brass);
    $this->assertSame('brass', $loaded_brass->bundle());
    $this->assertSame('Starline', $loaded_brass->get('manufacturer')->value);
    $this->assertSame('00622404310125', $loaded_brass->get('upc')->value);
    $this->assertSame('31.750000', $loaded_brass->get('case_length')->number);
    $this->assertSame('mm', $loaded_brass->get('case_length')->unit);
    $this->assertSame('Once-fired brass.', $loaded_brass->get('notes')->value);
  }

  /**
   * Ensures UPC values are searchable through component reference lookups.
   */
  public function testComponentSelectionSearchMatchesUpc(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_component');
    $storage->create([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj_searchable',
      'component_type' => 'bullet',
      'upc' => '001234567890',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $selection_handler */
    $selection_handler = $this->container->get('plugin.manager.entity_reference_selection')->getInstance([
      'target_type' => 'gpc_component',
      'handler' => 'default',
    ]);

    $this->assertSame(1, $selection_handler->countReferenceableEntities('001234567890'));
    $this->assertSame([
      'bullet' => [
        1 => '147gr FMJ',
      ],
    ], $selection_handler->getReferenceableEntities('001234567890'));
  }

}
