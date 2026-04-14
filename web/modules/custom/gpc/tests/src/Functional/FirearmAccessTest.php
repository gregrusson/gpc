<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers firearm ownership, access, and creation.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class FirearmAccessTest extends BrowserTestBase {

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
   * A shared caliber used by firearm tests.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $caliber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->caliber = $this->createCaliber([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger',
    ]);
  }

  /**
   * Tests that an authenticated user can create a firearm.
   */
  public function testAuthenticatedUserCanCreateFirearm(): void {
    $user = $this->drupalCreateUser([
      'view gpc firearms',
      'create gpc firearms',
      'edit gpc firearms',
      'delete gpc firearms',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/gpc/firearms/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Glock 19',
      'caliber' => $this->entityAutocompleteValue($this->caliber),
      'manufacturer' => 'Glock',
      'model' => '19',
      'serial_number' => 'ABC123',
      'notes' => 'Carry pistol.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Created the Glock 19 firearm.');
    $this->assertSession()->pageTextContains('Glock 19');

    $stored = $this->container->get('entity_type.manager')->getStorage('gpc_firearm')->loadMultiple();
    $this->assertCount(1, $stored);
    /** @var \Drupal\gpc\Entity\Firearm $firearm */
    $firearm = reset($stored);
    $this->assertSame((int) $user->id(), (int) $firearm->getOwnerId());
    $this->assertSame((int) $this->caliber->id(), (int) $firearm->get('caliber')->target_id);
  }

  /**
   * Tests that caliber is required on the firearm form.
   */
  public function testFirearmRequiresCaliber(): void {
    $user = $this->drupalCreateUser([
      'view gpc firearms',
      'create gpc firearms',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/gpc/firearms/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Test Firearm',
      'manufacturer' => 'Example',
      'model' => 'Model X',
    ], 'Save');

    $this->assertSession()->pageTextContains('Caliber field is required.');
  }

  /**
   * Tests that a user can view and edit their own firearm.
   */
  public function testOwnerCanViewAndEditOwnFirearm(): void {
    $owner = $this->drupalCreateUser([
      'view gpc firearms',
      'create gpc firearms',
      'edit gpc firearms',
      'delete gpc firearms',
    ]);

    $firearm = $this->createFirearm([
      'label' => 'Glock 19',
      'uid' => $owner->id(),
      'caliber' => $this->caliber->id(),
      'manufacturer' => 'Glock',
      'model' => '19',
    ]);

    $this->drupalLogin($owner);

    $this->drupalGet('/gpc/firearms/' . $firearm->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Glock 19');

    $this->drupalGet('/gpc/firearms/' . $firearm->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Glock 19 MOS',
      'caliber' => $this->entityAutocompleteValue($this->caliber),
      'manufacturer' => 'Glock',
      'model' => '19 MOS',
      'serial_number' => 'ABC123',
      'notes' => 'Updated firearm.',
    ], 'Save');

    $this->assertSession()->pageTextContains('Updated the Glock 19 MOS firearm.');

    $loaded = $this->container->get('entity_type.manager')->getStorage('gpc_firearm')->load($firearm->id());
    $this->assertNotNull($loaded);
    $this->assertSame('Glock 19 MOS', $loaded?->label());
    $this->assertSame('19 MOS', $loaded?->get('model')->value);
  }

  /**
   * Tests that one user cannot access another user's firearm.
   */
  public function testUserCannotAccessAnotherUsersFirearm(): void {
    $owner = $this->drupalCreateUser([
      'view gpc firearms',
      'create gpc firearms',
      'edit gpc firearms',
      'delete gpc firearms',
    ]);
    $other_user = $this->drupalCreateUser([
      'view gpc firearms',
      'create gpc firearms',
      'edit gpc firearms',
      'delete gpc firearms',
    ]);

    $firearm = $this->createFirearm([
      'label' => 'Smith & Wesson M&P 9',
      'uid' => $owner->id(),
      'caliber' => $this->caliber->id(),
      'manufacturer' => 'Smith & Wesson',
      'model' => 'M&P 9',
    ]);

    $this->drupalLogin($other_user);

    $this->drupalGet('/gpc/firearms/' . $firearm->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/gpc/firearms/' . $firearm->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/gpc/firearms');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Smith & Wesson M&P 9');
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
   * Creates a firearm entity for test setup.
   */
  protected function createFirearm(array $values): EntityInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_firearm');
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
