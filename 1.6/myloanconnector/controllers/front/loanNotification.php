<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

class MyLoanConnectorLoanNotificationModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
    }

    public function initContent()
    {
        parent::initContent();

        try {
            \MyLoan\HomeCredit\ResponseAPI::processLoanCreateResponse();
            $this->module->displayConfirmation(
                $this->module->l('Connection to Home Credit My\Loan API was successful!', __CLASS__)
            );
            $orderCollection = \Order::getByReference(\Tools::getValue("orderNumber"));
            $order_id = $orderCollection->getFirst()->id;
            //Stav zamítnuto - nabídnu uživateli změnu platební metody
            if (\Tools::getValue("stateReason") == \Loan::REJECTED) {
                $myLoanConnector = new \MyLoanConnector();
                $link = $myLoanConnector->getOrderChangePaymentLink($order_id);
                $this->context->smarty->assign(
                    [
                    "linkChangePayment" => $link,
                    ]
                );
                $this->setTemplate('rejected.tpl');
            } else {
                $this->setTemplate('waitForNextStep.tpl');
            }
        } catch (Exception $e) {
            \MyLoan\Tools::showErrorMessage($e->getMessage());
            $this->setTemplate('message.tpl');
        }
    }
}
