<?php

namespace MyLoan\HomeCredit\EndPoints;

/**
 * Class CzechTest
 *
 * @author     HN Consulting Brno s.r.o
 * @copyright  2019-*
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CzechTest implements IEndPoint
{
    private $id = "CZ_TEST";
    private $apiUrl = "https://apicz-test.homecredit.cz/verdun-train/";
    private $apiCalcUrl = "https://apicz-test.homecredit.cz/verdun-train/public/v1/calculator/";
    private $apiPublicCalcUrl = "https://kalkulacka.train.hccs.net/";

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

    function getApiCalcPublicUrl()
    {
        return $this->apiPublicCalcUrl;
    }

}
