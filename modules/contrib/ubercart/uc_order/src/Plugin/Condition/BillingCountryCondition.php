<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Order billing country' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_billing_country",
 *   label = @Translation("Check an order's billing country"),
 *   category = @Translation("Order"),
 *   context_definitions = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "countries" = @ContextDefinition("string",
 *       label = @Translation("Countries"),
 *       list_options_callback = "countryOptions",
 *       multiple = TRUE,
 *       required = TRUE,
 *       assignment_restriction = "input"
 *     )
 *   }
 * )
 */
class BillingCountryCondition extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The country_manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check user's billing country");
  }

  /**
   * Constructs a BillingCountryCondition object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The core country_manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CountryManagerInterface $countryManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->countryManager = $countryManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('country_manager')
    );
  }

  /**
   * Returns an array of country options.
   *
   * @return array
   *   An array of 2-character country codes keyed by country name.
   */
  public function countryOptions() {
    return $this->countryManager->getEnabledList();
  }

  /**
   * Evaluates if the user's billing address in one of the selected countries.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   * @param array $countries
   *   Array of 2-character country codes.
   *
   * @return bool
   *   TRUE if the user billing address is in one of the given countries.
   */
  protected function doEvaluate(OrderInterface $order, array $countries = []) {
    return in_array($order->getAddress('billing')->getCountry(), $countries);
  }

}
