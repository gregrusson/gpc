<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\physical\LengthUnit;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies the limited manual merge reference discovery layer.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class ReferenceDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'physical',
    'gpc',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('gpc_caliber');
    $this->installEntitySchema('gpc_component');
    $this->installEntitySchema('gpc_recipe');
    $this->installEntitySchema('gpc_firearm');
  }

  /**
   * Ensures Caliber and Component inbound references are discovered explicitly.
   */
  public function testDiscoverSupportedCaliberAndComponentReferences(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');

    $caliber_storage = $entity_type_manager->getStorage('gpc_caliber');
    $component_storage = $entity_type_manager->getStorage('gpc_component');
    $recipe_storage = $entity_type_manager->getStorage('gpc_recipe');
    $firearm_storage = $entity_type_manager->getStorage('gpc_firearm');

    $caliber = $caliber_storage->create([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_reference',
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
    ]);
    $caliber->save();

    $bullet = $component_storage->create([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj_reference',
      'component_type' => 'bullet',
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
      'ballistic_coefficient_model' => 'G7',
    ]);
    $bullet->save();

    $powder = $component_storage->create([
      'label' => 'Example Powder',
      'machine_name' => 'example_powder_reference',
      'component_type' => 'powder',
    ]);
    $powder->save();

    $primer = $component_storage->create([
      'label' => 'CCI 300',
      'machine_name' => 'cci_300_reference',
      'component_type' => 'primer',
    ]);
    $primer->save();

    $firearm = $firearm_storage->create([
      'label' => 'Reference pistol',
      'type' => 'pistol',
      'caliber' => $caliber->id(),
    ]);
    $firearm->save();

    $recipe = $recipe_storage->create([
      'label' => 'Reference load',
      'machine_name' => 'reference_load',
      'caliber' => $caliber->id(),
      'bullet_component' => $bullet->id(),
      'powder_component' => $powder->id(),
      'primer_component' => $primer->id(),
      'powder_charge_weight' => '4.500',
      'overall_length' => [
        'number' => '1.120',
        'unit' => LengthUnit::INCH,
      ],
    ]);
    $recipe->save();

    $discovery = $this->container->get('gpc.reference_discovery');

    $caliber_report = $discovery->discover('gpc_caliber', (int) $caliber->id());
    $this->assertSame(2, $caliber_report['totals']['overall']);
    $this->assertSame([
      'gpc_firearm' => 1,
      'gpc_recipe' => 1,
    ], $caliber_report['totals']['by_entity_type']);
    $this->assertSame([
      'gpc_firearm.caliber' => 1,
      'gpc_recipe.caliber' => 1,
    ], $caliber_report['totals']['by_entity_type_and_field']);
    $this->assertSame('gpc_caliber', $caliber_report['target']['entity_type_id']);
    $this->assertSame((int) $caliber->id(), $caliber_report['target']['entity_id']);
    $this->assertSame('9mm Luger', $caliber_report['target']['label']);

    $caliber_reference_types = array_column($caliber_report['references'], 'referencing_entity_type_id');
    $this->assertContains('gpc_firearm', $caliber_reference_types);
    $this->assertContains('gpc_recipe', $caliber_reference_types);

    $component_report = $discovery->discover('gpc_component', (int) $bullet->id());
    $this->assertSame(1, $component_report['totals']['overall']);
    $this->assertSame([
      'gpc_recipe' => 1,
    ], $component_report['totals']['by_entity_type']);
    $this->assertSame([
      'gpc_recipe.bullet_component' => 1,
    ], $component_report['totals']['by_entity_type_and_field']);
    $this->assertSame('gpc_component', $component_report['target']['entity_type_id']);
    $this->assertSame((int) $bullet->id(), $component_report['target']['entity_id']);
    $this->assertSame('147gr FMJ', $component_report['target']['label']);

    $component_reference = $component_report['references'][0] ?? NULL;
    $this->assertIsArray($component_reference);
    $this->assertSame('gpc_recipe', $component_reference['referencing_entity_type_id']);
    $this->assertSame('bullet_component', $component_reference['field_name']);
    $this->assertSame((int) $recipe->id(), $component_reference['referencing_entity_id']);
    $this->assertSame('Reference load', $component_reference['referencing_entity_label']);
  }

}
