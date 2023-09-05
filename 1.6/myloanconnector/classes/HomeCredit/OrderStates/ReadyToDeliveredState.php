<?php

namespace MyLoan\HomeCredit\OrderStates;

class ReadyToDeliveredState extends AbstractState
{
    const ID = "HC_READY_DELIVERED";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Order was delivered",
                "cs" => "HC - Objednávka byla doručena",
                "sk" => "HC - Objednávka byla doručena",
            ],
            "#00DD00",
            false,
            true,
            false,
            false,
            false,
            [
                "en" => "hc_delivered",
                "cs" => "hc_delivered",
                "sk" => "hc_delivered",
            ]
        );
    }
}