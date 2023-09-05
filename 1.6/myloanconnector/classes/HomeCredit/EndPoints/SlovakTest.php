<?php

namespace MyLoan\HomeCredit\EndPoints;

/**
 * Class SlovakTest
 *
 * @author     HN Consulting Brno s.r.o
 * @copyright  2019-*
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SlovakTest implements IEndPoint
{
    private $id = "SK_TEST";
    private $apiUrl = "https://apisk-test.homecredit.sk/verdun-train/";
    private $apiCalcUrl = "https://apisk-test.homecredit.sk/verdun-train/public/v1/calculator/";
    private $apiPublicCalcUrl = "https://kalkulacka-sk.train.hccs.net/";

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
