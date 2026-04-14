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
      'view gpc calibers',
      'create gpc calibers',
      'edit gpc calibers',
      'delete gpc calibers',
      'administer gpc components',
      'administer gpc recipes',
      'administer gpc batches',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the caliber add form for a non-admin user with shared-reference permissions.
   */
  public function testCaliberAddForm(): void {
    $this->drupalLogout();
    $caliberUser = $this->drupalCreateUser([
      'view gpc calibers',
      'create gpc calibers',
    ]);
    $this->drupalLogin($caliberUser);

    $this->drupalGet('/admin/content/gpc/calibers/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the 9mm Luger caliber.');
    $this->assertSession()->pageTextContains('9mm Luger');
  }

  /**
   * Tests the caliber edit form.
   */
  public function testCaliberEditForm(): void {
    $caliber = $this->createCaliber([
      'label' => '.308 Winchester',
      'machine_name' => '308_winchester',
      'nickname' => '.308 Win',
      'bullet_diameter' => '0.308',
      'case_length' => '2.015',
      'primer_type' => 'small_rifle',
      'neck_diameter' => '0.344',
      'shoulder_diameter' => '0.454',
      'base_diameter' => '0.470',
      'rim_diameter' => '0.473',
      'max_overall_length' => '2.800',
      'notes' => 'Common rifle caliber.',
    ]);

    $this->drupalGet('/admin/content/gpc/calibers/' . $caliber->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('Caliber name', '.308 Winchester');

    $this->submitForm([
      'label' => '.308 Win',
      'nickname' => '.308',
      'bullet_diameter' => '0.308',
      'case_length' => '2.015',
      'primer_type' => 'small_rifle',
      'neck_diameter' => '0.344',
      'shoulder_diameter' => '0.454',
      'base_diameter' => '0.470',
      'rim_diameter' => '0.473',
      'max_overall_length' => '2.800',
      'notes' => 'Updated note.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Updated the .308 Win caliber.');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_caliber')->load($caliber->id());
    $this->assertNotNull($loaded);
    $this->assertSame('.308 Win', $loaded?->label());
    $this->assertSame('.308', $loaded?->get('nickname')->value);
    $this->assertSame('0.308', $loaded?->get('bullet_diameter')->value);
    $this->assertSame('2.015', $loaded?->get('case_length')->value);
    $this->assertSame('small_rifle', $loaded?->get('primer_type')->value);
    $this->assertSame('0.344', $loaded?->get('neck_diameter')->value);
    $this->assertSame('0.454', $loaded?->get('shoulder_diameter')->value);
    $this->assertSame('0.470', $loaded?->get('base_diameter')->value);
    $this->assertSame('0.473', $loaded?->get('rim_diameter')->value);
    $this->assertSame('2.800', $loaded?->get('max_overall_length')->value);
  }

  /**
   * Tests caliber validation for structured fields.
   */
  public function testCaliberValidation(): void {
    $this->drupalLogout();
    $caliberUser = $this->drupalCreateUser([
      'view gpc calibers',
      'create gpc calibers',
    ]);
    $this->drupalLogin($caliberUser);

    $this->drupalGet('/admin/content/gpc/calibers/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Invalid Caliber',
      'machine_name' => 'invalid_caliber',
      'bullet_diameter' => '-0.001',
      'case_length' => '-1.000',
      'neck_diameter' => '-0.001',
      'shoulder_diameter' => '-0.001',
      'base_diameter' => '-0.001',
      'rim_diameter' => '-0.001',
      'max_overall_length' => '-0.001',
      'notes' => 'Should fail validation.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Bullet diameter must be higher than or equal to 0.');
    $this->assertSession()->pageTextContains('Case length must be higher than or equal to 0.');
    $this->assertSession()->pageTextContains('Neck diameter must be higher than or equal to 0.');
    $this->assertSession()->pageTextContains('Shoulder diameter must be higher than or equal to 0.');
    $this->assertSession()->pageTextContains('Base diameter must be higher than or equal to 0.');
    $this->assertSession()->pageTextContains('Rim diameter must be higher than or equal to 0.');
    $this->assertSession()->pageTextContains('Max overall length must be higher than or equal to 0.');
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
