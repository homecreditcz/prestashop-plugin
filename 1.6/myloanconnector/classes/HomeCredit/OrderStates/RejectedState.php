<?php

namespace MyLoan\HomeCredit\OrderStates;

class RejectedState extends AbstractState
{
    const ID = "HC_REJECTED";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Rejected",
                "cs" => "HC - Zamítnuto",
                "sk" => "HC - Zamítnuto",
            ],
            "#FF0000",
            false,
            false,
            false,
            false,
            false,
            [
                "en" => "hc_rejected",
                "cs" => "hc_rejected",
                "sk" => "hc_rejected",
            ]
        );
    }
}