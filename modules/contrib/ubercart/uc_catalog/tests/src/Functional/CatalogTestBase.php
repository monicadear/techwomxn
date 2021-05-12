<?php

namespace Drupal\Tests\uc_catalog\Functional;

use Drupal\Tests\uc_catalog\Traits\CatalogTestTrait;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Base class for Ubercart catalog tests.
 */
abstract class CatalogTestBase extends UbercartBrowserTestBase {
  use CatalogTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['uc_catalog'];

  /**
   * {@inheritdoc}
   */
  protected static $adminPermissions = ['view catalog'];

}
