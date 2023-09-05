<?php

namespace MyLoan\HomeCredit\EndPoints;

interface IEndPoint
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getApiUrl();

    /**
     * Vrátí URL pro certifikovaného prodejce
     *
     * @return string
     */
    public function getApiCalcUrl();

    /**
     * Vrátí URL pro kalkulačku
     *
     * @return string
     */
    public function getApiCalcPublicUrl();
}