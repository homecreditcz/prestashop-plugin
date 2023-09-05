<?php

namespace MyLoan\HomeCredit\EndPoints;

/**
 * Class Czech
 *
 * @author     HN Consulting Brno s.r.o
 * @copyright  2019-*
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Czech implements IEndPoint
{
    private $id = "CZ";
    private $apiUrl = "https://api.homecredit.cz/";
    private $apiCalcUrl = "https://api.homecredit.cz/public/v1/calculator/";
    private $apiPublicCalcUrl = "https://kalkulacka.homecredit.cz/";

    public function getId()
    {
        return $this->id;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function getApiCalcUrl()
    {
        return $this->apiCalcUrl;
    }

    public function getApiCalcPublicUrl()
    {
        return $this->apiPublicCalcUrl;
    }

}