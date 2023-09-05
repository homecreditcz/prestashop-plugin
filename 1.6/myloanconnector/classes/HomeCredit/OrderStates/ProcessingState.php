<?php

namespace MyLoan\HomeCredit\OrderStates;

class ProcessingState extends AbstractState
{
    const ID = "HC_PROCESSING";

    public function __construct()
    {
        parent::__construct(
            self::ID,
            [
                "en" => "HC - Processing",
                "cs" => "HC - Čeká na schválení",
                "sk" => "HC - Čeká na schválení",
            ],
            "#0000FF",
            false,
            false,
            false,
            false,
            false,
            [
                "en" => "hc_processing",
                "cs" => "hc_processing",
                "sk" => "hc_processing",
            ]
        );
    }
}