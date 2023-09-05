<?php

namespace MyLoan\HomeCredit\EndPoints;

/**
 * Class Slovak
 *
 * @author     HN Consulting Brno s.r.o
 * @copyright  2019-*
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Slovak implements IEndPoint
{
    private $id = "SK";
    private $apiUrl = "https://api.homecredit.sk/";
    private $apiCalcUrl = "https://api.homecredit.sk/public/v1/calculator/";
    private $apiPublicCalcUrl = "https://kalkulacka.homecredit.sk/";

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