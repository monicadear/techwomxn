<?php

namespace Drupal\Tests\uc_tax\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;
use Drupal\uc_tax\Entity\TaxRate;

/**
 * Provides a common set-up and utility routines for tax tests.
 */
abstract class TaxTestBase extends UbercartBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'uc_cart',
    'uc_payment',
    'uc_payment_pack',
    'uc_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $adminPermissions = [/*'administer rules', */'administer taxes'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Defines a new tax rate.
   *
   * @param string $plugin_id
   *   ID of the UbercartTaxRate plugin to use.
   * @param array $values
   *   Values to insert into the tax rate entity form.
   *
   * @return \Drupal\uc_tax\TaxRateInterface
   *   The TaxRate entity that was created.
   */
  protected function createTaxRate($plugin_id, array $values = []) {
    $has_user = $this->loggedInUser;
    if (!$has_user) {
      $this->drupalLogin($this->adminUser);
    }

    $values += [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm('admin/store/config/tax/add/' . $plugin_id, $values, 'Save tax rate');

    if (!$has_user) {
      $this->drupalLogout();
    }

    return TaxRate::load($values['id']);
  }

}
