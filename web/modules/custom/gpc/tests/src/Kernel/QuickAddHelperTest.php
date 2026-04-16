<?php

declare(strict_types=1);

namespace Drupal\Tests\gpc\Kernel;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\gpc\Utility\QuickAddHelper;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies the shared quick-add helper output.
 */
#[Group('gpc')]
#[RunTestsInSeparateProcesses]
class QuickAddHelperTest extends KernelTestBase {

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

    $this->installEntitySchema('gpc_component');
    $this->installEntitySchema('user');
    $this->installEntitySchema('gpc_caliber');
  }

  /**
   * Ensures the helper produces the expected quick-add response payload.
   */
  public function testBuildQuickAddAjaxResponse(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('gpc_caliber');
    $caliber = $storage->create([
      'label' => '9mm Luger',
      'machine_name' => '9mm_luger_quick_add',
    ]);
    $caliber->save();

    $response = QuickAddHelper::buildQuickAddAjaxResponse($caliber, '#edit-caliber');
    $commands = $response->getCommands();

    $this->assertCount(3, $commands);
    $this->assertSame('gpcQuickAddSetAutocompleteValue', $commands[0]['command']);
    $this->assertSame('#edit-caliber', $commands[0]['selector']);
    $this->assertSame('9mm Luger (' . $caliber->id() . ')', $commands[0]['value']);
    $this->assertSame('closeDialog', $commands[1]['command']);
    $this->assertSame('#drupal-modal', $commands[1]['selector']);
    $this->assertSame('message', $commands[2]['command']);
    $this->assertStringContainsString('9mm Luger', $commands[2]['message']);
    $this->assertStringContainsString('caliber', $commands[2]['message']);
    $this->assertStringContainsString('pending review', $commands[2]['message']);
  }

  /**
   * Ensures the helper generates the top-level target selector format.
   */
  public function testBuildTargetSelector(): void {
    $this->assertSame('#edit-caliber', QuickAddHelper::buildTargetSelector('caliber'));
    $this->assertSame('#edit-bullet-component', QuickAddHelper::buildTargetSelector('bullet_component'));
  }

  /**
   * Ensures quick-add links respect entity create access.
   */
  public function testBuildQuickAddLinkIfAllowedRespectsCreateAccess(): void {
    $anonymous = new AnonymousUserSession();
    $authenticated_user = User::create([
      'name' => 'quick_add_user',
      'status' => TRUE,
    ]);
    $authenticated_user->save();

    $this->assertFalse(QuickAddHelper::canCreateEntity('gpc_caliber', NULL, $anonymous));
    $this->assertTrue(QuickAddHelper::canCreateEntity('gpc_caliber', NULL, $authenticated_user));
    $this->assertTrue(QuickAddHelper::canCreateEntity('gpc_component', 'bullet', $authenticated_user));

    $link = QuickAddHelper::buildQuickAddLinkIfAllowed(
      'gpc_component',
      'bullet',
      'entity.gpc_component.add_form',
      ['component_type' => 'bullet'],
      'Add bullet',
      '#edit-bullet-component',
      $authenticated_user,
    );

    $this->assertStringContainsString('gpc_quick_add=1', $link);
    $this->assertStringContainsString('gpc_quick_add_target=%23edit-bullet-component', $link);
    $this->assertSame('', QuickAddHelper::buildQuickAddLinkIfAllowed(
      'gpc_component',
      'bullet',
      'entity.gpc_component.add_form',
      ['component_type' => 'bullet'],
      'Add bullet',
      '#edit-bullet-component',
      $anonymous,
    ));
  }

  /**
   * Ensures the quick-add form submit action is AJAX-enabled only in modal mode.
   */
  public function testQuickAddModeAddsAjaxSubmitHandler(): void {
    $request = Request::create('/admin/gpc/calibers/add', 'GET', [
      QuickAddHelper::QUICK_ADD_QUERY_KEY => '1',
      QuickAddHelper::TARGET_SELECTOR_KEY => '#edit-caliber',
    ]);
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get('request_stack')->push($request);

    $entity = $this->container->get('entity_type.manager')->getStorage('gpc_caliber')->create([
      'label' => '',
      'machine_name' => 'quick_add_form_test',
    ]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'add');

    $this->assertSame('::ajaxSubmit', $form['actions']['submit']['#ajax']['callback']);
  }

  /**
   * Ensures the quick-add AJAX submit callback returns the saved response.
   */
  public function testQuickAddAjaxSubmitReturnsExistingResponse(): void {
    $entity = $this->container->get('entity_type.manager')->getStorage('gpc_caliber')->create([
      'label' => 'Quick Add Caliber',
      'machine_name' => 'quick_add_ajax_submit',
    ]);
    $form_object = $this->container->get('entity_type.manager')->getFormObject('gpc_caliber', 'add');
    $form_object->setEntity($entity);

    $response = new AjaxResponse();
    $form_state = new FormState();
    $form_state->setResponse($response);

    $form = [
      '#attributes' => [
        'data-drupal-selector' => 'edit-gpc-caliber-add-form',
      ],
    ];

    $this->assertSame($response, $form_object->ajaxSubmit($form, $form_state));
  }

}
