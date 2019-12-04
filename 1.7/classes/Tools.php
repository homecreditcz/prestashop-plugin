<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

namespace MyLoan;

use MyLoan\HomeCredit\RequestAPI;

/**
 * Class Tools
 * @package MyLoan
 */
class Tools
{

    /**
     * @param $productID
     * @param string $imageType
     * @return mixed
     */
    public static function getProductImage($productID, $imageType = "")
    {
        if (empty($imageType)) {
            $imageType = \ImageType::getFormatedName('home');
        }

        $productImage = [];
        $productImage['url'] = "";
        $productImage['filename'] = "";
        $id_image = \Product::getCover($productID);
        if ($id_image) {
            $product = new \Product($productID);
            $link = new \Link;
            $image = new \Image($id_image['id_image']);
            $imageName = "$image->id_product-$imageType.$image->image_format";
            $productImage['url'] = $link->getImageLink($product->link_rewrite, $image->id_image, $imageType);
            $productImage['filename'] = $imageName;
        }

        return $productImage;
    }

    /**
     * Převod jednotek na jendotky pro Myloan
     * @param $amount
     * @param $currency
     * @return int
     */
    public static function convertNumberToMinorUnits($amount, $currency)
    {
        return (int)round($amount * (10 ** self::getCurrencyMinorUnit($currency)));
    }

    /**
     * Převod z HC na jendotky pro Prestashop
     * @param $amount
     * @param $currency
     * @return float
     */
    public static function convertNumberFromMinorUnits($amount, $currency)
    {
        return round($amount / (10 ** self::getCurrencyMinorUnit($currency)));
    }

    /**
     * Počet desetiných míst pro danou měnu
     * @param $currency
     * @return int
     */
    public static function getCurrencyMinorUnit($currency)
    {
        switch ((string)$currency) {
            case "CZK":
                $minor_unit = 2;
                break;
            case "EUR":
                $minor_unit = 2;
                break;
            default:
                $minor_unit = 2;
                break;
        };

        return $minor_unit;
    }

    /**
     * Vrátí doménu z url
     * @param $url
     * @return mixed
     */
    public static function getTopGenericDomainFromUrl($url)
    {
        $url = explode(".", parse_url($url, PHP_URL_HOST));
        return end($url);
    }

    /**
     * Vrátí DPH pro objednávku
     * @param $priceWithVat
     * @param $vat
     * @return float
     */
    public static function calcVatRate($priceWithVat, $vat)
    {
        return round((100 * $vat / $priceWithVat));
    }

    /**
     * Pokus zjistit číslo ulice, pokud se nepovede vyplním x
     * @param $address1
     * @param $address2
     * @return mixed|string
     */
    public static function parseStreetNumber($address1, $address2)
    {
        $streetNumber = "x";
        if (preg_match('/^([^\d]*[^\d\s]) *(\d.*)$/', $address1, $result)) {
            //address1 contain address and number+letters
            $streetNumber = $result[2];
        } else {
            if (preg_match('/^(\d+)(.*)$/', $address2, $result)) {
                //address2 contain only street number+letters
                $streetNumber = $result[1];
            }
        }

        return $streetNumber;
    }

    /**
     * Kontrola jestli produkt má minimální částku pro možnost nákupu na splátky
     * @param $productPrice
     * @return bool
     */
    private static function productHasMinimalPrice($productPrice)
    {
        $context = \Context::getContext();

        if ($productPrice !== "") {
            switch ($context->currency->iso_code) {
                case "CZK":
                    return $productPrice > \Loan::MINIMAL_PRICE_CZK;
                case "EUR":
                    return $productPrice > \Loan::MINIMAL_PRICE_EUR;
                default:
                    return false;
            }
        }

        return true;
    }

    /**
     * Jestli má zvolenou měnu která je povoléná pro Myloan
     * @return bool
     */
    private static function shopHasAllowedCurrency()
    {
        $context = \Context::getContext();

        switch ($context->currency->iso_code) {
            case "CZK":
                return in_array(
                    \MlcConfig::get(\MlcConfig::API_COUNTRY),
                    [\MlcConfig::CZ_VERSION, \MlcConfig::CZ_TEST_VERSION]
                );
            case "EUR":
                return in_array(
                    \MlcConfig::get(\MlcConfig::API_COUNTRY),
                    [\MlcConfig::SK_VERSION, \MlcConfig::SK_TEST_VERSION]
                );
            default:
                return false;
        }

        return false;
    }

    /**
     * Jestli to má modul zachytit daný hook
     * @param string $productPrice
     * @return bool
     */
    public static function shouldHookModule($productPrice = "")
    {
        return
          \MlcConfig::isModuleConfigured() &&
          self::productHasMinimalPrice($productPrice) &&
          self::shopHasAllowedCurrency() &&
          in_array(\Tools::getValue('controller'), ['product', 'order', 'orderopc', 'payment']);
    }

    /**
     * Vrátí cenu v nejmenších jednotkách pro danou měnu
     * @param $productId
     * @return int
     */
    public static function getProductPriceInMinorUnits($productId)
    {
        $context = \Context::getContext();
        $product = new \Product($productId);
        return \MyLoan\Tools::convertNumberToMinorUnits($product->getPrice(), $context->currency->iso_code);
    }

    /**
     * Vytvoření url pro kalkulačku
     * @param $productPrice
     * @return string
     */
    public static function genCalculatorUrl($productPrice)
    {
        if (\MlcConfig::get(\MlcConfig::API_CERTIFIED)) {
            $url = \MlcConfig::getApiCalcCertifiedUrl(\MlcConfig::get(\MlcConfig::API_COUNTRY));
        } else {
            $url = \MlcConfig::getApiCalcPublicUrl(\MlcConfig::get(\MlcConfig::API_COUNTRY));
            $url = self::buildPublicHcCalculatorUrl($productPrice, $url);
        }

        return $url;
    }

    /**
     * Veřejná url pro kalkulačku
     * @param $productPrice
     * @return string
     */
    public static function buildPublicHcCalculatorUrl($productPrice, $url)
    {
        $data = [
          'productSetCode' => \MlcConfig::get(\MlcConfig::API_PRODUCT_CODE),
          'price' => $productPrice,
          'downPayment' => 0,
          'apiKey' => \MlcConfig::get(\MlcConfig::API_CALC_KEY),
          'fixDownPayment' => 1
        ];
        return $url . '?' . http_build_query($data);
    }

    /**
     * Cesta k obrázku
     * @param $fileName
     * @return mixed
     */
    public static function getImagePath($fileName)
    {
        return \Media::getMediaPath(_PS_MODULE_DIR_ . \MlcConfig::MODULE_NAME . '/views/img/' . $fileName);
    }

    /**
     * @param $array
     * @param bool $border
     * @return string
     */
    public static function writeArrayData($array, $border = true)
    {
        $result = "";
        if (is_object($array)) {
            $array = (array)$array;
        }

        if (is_array($array) && count($array) > 0) {
            $result .= "<table cellspacing='0' cellpadding='2'";
            $result .= $border ? " border='1'" : null;
            $result .= ">\n";
            $result .= "<tbody>\n";
            foreach ($array as $key => $value) {
                if (is_object($value)) {
                    $value = (array)$value;
                }

                $result .= "<tr>\n";
                $result .= "<td class='valignTop'>" . $key . ":&nbsp;</td>\n";
                if (!is_array($value)) {
                    if ($value === true) {
                        $value = 'TRUE';
                    } else {
                        if ($value === false) {
                            $value = 'FALSE';
                        } else {
                            if ($value === null) {
                                $value = 'NULL';
                            }
                        }
                    }

                    $result .= "<td class='valignTop'>" . $value . "</td>\n";
                } else {
                    $result .= "<td class='valignTop'>\n";
                    $result .= self::writeArrayData($value, false);
                    $result .= "</td>\n";
                }
                $result .= "</tr>\n";
            }
            $result .= "</tbody>\n";
            $result .= "</table>\n";
            $result .= "<br />\n";
        } else {
            $result .= "<p>\n";
            $result .= "Pole neobsahuje žádná data.\n";
            $result .= "</p>\n";
        }
        return $result;
    }

    /**
     * Zkontroluje cookie pro Myloan
     * @param $cartOrderTotal
     * @return bool
     * @throws \Nette\Utils\JsonException
     */
    public static function isLoanCookieValid($cartOrderTotal)
    {
        if (\Context::getContext()->cookie->hc_calculator === false) {
            return false;
        }

        $loanCookie = \Nette\Utils\Json::decode(
            \Context::getContext()->cookie->hc_calculator,
            \Nette\Utils\Json::FORCE_ARRAY
        );

        if (is_array($loanCookie) && array_key_exists("productPrice", $loanCookie)) {
            return $cartOrderTotal == $loanCookie['productPrice'];
        } else {
            return false;
        }
    }

    /**
     * Zobrazí rekapitulaci půjčky z Cookie
     * @param $cartOrderTotal
     * @return string
     * @throws \Nette\Utils\JsonException
     */
    public static function getLoanOverview($cartOrderTotal)
    {
        if (!self::isLoanCookieValid($cartOrderTotal)) {
            return "";
        }

        $context = \Context::getContext();
        $loanCookie = \Nette\Utils\Json::decode(
            \Context::getContext()->cookie->hc_calculator,
            \Nette\Utils\Json::FORCE_ARRAY
        );

        $currencySign = $context->currency->sign;
        $currencyIsoCode = $context->currency->iso_code;

        $downPayment = self::convertNumberFromMinorUnits(
            $loanCookie['preferredDownPayment'],
            $currencyIsoCode
        );

        $installment = self::convertNumberFromMinorUnits(
            $loanCookie['preferredInstallment'],
            $currencyIsoCode
        );
        $preferredMonths = $loanCookie['preferredMonths'];

        return "$downPayment $currencySign + $preferredMonths x $installment $currencySign";
    }

    /**
     * Zobrazí chybovou hlášku
     * @param $message
     */
    public static function showErrorMessage($message)
    {
        $context = \Context::getContext();
        $module = \Module::getInstanceByName(\MlcConfig::MODULE_NAME);

        $context->smarty->assign(
            [
            "show_message" => $module->l($message),
            "type" => 'danger'
            ]
        );
    }
}
