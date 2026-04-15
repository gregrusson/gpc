<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\gpc\Form\ReferenceMergeExecuteForm;
use Drupal\physical\LengthUnit;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies the confirmed manual merge execution step.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class ReferenceMergeExecutionTest extends KernelTestBase {

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
    $this->installEntitySchema('gpc_component');
    $this->installEntitySchema('gpc_recipe');
    $this->installEntitySchema('gpc_firearm');
  }

  /**
   * Tests that caliber merges repoint references and annotate the source.
   */
  public function testExecuteRepointsCaliberReferences(): void {
    $source = $this->createCaliber([
      'label' => '9mm Para',
      'machine_name' => '9mm_para_execute',
      'nickname' => '9mm para',
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
    $target = $this->createCaliber([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_execute',
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
    $firearm = $this->createFirearm([
      'label' => 'Test pistol',
      'type' => 'pistol',
      'caliber' => $source->id(),
    ]);
    $recipe = $this->createRecipe([
      'label' => 'Test load',
      'machine_name' => 'test_load_execute',
      'caliber' => $source->id(),
      'bullet_component' => $this->createComponent([
        'label' => '147gr FMJ',
        'machine_name' => '147gr_fmj_execute',
        'component_type' => 'bullet',
      ])->id(),
      'powder_component' => $this->createComponent([
        'label' => 'Example Powder',
        'machine_name' => 'example_powder_execute',
        'component_type' => 'powder',
      ])->id(),
      'primer_component' => $this->createComponent([
        'label' => 'CCI 300',
        'machine_name' => 'cci_300_execute',
        'component_type' => 'primer',
      ])->id(),
      'powder_charge_weight' => '4.500',
      'overall_length' => [
        'number' => '1.120',
        'unit' => LengthUnit::INCH,
      ],
    ]);

    $service = $this->container->get('gpc.reference_merge_execution');
    $result = $service->execute('caliber', (int) $source->id(), (int) $target->id(), $this->container->get('current_user'));

    $this->assertSame('success', $result['status']);
    $this->assertSame(2, $result['updated_entity_count']);
    $this->assertSame(2, $result['updated_field_count']);
    $this->assertSame([], $result['failures']);

    $refreshed_firearm = $this->container->get('entity_type.manager')->getStorage('gpc_firearm')->load($firearm->id());
    $refreshed_recipe = $this->container->get('entity_type.manager')->getStorage('gpc_recipe')->load($recipe->id());
    $refreshed_source = $this->container->get('entity_type.manager')->getStorage('gpc_caliber')->load($source->id());

    $this->assertSame((int) $target->id(), (int) $refreshed_firearm->get('caliber')->target_id);
    $this->assertSame((int) $target->id(), (int) $refreshed_recipe->get('caliber')->target_id);
    $this->assertSame((int) $target->id(), (int) $refreshed_source->get('duplicate_of')->target_id);
    $this->assertStringContainsString((string) $target->id(), (string) $refreshed_source->get('review_notes')->value);
  }

  /**
   * Tests that component merges repoint references and annotate the source.
   */
  public function testExecuteRepointsComponentReferences(): void {
    $source = $this->createComponent([
      'label' => '147gr FMJ Old',
      'machine_name' => '147gr_fmj_old_execute',
      'component_type' => 'bullet',
    ]);
    $target = $this->createComponent([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj_execute_target',
      'component_type' => 'bullet',
    ]);
    $recipe = $this->createRecipe([
      'label' => 'Component load',
      'machine_name' => 'component_load_execute',
      'caliber' => $this->createCaliber([
        'label' => '9mm Luger',
        'machine_name' => '9mm_luger_component_execute',
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
      ])->id(),
      'bullet_component' => $source->id(),
      'powder_component' => $this->createComponent([
        'label' => 'Example Powder',
        'machine_name' => 'example_powder_component_execute',
        'component_type' => 'powder',
      ])->id(),
      'primer_component' => $this->createComponent([
        'label' => 'CCI 300',
        'machine_name' => 'cci_300_component_execute',
        'component_type' => 'primer',
      ])->id(),
      'powder_charge_weight' => '4.500',
      'overall_length' => [
        'number' => '1.120',
        'unit' => LengthUnit::INCH,
      ],
    ]);

    $service = $this->container->get('gpc.reference_merge_execution');
    $result = $service->execute('component', (int) $source->id(), (int) $target->id(), $this->container->get('current_user'));

    $this->assertSame('success', $result['status']);
    $this->assertSame(1, $result['updated_entity_count']);
    $this->assertSame(1, $result['updated_field_count']);
    $this->assertSame([], $result['failures']);

    $refreshed_recipe = $this->container->get('entity_type.manager')->getStorage('gpc_recipe')->load($recipe->id());
    $refreshed_source = $this->container->get('entity_type.manager')->getStorage('gpc_component')->load($source->id());

    $this->assertSame((int) $target->id(), (int) $refreshed_recipe->get('bullet_component')->target_id);
    $this->assertSame((int) $target->id(), (int) $refreshed_source->get('duplicate_of')->target_id);
    $this->assertStringContainsString((string) $target->id(), (string) $refreshed_source->get('review_notes')->value);
  }

  /**
   * Tests that the confirmation form is available for the execution step.
   */
  public function testExecutionFormBuildsConfirmationAction(): void {
    $source = $this->createCaliber([
      'label' => '9mm Para',
      'machine_name' => '9mm_para_form',
      'nickname' => '9mm para',
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
    $target = $this->createCaliber([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_form',
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

    $form = new ReferenceMergeExecuteForm(
      $this->container->get('gpc.reference_merge_registry'),
      $this->container->get('gpc.reference_discovery'),
      $this->container->get('gpc.reference_merge_execution'),
      $this->container->get('entity_type.manager'),
      $this->container->get('tempstore.private'),
      $this->container->get('uuid'),
    );

    $build = $form->buildForm([], new FormState(), 'caliber', (string) $source->id(), (string) $target->id());
    $this->assertSame('Confirm and execute merge', (string) $build['actions']['submit']['#value']);
    $this->assertSame((int) $source->id(), $build['source_entity_id']['#value']);
    $this->assertSame((int) $target->id(), $build['target_entity_id']['#value']);
  }

  /**
   * Creates a caliber entity for test setup.
   */
  protected function createCaliber(array $values) {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_caliber');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a component entity for test setup.
   */
  protected function createComponent(array $values) {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_component');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a firearm entity for test setup.
   */
  protected function createFirearm(array $values) {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_firearm');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a recipe entity for test setup.
   */
  protected function createRecipe(array $values) {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_recipe');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

}
