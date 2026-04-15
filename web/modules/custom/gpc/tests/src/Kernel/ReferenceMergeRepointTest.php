<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\physical\LengthUnit;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers reference repoint behavior for merge execution internals.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
final class ReferenceMergeRepointTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;

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
    'entity_test',
    'field_test',
    'physical',
    'gpc',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['field', 'system', 'user']);
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('gpc_caliber');
    $this->installEntitySchema('gpc_component');
    $this->installEntitySchema('gpc_recipe');
    $this->installEntitySchema('gpc_firearm');

    $this->createEntityReferenceField(
      'entity_test_mul',
      'entity_test_mul',
      'field_merge_calibers',
      'Merge calibers',
      'gpc_caliber',
      'default',
      [],
      3
    );
  }

  /**
   * Tests that a multivalue field repoint removes duplicates and keeps one target.
   */
  public function testRepointReferenceFieldDeduplicatesTargetValues(): void {
    $source = $this->createCaliber([
      'label' => '9mm Para',
      'machine_name' => '9mm_para_repoint',
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
      'machine_name' => '9mm_luger_repoint',
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

    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test_mul');
    $entity = $storage->create([
      'name' => 'Merge host',
      'field_merge_calibers' => [
        ['target_id' => $source->id()],
        ['target_id' => $target->id()],
        ['target_id' => $target->id()],
      ],
    ]);
    $entity->save();

    $service = $this->container->get('gpc.reference_merge_execution');
    $method = new \ReflectionMethod($service, 'repointReferenceField');
    $method->setAccessible(TRUE);
    $result = $method->invoke($service, $entity, 'field_merge_calibers', (int) $source->id(), (int) $target->id());
    $entity->save();

    $this->assertSame('updated', $result['status']);
    $this->assertSame(2, $result['reference_count']);

    $loaded = $storage->load($entity->id());
    $this->assertNotNull($loaded);
    $values = array_map(static fn (array $item): int => (int) $item['target_id'], $loaded->get('field_merge_calibers')->getValue());
    $this->assertSame([(int) $target->id()], $values);
  }

  /**
   * Creates a caliber entity for test setup.
   */
  protected function createCaliber(array $values): EntityInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_caliber');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

}
