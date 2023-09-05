<?php

namespace MyLoan\HomeCredit\OrderStates;

class ReadyToShippedState extends AbstractState
{
    const ID = "HC_READY_SHIPPED";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Order was shipped",
                "cs" => "HC - Objednávka odeslána",
                "sk" => "HC - Objednávka odeslána",
            ],
            "#00DD00",
            false,
            true,
            false,
            false,
            false,
            [
                "en" => "hc_shipped",
                "cs" => "hc_shipped",
                "sk" => "hc_shipped",
            ]
        );
    }
}