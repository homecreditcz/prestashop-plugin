<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

namespace MyLoan\HomeCredit;

use Loan;
use MlcConfig;
use MyLoan\HomeCredit\OrderStates\ProcessingState;
use MyLoan\HomeCredit\OrderStates\ReadyPaidState;
use MyLoan\HomeCredit\OrderStates\ReadyState;
use MyLoan\HomeCredit\OrderStates\ReadyToDeliveredState;
use MyLoan\HomeCredit\OrderStates\ReadyToDeliveringState;
use MyLoan\HomeCredit\OrderStates\ReadyToShippedState;
use MyLoan\HomeCredit\OrderStates\RejectedState;
use MyLoan\HomeCredit\OrderStates\UnclassifiedState;
use MyLoan\Tools;
use Order;
use PrestaShopModuleException;

class ResponseAPI
{
    /**
     *
     * @throws PrestaShopModuleException
     */
    public static function processLoanCreateResponse()
    {
        $orderReference = \Tools::getValue("orderNumber");
        $check_sum = \Tools::getValue("checkSum");
        $state_reason = \Tools::getValue("stateReason");

        if (!$orderReference || !$check_sum || !$state_reason) {

            $array = json_decode(file_get_contents("php://input"), true);

            if (
                !is_array($array) ||
                !@array_key_exists("orderReference", $array) ||
                !@array_key_exists("checkSum", $array) ||
                !@array_key_exists("stateReason", $array)
            ) {
                throw new PrestaShopModuleException("HomeCredit API - empty required parameter.");
            } else {
                $orderReference = $array["orderReference"];
                $check_sum = $array["checkSum"];
                $state_reason = $array["stateReason"];
            }
        }

        $secretKey = MlcConfig::get(MlcConfig::API_SECRETCODE);
        $data = $orderReference.":".$state_reason;

        if (\Tools::strtoupper(hash_hmac('sha512', $data, $secretKey)) != $check_sum) {
            throw new \PrestaShopModuleException("HomeCredit API - Wrong checkSum.");
        }

        $orderCollection = Order::getByReference($orderReference);

        if($orderCollection->getFirst() == false){
            throw new \PrestaShopModuleException("HomeCredit API - Unknown order.");
        }

        $order_id = $orderCollection->getFirst()->id;

        Loan::updateLoan($order_id);
        $loan = new Loan($order_id);

        //var_dump($loan);
        if (!self::authLoanCreateResponse($loan)) {
            throw new PrestaShopModuleException("HomeCredit API - unauthorized request");
        }

        self::changeOrderState($state_reason, $order_id);
    }

    /**
     *
     * @param Loan $loan
     * @return bool
     */
    public static function authLoanCreateResponse(Loan $loan)
    {
        return $loan->getApplicationId() === (string)\Tools::getValue("applicationId");
    }

    /**
     * Nastaví stav objednávky podle stavu z Myloan
     * @param $stateReason
     * @param $order_id
     * @throws PrestaShopModuleException
     */
    public static function changeOrderState($stateReason, $order_id)
    {
        $order = new Order($order_id);
        $managedState = self::transformStateReasonToManagedState($stateReason);

        if (MlcConfig::hasKey(MlcConfig::getIdOfOrderStateMapping($managedState))) {
            $newState = MlcConfig::get(MlcConfig::getIdOfOrderStateMapping($managedState));
        } elseif (!MlcConfig::isOrderStateGenerated(UnclassifiedState::ID)) {
            $newState = MlcConfig::get(MlcConfig::getIdOfOrderStateMapping(UnclassifiedState::ID));
        } else {
            $newState = MlcConfig::generateNewOrderState((new OrderStateManager)->getState(UnclassifiedState::ID));
            MlcConfig::updateValue(UnclassifiedState::ID, $newState);
        }

        if ($order->current_state != $newState) {
            $order->setCurrentState($newState);
            $order->update();
        }
    }

    /**
     * @param string $stateReason
     * @return string
     * @throws PrestaShopModuleException
     */
    private static function transformStateReasonToManagedState($stateReason)
    {
        switch ($stateReason) {
            case Loan::PROCESSING_PREAPPROVED:
            case Loan::PROCESSING_REDIRECT_NEEDED:
            case Loan::PROCESSING_REVIEW:
            case Loan::PROCESSING_SIGNED:
            case Loan::PROCESSING_APPROVED:
            case Loan::PROCESSING_ALT_OFFER:
                return ProcessingState::ID;
            case Loan::CANCELLED_NOT_PAID:
            case Loan::CANCELLED_RETURNED:
            case Loan::REJECTED:
                return RejectedState::ID;
            case Loan::READY_TO_SHIP:
                return ReadyState::ID;
            case Loan::READY_SHIPPED:
                return ReadyToShippedState::ID;
            case Loan::READY_DELIVERING:
                return ReadyToDeliveringState::ID;
            case Loan::READY_DELIVERED:
                return ReadyToDeliveredState::ID;
            case Loan::READY_PAID:
                return ReadyPaidState::ID;
            default:
                throw new PrestaShopModuleException("HomeCredit API - unknown response stateReason");
        }
    }
}
