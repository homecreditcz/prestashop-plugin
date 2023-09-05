<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

namespace MyLoan;

use MyLoan\HomeCredit\EndPointManager;

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
        return $priceWithVat != 0 ? round((100 * $vat / $priceWithVat)) : 0.0;
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
    public static function shouldHookModule($productPrice = false)
    {
        $isPriceValid = (!$productPrice) || self::productHasMinimalPrice($productPrice);
        return
          \MlcConfig::isModuleConfigured() &&
          $isPriceValid &&
          self::shopHasAllowedCurrency() &&
          in_array(\Tools::getValue('controller'), ['product', 'order', 'payment', 'orderopc']);
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
        //var_dump($product->getAttributesResume($context->language->id));
        return \MyLoan\Tools::convertNumberToMinorUnits($product->getPrice(), $context->currency->iso_code);
    }

    /**
     * Získá výchozý kód produktové sady daného produkt
     * @param $productId
     * @return string
     */

    public static function getProductSetCode($productId)
    {
        $sql = "SELECT * FROM "._DB_PREFIX_."hc_product WHERE id_product = '$productId'";

        // Výchozí produktový kód
        $productSetCode = \MlcConfig::get(\MlcConfig::API_PRODUCT_CODE);

        $row = \Db::getInstance()->getRow($sql);

        $productDiscountType = $row["discount"];
        $productDiscountReferral= $row["referral"];
        $name = \MlcConfig::REFERRAL_COOKIE_NAME;

        $cookie = new \Cookie(\MlcConfig::REFERRAL_COOKIE_NAME);

        $isDiscounted = (int)$productDiscountType !== (int)\MlcConfig::WITHOUT_DISCOUNT;
        $isReferral = (int)$productDiscountReferral !== (int)\MlcConfig::WITHOUT_DISCOUNT;

        // check if referral is in cookie data or in GET parameters
        $isReferralActive =
            $cookie->$name === \MlcConfig::get(\MlcConfig::DISCOUNT_UTM_STRING) ||
            \Tools::getValue(\MlcConfig::REFERRAL_COOKIE_NAME) === \MlcConfig::get(\MlcConfig::DISCOUNT_UTM_STRING);

        if($isDiscounted || ($isReferral && $isReferralActive)){
            $productSetCode = \MlcConfig::get(\MlcConfig::API_DISCOUNT_PRODUCT_CODE);
        }

        return $productSetCode;
    }

    /**
     * Na základě seznamu produktů určí produktovou sadu
     * @param $products
     * @return string
     */
    public static function getCartProductsSetCode($products)
    {

        $productSetCode = \MlcConfig::get(\MlcConfig::API_PRODUCT_CODE);

        foreach ($products as $product) {

            $code = self::getProductSetCode($product['id_product']);

            if($code === \MlcConfig::get(\MlcConfig::API_DISCOUNT_PRODUCT_CODE))
                $productSetCode = $code;

        }

        return $productSetCode;
    }

    /**
     * Vytvoření url pro kalkulačku
     * @param $productPrice
     * @param $productSetCode
     * @return string
     */
    public static function genCalculatorUrl($productPrice, $productSetCode)
    {
        $manager = EndPointManager::getInstance();
        if (\MlcConfig::get(\MlcConfig::API_CERTIFIED)) {
            $url = $manager->getApiCalcCertifiedUrl(\MlcConfig::get(\MlcConfig::API_COUNTRY));
        } else {
            $url = $manager->getApiCalcPublicUrl(\MlcConfig::get(\MlcConfig::API_COUNTRY));
            $url = self::buildPublicHcCalculatorUrl($productPrice, $url, $productSetCode);
        }

        return $url;
    }

    /**
     * Veřejná url pro kalkulačku
     * @param $productPrice
     * @param $url
     * @param $productSetCode
     * @return string
     */
    public static function buildPublicHcCalculatorUrl($productPrice, $url, $productSetCode)
    {
        $data = [
          'productSetCode' => $productSetCode,
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
     * @param string $message Message to be shown as error.
     */
    public static function addMyError($message){
        $cookie = \Context::getContext()->cookie;

        $tmp = json_decode($cookie->myErrors, true);

        if(!is_array($tmp)) {
            $tmp = array();
        }

        $tmp[] = $message;

        $cookie->myErrors = json_encode($tmp);
        $cookie->write();
    }

    /**
     * Zkontroluje cookie pro Myloan
     * @param $cartOrderTotal
     * @return bool
     */
    public static function isLoanCookieValid($cartOrderTotal)
    {
        if (\Context::getContext()->cookie->hc_calculator === false) {
            return false;
        }

        $loanCookie = (array)json_decode(
            \Context::getContext()->cookie->hc_calculator
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
     */
    public static function getLoanOverview($cartOrderTotal)
    {
        if (!self::isLoanCookieValid($cartOrderTotal)) {
            return "";
        }

        $context = \Context::getContext();
        $loanCookie = (array)json_decode(
            \Context::getContext()->cookie->hc_calculator
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
            "show_message" => $module->l($message, __CLASS__),
            "type" => 'danger'
            ]
        );
    }
}
