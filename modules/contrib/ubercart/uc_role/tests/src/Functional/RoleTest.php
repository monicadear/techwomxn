<?php

namespace Drupal\Tests\uc_role\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the role purchase functionality.
 *
 * @group ubercart
 */
class RoleTest extends UbercartBrowserTestBase {
  use CronRunTrait;

  /**
   * {@inheritdoc}
   *
   * Enable editor because that module caused a crash on non-shippable products.
   *
   * @see https://www.drupal.org/node/2695639
   */
  protected static $modules = [
    'uc_payment',
    'uc_payment_pack',
    'uc_role',
    'editor',
  ];

  /**
   * Tests purchase of role.
   */
  public function testRolePurchaseCheckout() {
    // Add role assignment to a free, non-shippable product.
    $product = $this->createProduct(['price' => 0, 'shippable' => 0]);
    $rid = $this->drupalCreateRole(['access content']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $product->id() . '/edit/features', ['feature' => 'role'], 'Add');
    $edit = [
      'role' => $rid,
      'end_override' => TRUE,
      'expire_relative_duration' => 1,
      'expire_relative_granularity' => 'day',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save feature');

    // Check out with the test product.
    $this->addToCart($product);
    $order = $this->checkout();

    // Test that the role was granted.
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($order->getOwner()->hasRole($rid), 'Existing user was granted role.');

    // Test that the email is correct.
    $role = Role::load($rid);
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', $role->label(), 4, 'Role assignment email mentions role in subject line.');

    // Test that the role product / user relation is deleted with the user.
    user_delete($order->getOwnerId());

    // Run cron to ensure deleted users are handled correctly.
    $this->cronRun();
  }

  /**
   * {@inheritdoc}
   */
  protected function populateCheckoutForm(array $edit = []) {
    $edit = parent::populateCheckoutForm($edit);
    foreach (array_keys($edit) as $key) {
      if (substr($key, 0, 15) == 'panes[delivery]') {
        unset($edit[$key]);
      }
    }
    return $edit;
  }

}
