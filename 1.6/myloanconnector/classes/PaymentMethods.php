<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

class PaymentMethods extends ModuleFrontController
{
    protected $orderReference;
    protected $order;

    public $display_column_left = false;

    public function setMedia()
    {
        parent::setMedia();
    }

    public function initContent()
    {
        parent::initContent();

        if (!Tools::getIsset("orderReference")) {
            $this->showMessage("Order reference not defined.", "danger");

            return;
        }

        $this->orderReference = Tools::getValue("orderReference");
        $order_collection = Order::getByReference($this->orderReference);

        if ($order_collection->count() != 1) {
            $this->showMessage("Order reference not found.", "danger");

            return;
        }

        $this->order = new Order($order_collection->getFirst()->id);

        if (!isset($this->order)) {
            $this->showMessage("Order error.", "danger");

            return;
        }

        if ((!Tools::getIsset("secure_key") or $this->order->secure_key != Tools::getValue("secure_key"))) {
            $this->showMessage("Secure key is not defined or does not match.", "danger");

            return;
        }

        $this->paymentMethod();
    }

    protected function paymentMethod()
    {
    }

    protected function removeAllFromCart()
    {
        foreach ($this->context->cart->getProducts() as $product) {
            $this->context->cart->deleteProduct($product["id_product"]);
        }
    }

    /**
     * Zobrazí zadanou zprávu
     * @param $text
     * @param $type
     */
    protected function showMessage($text, $type)
    {
        $this->context->smarty->assign(
            array(
            "show_message" => $this->module->l($text, __CLASS__),
            "type" => $type
            )
        );

        $this->setTemplate('message.tpl');
        //$this->setTemplate('module:myloanconnector/views/templates/front/message.tpl'); verze => 1.7
    }
}
