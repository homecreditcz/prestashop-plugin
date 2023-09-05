<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

namespace MyLoan;

/**
 * Class Validate
 * @package MyLoan\Loan
 */
class Validate
{
    /**
     * Zkontroluje objednávku
     * @param \Cart $cart
     * @param \Customer $customer
     * @param \Module $module
     * @param \Currency $currency
     * @param \Shop $shop
     * @return bool
     */
    public static function validateOrder(
        \Cart $cart,
        \Customer $customer,
        \Module $module,
        \Currency $currency,
        \Shop $shop
    ) {
        return
          $cart->id_customer &&
          $cart->id_address_delivery &&
          $cart->id_address_invoice &&
          $module->active &&
          self::checkCurrency($currency, $cart) &&
          $module->validateOrder(
              (int)$cart->id,
              \MlcConfig::get(\MlcConfig::getIdOfOrderStateMapping(\MyLoan\HomeCredit\OrderStates\ProcessingState::ID)),
              $cart->getOrderTotal(true, \Cart::BOTH),
              $module->displayName,
              null,
              null,
              (int)$currency->id,
              false,
              $customer->secure_key,
              $shop
          );
    }

    /**
     * Zkontroluje měnu
     * @param \Currency $currency
     * @param \Cart $cart
     * @return bool
     */
    public static function checkCurrency(\Currency $currency, \Cart $cart)
    {
        $currency_order = new \Currency($cart->id_currency);
        $currencies_module = $currency->getCurrency((int)$cart->id_currency);

        if (isset($currencies_module['id_currency'])) {
            $currencies_module = [$currencies_module];
        }

        foreach ($currencies_module as $currency_module) {
            if ($currency_order->id == $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vrátí data a provede validaci
     * @param $pairs
     * @return array|bool
     */
    public static function getDataAndValidate($pairs)
    {
        $form_data = array();
        foreach ($pairs as $key => $method) {
            $data = \Tools::getValue($key);

            if (\Validate::$method($data)) {
                $form_data[$key] = $data;
            } else {
                // Because of PS validator
                unset($method);
                return false;
            }
        }

        return $form_data;
    }
}
