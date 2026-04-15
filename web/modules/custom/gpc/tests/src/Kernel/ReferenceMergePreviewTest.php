<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\gpc\Controller\GpcReferenceMergeController;
use Drupal\gpc\Form\ReferenceMergeForm;
use Drupal\gpc\ReferenceMerge\ReferenceMergeRegistry;
use Drupal\physical\LengthUnit;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Covers the admin-only manual merge preview workflow.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class ReferenceMergePreviewTest extends KernelTestBase {

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
   * Tests the landing page, form build, and preview output for both merge types.
   */
  public function testPreviewPagesRenderForCaliberAndComponentMerges(): void {
    $controller = $this->createController();
    $renderer = $this->container->get('renderer');

    $caliber_canonical = $this->createCaliber([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_canonical',
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
    $caliber_duplicate = $this->createCaliber([
      'label' => '9mm Para',
      'machine_name' => '9mm_para_duplicate',
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
    $component_canonical = $this->createComponent([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj_canonical',
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
    $component_duplicate = $this->createComponent([
      'label' => '147gr FMJ Old',
      'machine_name' => '147gr_fmj_duplicate',
      'component_type' => 'bullet',
      'upc' => '000123456780',
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

    $this->createFirearm([
      'label' => 'Reference pistol',
      'type' => 'pistol',
      'caliber' => $caliber_duplicate->id(),
    ]);
    $this->createRecipe([
      'label' => 'Reference load',
      'machine_name' => 'reference_load_preview',
      'caliber' => $caliber_duplicate->id(),
      'bullet_component' => $component_duplicate->id(),
      'powder_component' => $component_canonical->id(),
      'primer_component' => $component_canonical->id(),
      'powder_charge_weight' => '4.500',
      'overall_length' => [
        'number' => '1.120',
        'unit' => LengthUnit::INCH,
      ],
    ]);

    $landing_markup = (string) $renderer->renderInIsolation($controller->landing());
    $this->assertStringContainsString('Manual admin maintenance only.', $landing_markup);
    $this->assertStringContainsString('Caliber', $landing_markup);
    $this->assertStringContainsString('Component', $landing_markup);

    $form = new ReferenceMergeForm($this->container->get('gpc.reference_merge_registry'), $this->container->get('entity_type.manager'));
    $form_build = $form->buildForm([], new FormState(), 'caliber');
    $this->assertSame('gpc_caliber', $form_build['source_entity_id']['#target_type']);
    $this->assertSame('gpc_caliber', $form_build['target_entity_id']['#target_type']);
    $this->assertSame('Preview merge', (string) $form_build['actions']['submit']['#value']);

    $caliber_markup = (string) $renderer->renderInIsolation($controller->preview('caliber', (string) $caliber_duplicate->id(), (string) $caliber_canonical->id()));
    $this->assertStringContainsString('Manual admin maintenance action.', $caliber_markup);
    $this->assertStringContainsString('9mm Para', $caliber_markup);
    $this->assertStringContainsString('9mm Luger', $caliber_markup);
    $this->assertStringContainsString('Reference pistol', $caliber_markup);
    $this->assertStringContainsString('Reference load', $caliber_markup);
    $this->assertStringContainsString('gpc_firearm.caliber', $caliber_markup);
    $this->assertStringContainsString('gpc_recipe.caliber', $caliber_markup);

    $component_markup = (string) $renderer->renderInIsolation($controller->preview('component', (string) $component_duplicate->id(), (string) $component_canonical->id()));
    $this->assertStringContainsString('147gr FMJ Old', $component_markup);
    $this->assertStringContainsString('147gr FMJ', $component_markup);
    $this->assertStringContainsString('gpc_recipe.bullet_component', $component_markup);
  }

  /**
   * Tests that the preview rejects identical source and target selections.
   */
  public function testPreviewRejectsIdenticalSourceAndTarget(): void {
    $controller = $this->createController();

    $caliber = $this->createCaliber([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_identity_check',
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

    $this->expectException(NotFoundHttpException::class);
    $controller->preview('caliber', (string) $caliber->id(), (string) $caliber->id());
  }

  /**
   * Creates the merge controller used by the test.
   */
  protected function createController(): GpcReferenceMergeController {
    return new GpcReferenceMergeController(
      $this->container->get('gpc.reference_discovery'),
      $this->container->get('gpc.reference_merge_registry'),
      $this->container->get('entity_type.manager'),
    );
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
