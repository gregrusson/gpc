<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers the core GPC entity add workflows.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class GpcEntityAddFormsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gpc',
    'field',
    'filter',
    'options',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user who can administer all tested GPC entities.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer gpc calibers',
      'administer gpc components',
      'administer gpc recipes',
      'administer gpc batches',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the caliber add form.
   */
  public function testCaliberAddForm(): void {
    $this->drupalGet('/admin/content/gpc/calibers/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger',
      'notes' => 'Common pistol caliber.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the 9mm Luger caliber.');
    $this->assertSession()->pageTextContains('9mm Luger');
  }

  /**
   * Tests the component add form.
   */
  public function testComponentAddForm(): void {
    $this->drupalGet('/admin/content/gpc/components/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj',
      'component_type' => 'bullet',
      'manufacturer' => 'Example Co.',
      'notes' => 'Practice bullet.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the 147gr FMJ component.');
    $this->assertSession()->pageTextContains('147gr FMJ');
  }

  /**
   * Tests the recipe add form.
   */
  public function testRecipeAddForm(): void {
    $caliber = $this->createCaliber([
      'label' => '10mm Auto',
      'machine_name' => '10mm_auto',
    ]);
    $bullet = $this->createComponent([
      'label' => '180gr JHP',
      'machine_name' => '180gr_jhp',
      'component_type' => 'bullet',
    ]);
    $powder = $this->createComponent([
      'label' => 'Longshot',
      'machine_name' => 'longshot',
      'component_type' => 'powder',
    ]);
    $primer = $this->createComponent([
      'label' => 'CCI 300',
      'machine_name' => 'cci_300',
      'component_type' => 'primer',
    ]);

    $this->drupalGet('/admin/content/gpc/recipes/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Practice Load',
      'machine_name' => 'practice_load',
      'caliber' => $this->entityAutocompleteValue($caliber),
      'bullet_component' => $this->entityAutocompleteValue($bullet),
      'powder_component' => $this->entityAutocompleteValue($powder),
      'primer_component' => $this->entityAutocompleteValue($primer),
      'powder_charge_weight' => '8.200',
      'overall_length' => '1.255',
      'notes' => 'Range use.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the Practice Load recipe.');
    $this->assertSession()->pageTextContains('Practice Load');
  }

  /**
   * Tests the batch add form.
   */
  public function testBatchAddForm(): void {
    $caliber = $this->createCaliber([
      'label' => '.223 Rem',
      'machine_name' => '223_rem',
    ]);
    $bullet = $this->createComponent([
      'label' => '55gr FMJ',
      'machine_name' => '55gr_fmj',
      'component_type' => 'bullet',
    ]);
    $powder = $this->createComponent([
      'label' => 'H335',
      'machine_name' => 'h335',
      'component_type' => 'powder',
    ]);
    $primer = $this->createComponent([
      'label' => 'CCI 400',
      'machine_name' => 'cci_400',
      'component_type' => 'primer',
    ]);
    $recipe = $this->createRecipe([
      'label' => 'Training Load',
      'machine_name' => 'training_load',
      'caliber' => $caliber->id(),
      'bullet_component' => $bullet->id(),
      'powder_component' => $powder->id(),
      'primer_component' => $primer->id(),
      'powder_charge_weight' => '24.000',
      'overall_length' => '2.230',
    ]);

    $this->drupalGet('/admin/content/gpc/batches/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Batch Alpha',
      'machine_name' => 'batch_alpha',
      'recipe' => $this->entityAutocompleteValue($recipe),
      'batch_date' => '2026-04-13',
      'quantity_produced' => '150',
      'notes' => 'Initial run.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the Batch Alpha batch.');
    $this->assertSession()->pageTextContains('Batch Alpha');
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

  /**
   * Creates a component entity for test setup.
   */
  protected function createComponent(array $values): EntityInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_component');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a recipe entity for test setup.
   */
  protected function createRecipe(array $values): EntityInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_recipe');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Formats an entity reference value for entity autocomplete widgets.
   */
  protected function entityAutocompleteValue(EntityInterface $entity): string {
    return sprintf('%s (%s)', $entity->label(), $entity->id());
  }

}
