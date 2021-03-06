<?php

namespace Drupal\Tests\uc_payment\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the order payments form.
 *
 * @group ubercart
 */
class OrderPaymentsFormTest extends UbercartBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['uc_payment', 'uc_payment_pack'];

  /**
   * {@inheritdoc}
   */
  protected static $adminPermissions = [
    'view payments',
    'manual payments',
    'delete payments',
  ];

  /**
   * Number of digits after decimal point, for currency rounding.
   *
   * @var int
   */
  protected $precision = 2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');

    // Get configured currency precision.
    $config = \Drupal::config('uc_store.settings')->get('currency');
    $this->precision = $config['precision'];
  }

  /**
   * Tests administration form for displaying, entering, and deleting payments.
   */
  public function testOrderPayments() {
    // Check out with the test product.
    $method = $this->createPaymentMethod('check');
    $this->addToCart($this->product);
    $order = $this->checkout();
    // Add a payment of $1 so that the order total and
    // current balance are different.
    uc_payment_enter($order->id(), 'check', 1.0);

    // Log in as admin user to test order payments form.
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Goto order payments form and confirm order total and
    // payments total of $1 show up.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/payments');
    $assert->responseContains(
      '<span class="uc-price">' . uc_currency_format($order->getTotal()) . '</span>',
      'Order total is correct'
    );
    $assert->responseContains(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0) . '</span>',
      'Current balance is correct'
    );

    // Add a partial payment.
    $first_payment = round($order->getTotal() / 4.0, $this->precision);
    $edit = [
      'amount' => $first_payment,
      'method' => 'check',
      'comment' => 'Test <b>markup</b> in comments.',
    ];
    $this->drupalPostForm(
      'admin/store/orders/' . $order->id() . '/payments',
      $edit,
      'Record payment'
    );
    $assert->pageTextContains('Payment entered.');
    // Verify partial payment shows up in table.
    $assert->responseContains(
      '<span class="uc-price">' . uc_currency_format($first_payment) . '</span>',
      'Payment appears on page.'
    );
    // Verify balance.
    $assert->responseContains(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0 - $first_payment) . '</span>',
      'Current balance is correct'
    );
    // Verify markup in comments.
    $assert->responseContains(
      'Test <b>markup</b> in comments.',
      'Markup is preserved in payment receipt comments.'
    );
    // Add another partial payment.
    $second_payment = round($order->getTotal() / 2.0, $this->precision);
    $edit = [
      'amount' => $second_payment,
      'method' => 'check',
      'comment' => 'Test <em>markup</em> in comments.',
    ];
    $this->drupalPostForm(
      'admin/store/orders/' . $order->id() . '/payments',
      $edit,
      'Record payment'
    );
    // Verify partial payment shows up in table.
    $assert->responseContains(
      '<span class="uc-price">' . uc_currency_format($second_payment) . '</span>',
      'Payment appears on page.'
    );
    // Verify balance.
    $assert->responseContains(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0 - $first_payment - $second_payment) . '</span>',
      'Order total is correct'
    );

    // Delete first partial payment.
    $assert->linkExists('Delete');
    $this->clickLink('Delete');
    // Delete takes us to confirm page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/payments/1/delete');
    // Check that the deletion confirm question was found.
    $assert->pageTextContains('Are you sure you want to delete this payment?');
    // "Cancel" returns to the payments list page.
    $this->clickLink('Cancel');
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/payments');

    // Again with the "Delete".
    // Delete the first partial payment, not the $1 initial payment.
    $this->clickLink('Delete', 1);
    $this->drupalPostForm(NULL, [], 'Delete');
    // Delete returns to new payments page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/payments');
    $assert->pageTextContains('Payment deleted.');

    // Verify balance has increased.
    $assert->responseContains(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0 - $second_payment) . '</span>',
      'Current balance is correct'
    );

    // Go to order log and ensure two payments and
    // one payment deletion were logged.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/log');
    // Check that the first payment was logged.
    $assert->pageTextContains('Check payment for ' . uc_currency_format($first_payment) . ' entered.');
    // Check that the second payment was logged.
    $assert->pageTextContains('Check payment for ' . uc_currency_format($second_payment) . ' entered.');
    // Check that the payment deletion was logged.
    $assert->pageTextContains('Check payment for ' . uc_currency_format($first_payment) . ' deleted.');
  }

}
