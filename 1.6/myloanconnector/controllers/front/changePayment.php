<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

require_once(dirname(__FILE__) . "/../../classes/PaymentMethods.php");

class MyLoanConnectorChangePaymentModuleFrontController extends PaymentMethods
{
    private function addProductsFromOrderToCart()
    {
        $cart = null;

        if ($this->context->cookie->id_cart) {
            $cart = new Cart($this->context->cookie->id_cart);
        }

        if (!isset($cart) or !$this->context->cart->id) {
            $cart = new Cart();
            $cart->id_customer = (int)($this->context->cookie->id_customer);
            $cart->id_address_delivery = (int)(Address::getFirstCustomerAddressId($cart->id_customer));
            $cart->id_address_invoice = $cart->id_address_delivery;
            $cart->id_lang = (int)($this->context->cookie->id_lang);
            $cart->id_currency = (int)($this->context->cookie->id_currency);
            $cart->id_carrier = 1;
            $cart->recyclable = 0;
            $cart->gift = 0;
            $cart->add();
            $this->context->cookie->id_cart = (int)($cart->id);
            $cart->update();
        }

        foreach ($this->order->getProducts() as $id => $product) {
            // Because of PS validator
            unset($id);

            $cart->updateQty($product["product_quantity"], $product["product_id"], $product["product_attribute_id"]);
        }
    }

    protected function paymentMethod()
    {
        if ($this->order->current_state != MlcConfig::get(\MlcConfig::getIdOfOrderStateMapping(\MyLoan\HomeCredit\OrderStates\RejectedState::ID)) {
            $this->showMessage("Order is not was not denied by HC. Can't change payment method.", "danger");
            return;
        }

        $this->removeAllFromCart();

        $this->addProductsFromOrderToCart();

        Tools::redirect('index.php?controller=order');
    }
}
