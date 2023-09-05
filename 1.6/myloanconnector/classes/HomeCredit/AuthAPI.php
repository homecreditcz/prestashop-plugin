<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

namespace MyLoan\HomeCredit;

use Exception;
use HcApi\HcApi;
use MlcConfig;
use PrestaShopModuleException;

/**
 * Class AuthAPI
 * @package MyLoan\HomeCredit
 */
class AuthAPI
{
    /**
     * @var string
     */
    private $apiAddress;
    /**
     * @var string
     */
    private $apiUser;
    /**
     * @var string
     */
    private $apiPassword;

    /**
     * @var HcApi
     */
    private $client;

    /**
     * AuthAPI constructor.
     */
    public function __construct()
    {
        $credentials = MlcConfig::getConfigArray();
        $this->setApiAddress($credentials[MlcConfig::API_URL]);
        $this->setApiUser($credentials[MlcConfig::API_USER]);
        $this->setApiPassword($credentials[MlcConfig::API_PASSWORD]);
        $this->authorize();

    }

    /**
     * Vytvoří komunikační třídu z OneClickApi
     * @return HcApi
     * @throws PrestaShopModuleException
     */
    public function authorize()
    {
        try {
            //var_dump($this->getApiAddress(),$this->getApiPassword(),$this->getApiUser());
            $this->client = new HcApi(array($this->getApiUser(), $this->getApiPassword()), $this->getApiAddress());
        } catch (Exception $e){
            throw new PrestaShopModuleException("HomeCredit API - Cannot construct HcApi.", $e->getCode());
        }

        return $this->client;

    }

    /**
     * Zjistí jestli jsem přihlášený do Myloan
     * @return bool
     * @throws PrestaShopModuleException
     */
    public function isLogged()
    {
        if($this->client === null){
            $this->authorize();
        }
        return $this->client->isLoggedIn();
    }

    /**
     * Vrátí adresu API
     * @return string
     */
    public function getApiAddress()
    {
        return $this->apiAddress;
    }

    /**
     * Nastaví adresu API
     * @param string $apiAddress
     */
    public function setApiAddress($apiAddress)
    {
        $this->apiAddress = $apiAddress;
    }

    /**
     * Vratí uživatele pro Myloan
     * @return string
     */
    public function getApiUser()
    {
        return $this->apiUser;
    }

    /**
     * Nastaví uživatele pro Myloan
     * @param string $apiUser
     */
    public function setApiUser($apiUser)
    {
        $this->apiUser = $apiUser;
    }

    /**
     * Vrátí heslo pro Myloan
     * @return string
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * Nastaví heslo pro Myloan
     * @param string $apiPassword
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;
    }
}
