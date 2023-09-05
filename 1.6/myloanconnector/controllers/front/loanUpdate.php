<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

use MyLoan\HomeCredit\ResponseAPI;

class MyLoanConnectorLoanUpdateModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::getIsset("action") && Tools::getIsset("orderReference")) {

            $orderReference = Tools::getValue("orderReference");

            $order_collection = Order::getByReference($orderReference);

            if ($order_collection->count() != 1) {
                $this->showMessage("Order reference not found.", "danger");

                return;
            }

            $order = new Order($order_collection->getFirst()->id);

            if ((!Tools::getIsset("secure_key") or $order->secure_key != Tools::getValue("secure_key"))) {


                return;
            }

            // Update action
            if (Tools::getValue("action") == "update") {

                // Update loan data - triggered by constructor
                Loan::updateLoan($order_collection->getFirst()->id);
                new Loan($order_collection->getFirst()->id, null, null);

            }

            // Cancel action
            if (Tools::getValue("action") == "cancel") {


                $loan = new Loan($order_collection->getFirst()->id, null, null);
                $client  = new \MyLoan\HomeCredit\RequestAPI();

                try {
                    $client->getClient()->cancelApplication(
                      $loan->getApplicationId(),
                      Tools::getValue("reason"),
                      Tools::getValue("message")
                    );
                } catch(Exception $e) {
                    $this->showMessage("Cannot cancel! Error: ".$e->getMessage(), "danger");
                }

                // Update loan
                Loan::updateLoan($order_collection->getFirst()->id);
                new Loan($order_collection->getFirst()->id, null, null);

            }

            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
    }

    protected function showMessage($text, $type)
    {
        $this->context->smarty->assign(
            array(
            "show_message" => $this->module->l($text),
            "type" => $type
            )
        );

        $this->setTemplate('message.tpl');
        //$this->setTemplate('module:myloanconnector/views/templates/front/message.tpl'); verze => 1.7
    }
}
