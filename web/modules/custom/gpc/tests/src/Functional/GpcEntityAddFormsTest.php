<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\physical\LengthUnit;
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
    'physical',
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
      'view gpc recipes',
      'create gpc recipes',
      'edit gpc recipes',
      'delete gpc recipes',
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
    $this->assertSession()->optionExists('bullet_diameter[unit]', LengthUnit::MILLIMETER);
    $this->assertSession()->fieldValueEquals('bullet_diameter[unit]', LengthUnit::INCH);

    $this->submitForm([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger',
      'bullet_diameter[number]' => '25.4',
      'bullet_diameter[unit]' => LengthUnit::MILLIMETER,
      'case_length[number]' => '25.4',
      'case_length[unit]' => LengthUnit::MILLIMETER,
      'neck_diameter[number]' => '0.380',
      'neck_diameter[unit]' => LengthUnit::INCH,
      'shoulder_diameter[number]' => '0.391',
      'shoulder_diameter[unit]' => LengthUnit::INCH,
      'base_diameter[number]' => '0.391',
      'base_diameter[unit]' => LengthUnit::INCH,
      'rim_diameter[number]' => '0.392',
      'rim_diameter[unit]' => LengthUnit::INCH,
      'max_overall_length[number]' => '1.169',
      'max_overall_length[unit]' => LengthUnit::INCH,
      'primer_type' => 'small_pistol',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the 9mm Luger caliber.');
    $this->assertSession()->pageTextContains('9mm Luger');
    $this->assertSession()->pageTextContains('1 in');
  }

  /**
   * Tests the caliber edit form.
   */
  public function testCaliberEditForm(): void {
    $caliber = $this->createCaliber([
      'label' => '.308 Winchester',
      'machine_name' => '308_winchester',
      'nickname' => '.308 Win',
      'bullet_diameter' => [
        'number' => '7.82',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'case_length' => [
        'number' => '51.18',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'primer_type' => 'small_rifle',
      'neck_diameter' => [
        'number' => '8.74',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'shoulder_diameter' => [
        'number' => '11.53',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'base_diameter' => [
        'number' => '11.94',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'rim_diameter' => [
        'number' => '12.02',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'max_overall_length' => [
        'number' => '71.12',
        'unit' => LengthUnit::MILLIMETER,
      ],
      'notes' => 'Common rifle caliber.',
    ]);

    $this->drupalGet('/admin/content/gpc/calibers/' . $caliber->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('Caliber name', '.308 Winchester');
    $this->assertSession()->fieldValueEquals('bullet_diameter[unit]', LengthUnit::MILLIMETER);

    $this->submitForm([
      'label' => '.308 Win',
      'nickname' => '.308',
      'bullet_diameter[number]' => '7.82',
      'bullet_diameter[unit]' => LengthUnit::MILLIMETER,
      'case_length[number]' => '51.18',
      'case_length[unit]' => LengthUnit::MILLIMETER,
      'primer_type' => 'small_rifle',
      'neck_diameter[number]' => '8.74',
      'neck_diameter[unit]' => LengthUnit::MILLIMETER,
      'shoulder_diameter[number]' => '11.53',
      'shoulder_diameter[unit]' => LengthUnit::MILLIMETER,
      'base_diameter[number]' => '11.94',
      'base_diameter[unit]' => LengthUnit::MILLIMETER,
      'rim_diameter[number]' => '12.02',
      'rim_diameter[unit]' => LengthUnit::MILLIMETER,
      'max_overall_length[number]' => '71.12',
      'max_overall_length[unit]' => LengthUnit::MILLIMETER,
      'notes' => 'Updated note.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Updated the .308 Win caliber.');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_caliber')->load($caliber->id());
    $this->assertNotNull($loaded);
    $this->assertSame('.308 Win', $loaded?->label());
    $this->assertSame('.308', $loaded?->get('nickname')->value);
    $this->assertSame('7.820000', $loaded?->get('bullet_diameter')->number);
    $this->assertSame('mm', $loaded?->get('bullet_diameter')->unit);
    $this->assertSame('51.180000', $loaded?->get('case_length')->number);
    $this->assertSame('mm', $loaded?->get('case_length')->unit);
    $this->assertSame('small_rifle', $loaded?->get('primer_type')->value);
    $this->assertSame('8.740000', $loaded?->get('neck_diameter')->number);
    $this->assertSame('mm', $loaded?->get('neck_diameter')->unit);
    $this->assertSame('11.530000', $loaded?->get('shoulder_diameter')->number);
    $this->assertSame('mm', $loaded?->get('shoulder_diameter')->unit);
    $this->assertSame('11.940000', $loaded?->get('base_diameter')->number);
    $this->assertSame('mm', $loaded?->get('base_diameter')->unit);
    $this->assertSame('12.020000', $loaded?->get('rim_diameter')->number);
    $this->assertSame('mm', $loaded?->get('rim_diameter')->unit);
    $this->assertSame('71.120000', $loaded?->get('max_overall_length')->number);
    $this->assertSame('mm', $loaded?->get('max_overall_length')->unit);
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
      'bullet_diameter[number]' => '-0.001',
      'case_length[number]' => '-1.000',
      'neck_diameter[number]' => '-0.001',
      'shoulder_diameter[number]' => '-0.001',
      'base_diameter[number]' => '-0.001',
      'rim_diameter[number]' => '-0.001',
      'max_overall_length[number]' => '-0.001',
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
   * Tests the component add page exposes the supported bundles.
   */
  public function testComponentAddPage(): void {
    $this->drupalGet('/admin/content/gpc/components/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Bullet');
    $this->assertSession()->linkExists('Powder');
    $this->assertSession()->linkExists('Primer');
    $this->assertSession()->linkExists('Brass');
  }

  /**
   * Tests the bullet component add form.
   */
  public function testBulletComponentAddForm(): void {
    $this->drupalGet('/admin/content/gpc/components/add/bullet');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Component name');
    $this->assertSession()->fieldExists('Bullet weight');
    $this->assertSession()->fieldExists('UPC');
    $this->assertSession()->optionExists('diameter[unit]', LengthUnit::MILLIMETER);
    $this->assertSession()->fieldValueEquals('diameter[unit]', LengthUnit::INCH);
    $this->assertSession()->optionExists('ballistic_coefficient_model', 'G1');
    $this->assertSession()->optionExists('ballistic_coefficient_model', 'G7');
    $this->assertSession()->fieldExists('length[number]');
    $this->assertSession()->fieldValueEquals('length[unit]', LengthUnit::INCH);
    $this->assertSession()->fieldExists('Ballistic coefficient value');
    $this->assertSession()->fieldExists('Ballistic coefficient model');
    $this->assertSession()->fieldExists('Sectional density');

    $this->submitForm([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj',
      'manufacturer' => 'Example Co.',
      'upc' => '000123456789',
      'weight' => '147.500',
      'diameter[number]' => '0.3550',
      'diameter[unit]' => LengthUnit::INCH,
      'length[number]' => '0.5750',
      'length[unit]' => LengthUnit::INCH,
      'sectional_density' => '0.1670',
      'ballistic_coefficient_value' => '0.4350',
      'ballistic_coefficient_model' => 'G7',
      'notes' => 'Practice bullet.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the 147gr FMJ component.');
    $this->assertSession()->pageTextContains('147gr FMJ');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_component')->loadByProperties([
      'machine_name' => '147gr_fmj',
    ]);
    $loaded = reset($loaded);
    $this->assertNotFalse($loaded);
    $this->assertSame('000123456789', $loaded->get('upc')->value);
    $this->assertSame('0.355000', $loaded->get('diameter')->number);
    $this->assertSame('in', $loaded->get('diameter')->unit);
    $this->assertSame('0.575000', $loaded->get('length')->number);
    $this->assertSame('in', $loaded->get('length')->unit);
    $this->assertSame('0.4350', $loaded->get('ballistic_coefficient_value')->value);
    $this->assertSame('G7', $loaded->get('ballistic_coefficient_model')->value);
  }

  /**
   * Tests the brass component add form.
   */
  public function testBrassComponentAddForm(): void {
    $this->drupalGet('/admin/content/gpc/components/add/brass');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('UPC');
    $this->assertSession()->optionExists('case_length[unit]', LengthUnit::MILLIMETER);
    $this->assertSession()->fieldValueEquals('case_length[unit]', LengthUnit::INCH);

    $this->submitForm([
      'label' => 'Starline 10mm',
      'machine_name' => 'starline_10mm',
      'manufacturer' => 'Starline',
      'upc' => '00622404310125',
      'case_length[number]' => '1.250',
      'case_length[unit]' => LengthUnit::INCH,
      'notes' => 'Once-fired brass.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the Starline 10mm component.');
    $this->assertSession()->pageTextContains('Starline 10mm');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_component')->loadByProperties([
      'machine_name' => 'starline_10mm',
    ]);
    $loaded = reset($loaded);
    $this->assertNotFalse($loaded);
    $this->assertSame('00622404310125', $loaded->get('upc')->value);
    $this->assertSame('1.250000', $loaded->get('case_length')->number);
    $this->assertSame('in', $loaded->get('case_length')->unit);
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
    $brass = $this->createComponent([
      'label' => 'Starline 10mm',
      'machine_name' => 'starline_10mm',
      'component_type' => 'brass',
    ]);

    $this->drupalGet('/gpc/recipes/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Recipe code');
    $this->assertSession()->fieldExists('Nickname');
    $this->assertSession()->fieldExists('overall_length[number]');
    $this->assertSession()->fieldValueEquals('overall_length[unit]', LengthUnit::INCH);
    $this->assertSession()->fieldExists('Crimped');

    $this->submitForm([
      'label' => 'Practice Load',
      'nickname' => 'Range load',
      'machine_name' => 'practice_load',
      'caliber' => $this->entityAutocompleteValue($caliber),
      'bullet_component' => $this->entityAutocompleteValue($bullet),
      'powder_component' => $this->entityAutocompleteValue($powder),
      'primer_component' => $this->entityAutocompleteValue($primer),
      'brass_component' => $this->entityAutocompleteValue($brass),
      'powder_charge_weight' => '8.200',
      'overall_length[number]' => '1.255',
      'overall_length[unit]' => LengthUnit::INCH,
      'crimp' => 1,
      'notes' => 'Range use.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the Practice Load recipe.');
    $this->assertSession()->pageTextContains('Practice Load');
    $this->assertSession()->pageTextContains('Range load');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_recipe')->loadByProperties([
      'machine_name' => 'practice_load',
    ]);
    $loaded = reset($loaded);
    $this->assertNotFalse($loaded);
    $this->assertSame('1.255000', $loaded->get('overall_length')->number);
    $this->assertSame('in', $loaded->get('overall_length')->unit);
    $this->assertSame(1, (int) $loaded->get('crimp')->value);
  }

  /**
   * Tests the recipe field storage matches the current model rules.
   */
  public function testRecipeFieldStorageMatchesGuidelines(): void {
    $definitions = $this->container->get('entity_field.manager')->getFieldStorageDefinitions('gpc_recipe');

    $this->assertSame('physical_measurement', $definitions['overall_length']->getType());
    $this->assertSame('length', $definitions['overall_length']->getSetting('measurement_type'));
    $this->assertSame('boolean', $definitions['crimp']->getType());
    $this->assertSame('Yes', (string) $definitions['crimp']->getSetting('on_label'));
    $this->assertSame('No', (string) $definitions['crimp']->getSetting('off_label'));
  }

  /**
   * Tests that a user can view and edit their own recipe.
   */
  public function testOwnerCanViewAndEditOwnRecipe(): void {
    $this->drupalLogout();
    $owner = $this->drupalCreateUser([
      'view gpc recipes',
      'create gpc recipes',
      'edit gpc recipes',
      'delete gpc recipes',
    ]);

    $recipe = $this->createRecipe([
      'label' => 'Practice Load',
      'nickname' => 'Range load',
      'machine_name' => 'practice_load_owner',
      'uid' => $owner->id(),
      'caliber' => $this->createCaliber([
        'label' => '10mm Auto',
        'machine_name' => '10mm_auto_owner',
      ])->id(),
      'bullet_component' => $this->createComponent([
        'label' => '180gr JHP',
        'machine_name' => '180gr_jhp_owner',
        'component_type' => 'bullet',
      ])->id(),
      'powder_component' => $this->createComponent([
        'label' => 'Longshot',
        'machine_name' => 'longshot_owner',
        'component_type' => 'powder',
      ])->id(),
      'primer_component' => $this->createComponent([
        'label' => 'CCI 300',
        'machine_name' => 'cci_300_owner',
        'component_type' => 'primer',
      ])->id(),
      'powder_charge_weight' => '8.200',
      'overall_length' => '1.255',
    ]);

    $this->drupalLogin($owner);

    $this->drupalGet('/gpc/recipes/' . $recipe->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Practice Load');

    $this->drupalGet('/gpc/recipes/' . $recipe->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('overall_length[unit]', LengthUnit::INCH);

    $this->submitForm([
      'label' => 'Practice Load v2',
      'nickname' => 'Updated range load',
      'caliber' => $this->entityAutocompleteValue($this->container->get('entity_type.manager')->getStorage('gpc_caliber')->load($recipe->get('caliber')->target_id)),
      'bullet_component' => $this->entityAutocompleteValue($this->container->get('entity_type.manager')->getStorage('gpc_component')->load($recipe->get('bullet_component')->target_id)),
      'powder_component' => $this->entityAutocompleteValue($this->container->get('entity_type.manager')->getStorage('gpc_component')->load($recipe->get('powder_component')->target_id)),
      'primer_component' => $this->entityAutocompleteValue($this->container->get('entity_type.manager')->getStorage('gpc_component')->load($recipe->get('primer_component')->target_id)),
      'powder_charge_weight' => '8.100',
      'overall_length[number]' => '1.245',
      'overall_length[unit]' => LengthUnit::INCH,
      'crimp' => 1,
      'notes' => 'Updated recipe.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Updated the Practice Load v2 recipe.');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_recipe')->load($recipe->id());
    $this->assertNotNull($loaded);
    $this->assertSame('1.245000', $loaded->get('overall_length')->number);
    $this->assertSame('in', $loaded->get('overall_length')->unit);
    $this->assertSame(1, (int) $loaded->get('crimp')->value);
  }

  /**
   * Tests that one user cannot access another user's recipe.
   */
  public function testUserCannotAccessAnotherUsersRecipe(): void {
    $owner = $this->drupalCreateUser([
      'view gpc recipes',
      'create gpc recipes',
      'edit gpc recipes',
      'delete gpc recipes',
    ]);
    $other_user = $this->drupalCreateUser([
      'view gpc recipes',
      'create gpc recipes',
      'edit gpc recipes',
      'delete gpc recipes',
    ]);

    $recipe = $this->createRecipe([
      'label' => 'Owner Load',
      'nickname' => 'Private',
      'machine_name' => 'owner_load_private',
      'uid' => $owner->id(),
      'caliber' => $this->createCaliber([
        'label' => '9mm Luger',
        'machine_name' => '9mm_luger_private',
      ])->id(),
      'bullet_component' => $this->createComponent([
        'label' => '124gr FMJ',
        'machine_name' => '124gr_fmj_private',
        'component_type' => 'bullet',
      ])->id(),
      'powder_component' => $this->createComponent([
        'label' => 'Titegroup',
        'machine_name' => 'titegroup_private',
        'component_type' => 'powder',
      ])->id(),
      'primer_component' => $this->createComponent([
        'label' => 'CCI 500',
        'machine_name' => 'cci_500_private',
        'component_type' => 'primer',
      ])->id(),
      'powder_charge_weight' => '4.700',
      'overall_length' => '1.120',
    ]);

    $this->drupalLogin($other_user);

    $this->drupalGet('/gpc/recipes/' . $recipe->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/gpc/recipes/' . $recipe->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/gpc/recipes');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Owner Load');
  }

  /**
   * Tests the recipe add form requires a recipe code.
   */
  public function testRecipeRequiresCode(): void {
    $this->drupalLogout();
    $user = $this->drupalCreateUser([
      'view gpc recipes',
      'create gpc recipes',
    ]);
    $this->drupalLogin($user);

    $caliber = $this->createCaliber([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_recipe_required',
    ]);
    $bullet = $this->createComponent([
      'label' => '147gr FMJ',
      'machine_name' => '147gr_fmj_recipe_required',
      'component_type' => 'bullet',
    ]);
    $powder = $this->createComponent([
      'label' => 'Longshot',
      'machine_name' => 'longshot_recipe_required',
      'component_type' => 'powder',
    ]);
    $primer = $this->createComponent([
      'label' => 'CCI 300',
      'machine_name' => 'cci_300_recipe_required',
      'component_type' => 'primer',
    ]);

    $this->drupalGet('/gpc/recipes/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => '',
      'machine_name' => 'invalid_recipe',
      'caliber' => $this->entityAutocompleteValue($caliber),
      'bullet_component' => $this->entityAutocompleteValue($bullet),
      'powder_component' => $this->entityAutocompleteValue($powder),
      'primer_component' => $this->entityAutocompleteValue($primer),
      'powder_charge_weight' => '8.200',
      'overall_length[number]' => '1.255',
      'overall_length[unit]' => LengthUnit::INCH,
      'notes' => 'Should fail.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Recipe code field is required.');
  }

  /**
   * Tests the batch add form.
   */
  public function testBatchAddForm(): void {
    $this->drupalLogout();
    $user = $this->drupalCreateUser([
      'view gpc batches',
      'create gpc batches',
      'edit gpc batches',
      'delete gpc batches',
      'view gpc recipes',
    ]);
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
      'nickname' => 'Baseline',
      'machine_name' => 'training_load',
      'uid' => $user->id(),
      'caliber' => $caliber->id(),
      'bullet_component' => $bullet->id(),
      'powder_component' => $powder->id(),
      'primer_component' => $primer->id(),
      'powder_charge_weight' => '24.000',
      'overall_length' => [
        'number' => '2.230',
        'unit' => LengthUnit::INCH,
      ],
    ]);

    $this->drupalLogin($user);

    $this->drupalGet('/gpc/batches/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Batch code');
    $this->assertSession()->fieldExists('Recipe');

    $this->submitForm([
      'label' => 'Batch Alpha',
      'machine_name' => 'batch_alpha',
      'recipe' => $this->entityAutocompleteValue($recipe),
      'batch_date' => '2026-04-13',
      'quantity_produced' => '150',
      'notes' => 'Initial run.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the Batch Alpha batch.');
    $this->assertSession()->pageTextContains('Training Load / Batch Alpha');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_batch')->loadByProperties([
      'machine_name' => 'batch_alpha',
    ]);
    $loaded = reset($loaded);
    $this->assertNotFalse($loaded);
    $this->assertSame((int) $user->id(), (int) $loaded->getOwnerId());
    $this->assertSame('Batch Alpha', $loaded->label());
    $this->assertSame((int) $recipe->id(), (int) $loaded->get('recipe')->target_id);
    $this->assertSame(150, (int) $loaded->get('quantity_produced')->value);
  }

  /**
   * Tests that a user can view and edit their own batch and others cannot.
   */
  public function testOwnerCanViewAndEditOwnBatch(): void {
    $this->drupalLogout();
    $owner = $this->drupalCreateUser([
      'view gpc batches',
      'create gpc batches',
      'edit gpc batches',
      'delete gpc batches',
      'view gpc recipes',
      'create gpc recipes',
    ]);
    $other_user = $this->drupalCreateUser([
      'view gpc batches',
      'create gpc batches',
      'edit gpc batches',
      'delete gpc batches',
      'view gpc recipes',
      'create gpc recipes',
    ]);

    $caliber = $this->createCaliber([
      'label' => '.223 Rem',
      'machine_name' => '223_rem_owner',
    ]);
    $bullet = $this->createComponent([
      'label' => '55gr FMJ',
      'machine_name' => '55gr_fmj_owner',
      'component_type' => 'bullet',
    ]);
    $powder = $this->createComponent([
      'label' => 'H335',
      'machine_name' => 'h335_owner',
      'component_type' => 'powder',
    ]);
    $primer = $this->createComponent([
      'label' => 'CCI 400',
      'machine_name' => 'cci_400_owner',
      'component_type' => 'primer',
    ]);
    $recipe = $this->createRecipe([
      'label' => 'Training Load',
      'machine_name' => 'training_load_owner',
      'uid' => $owner->id(),
      'caliber' => $caliber->id(),
      'bullet_component' => $bullet->id(),
      'powder_component' => $powder->id(),
      'primer_component' => $primer->id(),
      'powder_charge_weight' => '24.000',
      'overall_length' => [
        'number' => '2.230',
        'unit' => LengthUnit::INCH,
      ],
    ]);
    $batch = $this->createBatch([
      'label' => 'Batch Alpha',
      'machine_name' => 'batch_alpha_owner',
      'uid' => $owner->id(),
      'recipe' => $recipe->id(),
      'batch_date' => strtotime('2026-04-13 00:00:00'),
      'quantity_produced' => 150,
      'notes' => 'Initial run.',
    ]);

    $this->drupalLogin($owner);
    $this->drupalGet('/gpc/batches/' . $batch->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Batch Alpha');

    $this->drupalGet('/gpc/batches');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Training Load / Batch Alpha');

    $this->drupalGet('/gpc/batches/' . $batch->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Batch Alpha v2',
      'recipe' => $this->entityAutocompleteValue($recipe),
      'batch_date' => '2026-04-14',
      'quantity_produced' => '175',
      'notes' => 'Updated run.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Updated the Batch Alpha v2 batch.');

    $this->drupalLogin($other_user);
    $this->drupalGet('/gpc/batches/' . $batch->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/gpc/batches/' . $batch->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/gpc/batches');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Batch Alpha v2');
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
    if (array_key_exists('overall_length', $values) && !is_array($values['overall_length'])) {
      $values['overall_length'] = [
        'number' => (string) $values['overall_length'],
        'unit' => LengthUnit::INCH,
      ];
    }

    if (array_key_exists('crimp', $values)) {
      $values['crimp'] = !empty($values['crimp']);
    }

    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_recipe');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a batch entity for test setup.
   */
  protected function createBatch(array $values): EntityInterface {
    if (array_key_exists('recipe', $values) && !is_array($values['recipe'])) {
      $values['recipe'] = $values['recipe'];
    }

    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_batch');
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
