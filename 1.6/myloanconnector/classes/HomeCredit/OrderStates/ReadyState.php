<?php

namespace MyLoan\HomeCredit\OrderStates;

class ReadyState extends AbstractState
{
    const ID = "HC_READY";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Approved",
                "cs" => "HC - Schváleno",
                "sk" => "HC - Schváleno",
            ],
            "#00FF00",
            false,
            false,
            false,
            false,
            false,
            [
                "en" => "hc_ready",
                "cs" => "hc_ready",
                "sk" => "hc_ready",
            ]
        );
    }
}