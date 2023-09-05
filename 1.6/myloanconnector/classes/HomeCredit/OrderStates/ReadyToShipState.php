<?php

namespace MyLoan\HomeCredit\OrderStates;

class ReadyToShipState extends AbstractState
{
    const ID = "HC_READY_TO_SHIP";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Order is ready for shipping",
                "cs" => "HC - Objednávka připravena k odeslání",
                "sk" => "HC - Objednávka připravena k odeslání",
            ],
            "#00FF00",
            false,
            false,
            false,
            false,
            false,
            [
                "en" => "hc_shipping",
                "cs" => "hc_shipping",
                "sk" => "hc_shipping",
            ]
        );
    }
}