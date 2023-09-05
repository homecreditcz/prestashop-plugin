<?php

use MyLoan\HomeCredit\RequestAPI;

/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

class Loan extends ObjectModel
{
    public $id_order;
    public $id_order_down_payment;
    public $withdrawal;
    public $down_payment;
    public $state_reason;
    public $application_id;
    public $application_url;
    public $check_sum;
    public $currency;

    const PROCESSING_REDIRECT_NEEDED = 'PROCESSING_REDIRECT_NEEDED';
    const PROCESSING_PREAPPROVED = 'PROCESSING_PREAPPROVED';
    const PROCESSING_APPROVED = 'PROCESSING_APPROVED';
    const PROCESSING_ALT_OFFER = 'PROCESSING_ALT_OFFER';
    const PROCESSING_SIGNED = 'PROCESSING_SIGNED';
    const PROCESSING_REVIEW = 'PROCESSING_REVIEW';
    const READY_PAID = 'READY_PAID';
    const READY_TO_SHIP = 'READY_TO_SHIP';
    const READY_SHIPPED = 'READY_SHIPPED';
    const READY_DELIVERED = 'READY_DELIVERED';
    const READY_DELIVERING = 'READY_DELIVERING';
    const REJECTED = 'REJECTED';
    const CANCELLED_RETURNED = 'CANCELLED_RETURNED';
    const CANCELLED_NOT_PAID = 'CANCELLED_NOT_PAID';

    const MINIMAL_PRICE_CZK = "1000";
    const MINIMAL_PRICE_EUR = "40";

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
      'table' => 'hc_loan',
      'primary' => 'id_order',
      'multishop' => true,
      'fields' => [
        'id_order' => [
          'type' => self::TYPE_INT,
          'validate' => 'isNullOrUnsignedId',
          'copy_post' => false,
          'required' => true,
        ],
        'id_order_down_payment' => [
          'type' => self::TYPE_INT,
          'validate' => 'isNullOrUnsignedId',
          'copy_post' => false,
          'required' => false
        ],
        'withdrawal' => [
          'type' => self::TYPE_BOOL,
          'validate' => 'isBool',
          'copy_post' => false,
          'required' => false,
        ],
        'down_payment' => [
          'type' => self::TYPE_FLOAT,
          'validate' => 'isPrice',
          'copy_post' => false,
          'required' => false
        ],
        'currency' => [
          'type' => self::TYPE_STRING,
          'validate' => 'isString',
          'copy_post' => false,
          'required' => true,
          'size' => 32
        ],
        'state_reason' => [
          'type' => self::TYPE_STRING,
          'validate' => 'isString',
          'copy_post' => false,
          'required' => true
        ],
        'application_id' => [
          'type' => self::TYPE_STRING,
          'validate' => 'isString',
          'copy_post' => false,
          'required' => false,
          'size' => 255
        ],
        'application_url' => [
          'type' => self::TYPE_STRING,
          'validate' => 'isString',
          'copy_post' => false,
          'required' => false,
          'size' => 255
        ],
        'check_sum' => [
          'type' => self::TYPE_STRING,
          'validate' => 'isString',
          'copy_post' => false,
          'required' => false,
          'size' => 1024
        ],
      ]
    ];

    /**
     * Aktualizace objednávky v Myloan
     * @param $id
     * @throws PrestaShopModuleException
     */
    public static function updateLoan($id)
    {
        try{
            $api = new RequestAPI();
        } catch(Exception $e){
            throw new PrestaShopModuleException("HomeCredit API - Cannot update loan.");
        }
        $api->updateLoan($id);
    }


    /**
     * Vrátí akontaci
     * @return mixed
     */
    public function getIdOrder()
    {
        return $this->id_order;
    }

    /**
     * @param mixed $id_order
     */
    public function setIdOrder($id_order)
    {
        $this->id_order = (int)$id_order;
    }

    /**
     * @return mixed
     */
    public function getWithdrawal()
    {
        return $this->withdrawal;
    }

    /**
     * @param mixed $withdrawal
     */
    public function setWithdrawal($withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    /**
     * Naství u objednávky akontaci
     * @return mixed
     */
    public function getDownPayment()
    {
        return $this->down_payment;
    }

    /**
     * @param mixed $down_payment
     */
    public function setDownPayment($down_payment)
    {
        $this->down_payment = $down_payment;

        DB::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "orders` 
                  SET `downpayment` = '" . $down_payment . "' 
                  WHERE `id_order` = " . $this->id_order . ";");
    }

    /**
     * Vratí měnu
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Nastaví měnu
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Vratí stav z Myloan
     * @return mixed
     */
    public function getStateReason()
    {
        return $this->state_reason;
    }

    /**
     * Nastaví stav
     * @param mixed $state_reason
     */
    public function setStateReason($state_reason)
    {
        $this->state_reason = $state_reason;
    }

    /**
     * Vrátí id předělené v Myloan
     * @return mixed
     */
    public function getApplicationId()
    {
        return $this->application_id;
    }

    /**
     * Nastaví id přidělené v Myloan
     * @param mixed $application_id
     */
    public function setApplicationId($application_id)
    {
        $this->application_id = $application_id;
    }

    /**
     * Vrátí url do eshopu
     * @return mixed
     */
    public function getApplicationUrl()
    {
        return $this->application_url;
    }

    /**
     * Nastaví url pro redirect do eshopu
     * @param mixed $application_url
     */
    public function setApplicationUrl($application_url)
    {
        $this->application_url = $application_url;
    }
}
