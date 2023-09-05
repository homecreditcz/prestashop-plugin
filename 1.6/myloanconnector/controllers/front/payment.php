<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

class MyLoanConnectorPaymentModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
    }

    public function initContent()
    {
        parent::initContent();
        if (Tools::getValue('hc_calculator') != '')
            return true;

        $module = \Module::getInstanceByName(\MlcConfig::MODULE_NAME);
        $cartOrderTotal = $this->context->cart->getOrderTotal();
        $order_id = null;

        if (!\MyLoan\Tools::shouldHookModule($cartOrderTotal)) {
            \MyLoan\Tools::showErrorMessage(
                $module->l('An error occurred while confirming your order.', __CLASS__) . " ".
                $module->l('Please try again later.', __CLASS__)
            );
            $this->setTemplate('message.tpl');
            return false;
        }

        try {
            if (!\MyLoan\Validate::validateOrder(
                $this->context->cart,
                $this->context->customer,
                $this->module,
                $this->context->currency,
                $this->context->shop
            )) {
                \MyLoan\Tools::showErrorMessage($this->l('Order is invalid.', __CLASS__));
                $this->setTemplate('message.tpl');
            }

            $order_id = (int)$this->module->currentOrder;

            $request = new \MyLoan\HomeCredit\RequestAPI();
            $response = $request->createLoan($order_id);
            Tools::redirect($response["gatewayRedirectUrl"]);
        } catch (Exception $e) {
            if (isset($order_id)) {
                \MyLoan\HomeCredit\ResponseAPI::changeOrderState(\Loan::REJECTED, $order_id);
            }

            $this->context->smarty->assign(
              [
                "error" => true,
                "linkChangePayment" => $this->module->getOrderChangePaymentLink($order_id)
              ]
            );

            $this->setTemplate('rejected.tpl');
        }
    }

    public function postProcess()
    {
        $cookie_name = "hc_calculator";

        if (Tools::getIsset($cookie_name)) {
            $this->context->cookie->$cookie_name = Tools::getValue($cookie_name);
            $this->context->cookie->write();
        }
    }
}
