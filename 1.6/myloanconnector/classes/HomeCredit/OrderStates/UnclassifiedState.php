<?php

namespace MyLoan\HomeCredit\OrderStates;

class UnclassifiedState extends AbstractState
{
    const ID = "HC_UNCLASSIFIED";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Unclassified",
                "cs" => "HC - Nezařazeno",
                "sk" => "HC - Nezaradené",
            ],
            "#D8D8D8",
            false,
            false,
            false,
            false,
            false,
            [
                "en" => "hc_unclassified",
                "cs" => "hc_unclassified",
                "sk" => "hc_unclassified",
            ]
        );
    }
}