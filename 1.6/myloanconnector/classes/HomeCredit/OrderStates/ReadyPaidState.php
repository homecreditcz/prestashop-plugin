<?php

namespace MyLoan\HomeCredit\OrderStates;

class ReadyPaidState extends AbstractState
{
    const ID = "HC_READY_PAID";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Order is paid",
                "cs" => "HC - Objednávka zaplacena",
                "sk" => "HC - Objednávka zaplacena",
            ],
            "#009900",
            false,
            true,
            false,
            true,
            false,
            [
                "en" => "hc_paid",
                "cs" => "hc_paid",
                "sk" => "hc_paid",
            ]
        );
    }
}