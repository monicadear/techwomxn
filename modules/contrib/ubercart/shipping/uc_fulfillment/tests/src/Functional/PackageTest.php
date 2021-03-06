<?php

namespace Drupal\Tests\uc_fulfillment\Functional;

use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\Entity\OrderProduct;

/**
 * Tests creating new packages from purchased products.
 *
 * @group ubercart
 */
class PackageTest extends FulfillmentTestBase {

  /**
   * Tests the User Interface for packaging products.
   */
  public function testPackagesUi() {
    // Log on as administrator to fulfill order.
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // A payment method for the order.
    $method = $this->createPaymentMethod('other');

    // Process an anonymous, shippable order.
    $order = Order::create([
      'uid' => 0,
      'primary_email' => $this->randomMachineName() . '@example.org',
      'payment_method' => $method['id'],
    ]);

    // Add three more products to use for our tests.
    $products = [];
    for ($i = 1; $i <= 4; $i++) {
      $product = $this->createProduct(['uid' => $this->adminUser->id(), 'promote' => 0]);
      $order->products[$i] = OrderProduct::create([
        'nid' => $product->nid->target_id,
        'title' => $product->title->value,
        'model' => $product->model,
        'qty' => 1,
        'cost' => $product->cost->value,
        'price' => $product->price->value,
        'weight' => $product->weight,
        'data' => [],
      ]);
      $order->products[$i]->data->shippable = 1;
    }
    $order->save();
    $order = Order::load($order->id());

    uc_payment_enter($order->id(), 'other', $order->getTotal());

// Order with 4 products shippable products. (where do we test not-shippable?)
// Check all, make one package, verify we're on packages page with only one packge.
// Try create package link, should see there are no products message.
// Delete package.

// Check all, make shipment, verify we're on packages page with N packages.
// Delete packages.

// How does Sep work? how does making 2 packages out of 4 products work?

// Check all, cancel, verify we're on order page.

// After packages made and check for # (check make one and make shipment, use sep. as well)
// Can use edit/delete actions to package then start over with the same order.
// and check for full table at /packages and check for action on /packages page,
// goto shipments tab and look for No shipments have been made for this order.
// As well as a list of all the packages.

    //
    // Test presence and operation of package operation on order admin View.
    //
    $this->drupalGet('admin/store/orders/view');
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/packages');
    // Test action.
    $this->clickLink('Package');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains(
      "This order's products have not been organized into packages.",
      'Package action found.'
    );

    // Now package the products in this order.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    // First time through we'll be verbose - skip this on subsequent tests.
    foreach ($order->products as $sequence => $item) {
      $assert->pageTextContains(
        $item->title->value,
        'Product title found.'
      );
      $assert->pageTextContains(
        $item->model->value,
        'Product SKU found.'
      );
      // Check that product is available for packaging.
      $assert->fieldValueEquals('shipping_types[small_package][table][' . $sequence . '][checked]', '');
    }

    // Select all products and test the "Cancel" button.
    $this->drupalPostForm(
      NULL,
      [
        'shipping_types[small_package][table][1][checked]' => 1,
        'shipping_types[small_package][table][2][checked]' => 1,
        'shipping_types[small_package][table][3][checked]' => 1,
        'shipping_types[small_package][table][4][checked]' => 1,
      ],
      'Cancel'
    );
    // Go back to Packages tab and try something else.
    $assert->addressEquals('admin/store/orders/' . $order->id());
    $this->clickLink('Packages');
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    $assert->pageTextContains(
      "This order's products have not been organized into packages.",
      'Package action found.'
    );

    // Now test the "Create one package" button without selecting anything.
    $this->drupalPostForm(NULL, [], 'Create one package');
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    $assert->pageTextContains(
      'Packages must contain at least one product.',
      'Validation that there must be products in a package.'
    );

    // Now test the "Create one package" button with all products selected.
    $this->drupalPostForm(
      NULL,
      [
        'shipping_types[small_package][table][1][checked]' => 1,
        'shipping_types[small_package][table][2][checked]' => 1,
        'shipping_types[small_package][table][3][checked]' => 1,
        'shipping_types[small_package][table][4][checked]' => 1,
      ],
      'Create one package'
    );

    // Check that we're now on the package list page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages');
    foreach ($order->products as $sequence => $item) {
      $assert->pageTextContains(
        $item->qty->value . ' x ' . $item->model->value,
        'Product quantity x SKU found.'
      );
    }

    // The "Create packages" local action should now be available too.
    $assert->linkExists('Create packages');
    $this->clickLink('Create packages');
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    // But we've already packaged everything...
    $assert->pageTextContains(
      'There are no products available for this type of package.',
      'Create packages local action found.'
    );

    //
    // Test "Ship", "Edit", and "Delete" operations for this package.
    //

    // First "Ship".
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $assert->linkExists('Ship');
    $this->clickLink('Ship');
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/shipments/new?pkgs=1');
    foreach ($order->products as $sequence => $item) {
      $assert->pageTextContains(
        $item->qty->value . ' x ' . $item->model->value,
        'Product quantity x SKU found.'
      );
    }

    // Second, "Edit".
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    // (Use Href to distinguish Edit operation from Edit tab.)
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/packages/1/edit');
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages/1/edit');
    // We're editing the package we already made, so all the
    // products should be checked.
    foreach ($order->products as $sequence => $item) {
      // Check that product is available for packaging.
      $assert->fieldValueEquals('products[' . $sequence . '][checked]', 1);
    }
    // Save the package to make sure the submit handler is working.
    $this->drupalPostForm(NULL, [], 'Save');
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/packages');

    // Third, "Delete".
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $assert->linkExists('Delete');
    $this->clickLink('Delete');
    // Delete takes us to confirm page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/1/delete');
    $assert->pageTextContains(
      'The products it contains will be available for repackaging.',
      'Deletion confirm question found.'
    );
    // "Cancel" returns to the package list page.
    $this->clickLink('Cancel');
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/packages');

    // Again with the "Delete".
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    // Delete returns to new packages page with all packages unchecked.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    $assert->pageTextContains(
      'Package 1 has been deleted.',
      'Package deleted message found.'
    );
    foreach ($order->products as $sequence => $item) {
      // Check that product is available for packaging.
      $assert->fieldValueEquals('shipping_types[small_package][table][' . $sequence . '][checked]', '');
    }

    // Back to no packages. Now test making more than one package.
    // Now test the "Create one package" button with all products selected.
    $this->drupalPostForm(
      NULL,
      [
        'shipping_types[small_package][table][1][checked]' => 1,
        'shipping_types[small_package][table][2][checked]' => 1,
      ],
      'Create one package'
    );

    // Check that we're now on the package list page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages');
    $assert->pageTextContains(
      $order->products[1]->qty->value . ' x ' . $order->products[1]->model->value,
      'Product quantity x SKU found.'
    );
    $assert->pageTextContains(
      $order->products[2]->qty->value . ' x ' . $order->products[2]->model->value,
      'Product quantity x SKU found.'
    );
    $assert->pageTextNotContains(
      $order->products[3]->qty->value . ' x ' . $order->products[3]->model->value,
      'Product quantity x SKU not found.'
    );
    $assert->pageTextNotContains(
      $order->products[4]->qty->value . ' x ' . $order->products[4]->model->value,
      'Product quantity x SKU not found.'
    );

    // Use "Create packages" to create a second package.
    $assert->linkExists('Create packages');
    $this->clickLink('Create packages');
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    $assert->pageTextNotContains(
      $order->products[1]->model->value,
      'Product SKU not found.'
    );
    $assert->pageTextNotContains(
      $order->products[2]->model->value,
      'Product SKU not found.'
    );
    $assert->pageTextContains(
      $order->products[3]->model->value,
      'Product SKU found.'
    );
    $assert->pageTextContains(
      $order->products[4]->model->value,
      'Product SKU found.'
    );
    $this->drupalPostForm(
      NULL,
      [
        'shipping_types[small_package][table][3][checked]' => 1,
        'shipping_types[small_package][table][4][checked]' => 1,
      ],
      'Create one package'
    );
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/packages');
    foreach ($order->products as $sequence => $item) {
      $assert->pageTextContains(
        $item->qty->value . ' x ' . $item->model->value,
        'Product quantity x SKU found.'
      );
    }

    // How do we test for two packages? Look for two "Ship" links.
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/shipments/new?pkgs=2');
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/shipments/new?pkgs=3');

    // Now delete both packages.
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $assert->pageTextContains(
      'Package 2 has been deleted.',
      'Package deleted message found.'
    );
    // There's still one left to delete...
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    $assert->pageTextContains(
      'Package 3 has been deleted.',
      'Package deleted message found.'
    );

    // Back to no packages. Now test "Make packages" button.
    $this->drupalPostForm(
      NULL,
      [
        'shipping_types[small_package][table][1][checked]' => 1,
        'shipping_types[small_package][table][2][checked]' => 1,
        'shipping_types[small_package][table][3][checked]' => 1,
        'shipping_types[small_package][table][4][checked]' => 1,
      ],
      'Make packages'
    );

    // Check that we're now on the package list page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages');
    foreach ($order->products as $sequence => $item) {
      $assert->pageTextContains(
        $item->qty->value . ' x ' . $item->model->value,
        'Product quantity x SKU found.'
      );
    }

  }

}
