<?php

namespace MyLoan\HomeCredit\OrderStates;

class ReadyToDeliveringState extends AbstractState
{
    const ID = "HC_READY_DELIVERING";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Order is being delivered",
                "cs" => "HC - Objednávka doručována",
                "sk" => "HC - Objednávka doručována",
            ],
            "#009900",
            false,
            true,
            false,
            false,
            false,
            [
                "en" => "hc_delivering",
                "cs" => "hc_delivering",
                "sk" => "hc_delivering",
            ]
        );
    }
}