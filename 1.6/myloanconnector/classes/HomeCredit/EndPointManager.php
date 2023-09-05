<?php

namespace MyLoan\HomeCredit;

use \InvalidArgumentException;
use MyLoan\HomeCredit\EndPoints\Czech;
use MyLoan\HomeCredit\EndPoints\CzechTest;
use MyLoan\HomeCredit\EndPoints\IEndPoint;
use MyLoan\HomeCredit\EndPoints\Slovak;
use MyLoan\HomeCredit\EndPoints\SlovakTest;
use Tools;

/**
 * Class EndPointManager
 *
 * @package MyLoan\HomeCredit
 */
class EndPointManager
{
    /**
     * @var IEndPoint[]
     */
    private $endPoints = [];
    private static $instance = null;

    /**
     * EndPointManager constructor.
     */
    private function __construct() {
        $this->addEndPoint(new Czech);
        $this->addEndPoint(new Slovak);
        $this->addEndPoint(new CzechTest);
        $this->addEndPoint(new SlovakTest);
    }

    /**
     * @return EndPointManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Přidání nového endpointu. Je možné i přepisování endPointu.
     * @param IEndPoint $endPoint
     */
    public function addEndPoint(IEndPoint $endPoint) {
        $this->endPoints[Tools::strtoupper($endPoint->getId())] = $endPoint;
    }

    /**
     * @return string[]
     */
    public function getVersionList() {
        return array_map(function(IEndPoint $endPoint) {
            return $endPoint->getId();
        }, $this->endPoints);
    }

    public function getApiUrl($version)
    {
        return $this->findValue($version, __FUNCTION__);
    }

    /**
     * Vrátí URL pro certifikovaného prodejce
     * @param string $version
     * @return string
     */
    public function getApiCalcCertifiedUrl($version) {
        return $this->findValue($version, "getApiCalcUrl");
    }

    /**
     * Vrátí URL pro kalkulačku
     * @param string $version
     * @return string
     */
    public function getApiCalcPublicUrl($version) {
        return $this->findValue($version, __FUNCTION__);
    }

    private function findEndPoint($id) {
        $id = Tools::strtoupper($id);
        if (!array_key_exists($id, $this->endPoints))
            throw new InvalidArgumentException("Chybějící konfigurace pro endPoint (id: {$id}");

        return $this->endPoints[$id];
    }

    private function findValue($id, $propertyGetter) {
        $endPoint = $this->findEndPoint($id);

        return call_user_func([$endPoint, $propertyGetter]);
    }
}