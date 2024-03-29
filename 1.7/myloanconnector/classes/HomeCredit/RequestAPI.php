<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

namespace MyLoan\HomeCredit;

use Address;
use Context;
use Currency;
use Customer;
use Exception;
use HcApi\HcApi;
use Link;
use Loan;
use Manufacturer;
use MlcConfig;
use MyLoan\Tools;
use Order;
use PrestaShopException;
use PrestaShopModuleException;

/**
 * Class RequestAPI
 * @package MyLoan\HomeCredit
 */
class RequestAPI extends AuthAPI
{
    /**
     *
     */
    const END_POINT_CZ = "https://api.homecredit.cz/";
    /**
     *
     */
    const END_POINT_SK = "https://api.homecredit.sk/";

    /**
     *
     */
    const END_POINT_CZ_TEST = "https://apicz-test.homecredit.net/verdun-train/";
    /**
     *
     */
    const END_POINT_SK_TEST = "https://apisk-test.homecredit.net/verdun-train/";

    /**
     *
     */
    const END_POINT_CALCULATOR_CZ = "https://api.homecredit.cz/public/v1/calculator/";
    /**
     *
     */
    const END_POINT_CALCULATOR_SK = "https://api.homecredit.sk/public/v1/calculator/";

    /**
     *
     */
    const END_POINT_CALCULATOR_PUBLIC_CZ = "https://kalkulacka.homecredit.cz/";
    /**
     *
     */
    const END_POINT_CALCULATOR_PUBLIC_TEST_CZ = "https://kalkulacka.train.hccs.net/";
    /**
     *
     */
    const END_POINT_CALCULATOR_PUBLIC_SK = "https://kalkulacka.homecredit.sk/";
    /**
     *
     */
    const END_POINT_CALCULATOR_PUBLIC_TEST_SK = "https://kalkulacka-sk.train.hccs.net/";

    /**
     *
     */
    const END_POINT_CALCULATOR_CZ_TEST = "https://apicz-test.homecredit.net/verdun-train/public/v1/calculator/";
    /**
     *
     */
    const END_POINT_CALCULATOR_SK_TEST = "https://apisk-test.homecredit.net/verdun-train/public/v1/calculator/";


    private $client;

    /**
     * RequestAPI constructor.
     * @throws PrestaShopModuleException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setClient($this->authorize());
    }

    /**
     * aktualizace objedn�vky v Myloan
     * @param string $orderNumberPS
     * @return array
     * @throws PrestaShopModuleException
     */
    public function updateLoan($orderNumberPS)
    {
        $loan = new Loan($orderNumberPS);
        $response = $this->getLoanDetail($loan->getApplicationId());

        if(array_key_exists("stateReason", $response)) {
            $loan->setStateReason($response["stateReason"]);
        }

        if(array_key_exists("gatewayRedirectUrl", $response)) {
            $loan->setApplicationUrl($response["gatewayRedirectUrl"]);
        }

        $loan->setDownPayment(Tools::convertNumberFromMinorUnits(
          $response["settingsInstallment"]["preferredDownPayment"]["amount"],
          $response["settingsInstallment"]["preferredDownPayment"]["currency"]
        ));
        $loan->setCurrency($response["settingsInstallment"]["preferredDownPayment"]["currency"]);

        $loan->update();

        ResponseAPI::changeOrderState($loan->getStateReason(), $orderNumberPS);

        return $response;
    }

    /**
     * Vytvo�en� objedn�vky v Myloan
     * @param string $orderNumberPS
     * @return array|null
     * @throws PrestaShopException
     */
    public function createLoan($orderNumberPS)
    {
        $context = Context::getContext();

        $order = new Order($orderNumberPS);
        $currency = $context->currency;
        $link = $context->link;
        $customer = $order->getCustomer();
        $deliveryAddress = new Address($order->id_address_invoice);
        $invoiceAddress = new Address($order->id_address_invoice);

        $application = array(
          "customer" => $this->createCustomer($customer, $invoiceAddress),
          "order" => $this->createOrder($order, $currency, $deliveryAddress, $invoiceAddress),
          "type" => "INSTALLMENT",
          "merchantUrls" => $this->createMerchantUrl($link),
          "settingsInstallment" => $this->createSettingsInstallment($currency),
          "agreementPersonalDataProcessing" => true,
        );

        try {

            $response = $this->getClient()->createApplication(json_encode($application));

        } catch (Exception $e){

            throw new PrestaShopModuleException("HomeCredit API - Request error.");

        }

        $loan = new Loan();
        $loan->setIdOrder($orderNumberPS);
        $loan->setStateReason($response['stateReason']);
        $loan->setApplicationId($response['id']);
        $loan->setApplicationUrl($response['gatewayRedirectUrl']);
        $loan->setCurrency($currency->sign);
        $loan->add();

        return $response;
    }

    /**
     * Vezme nastaven� z ji� nastaven�ch session a nastav� to do Myloan syst�mu
     * @return array|false
     */
    private function createFingerprintComponents()
    {
        $loanCookie = null;
        if (isset(Context::getContext()->cookie->hc_calculator)) {

            $loanCookie = (array)json_decode(Context::getContext()->cookie->hc_calculator);

            unset(Context::getContext()->cookie->hc_calculator);

            Context::getContext()->cookie->write();
        }

        if (is_array($loanCookie) &&
          array_key_exists('preferredMonths', $loanCookie) &&
          array_key_exists('preferredInstallment', $loanCookie) &&
          array_key_exists('preferredDownPayment', $loanCookie) &&
          array_key_exists('productCode', $loanCookie)
        ) {
            return $loanCookie;
        } else {
            return false;
        }
    }

    /**
     * Vytvo�� t��du customer pro dal�� pr�ci z OneClickApi
     * @param Customer $customer
     * @param Address $invoiceAddress
     * @return array Customer
     */
    private function createCustomer(Customer $customer, Address $invoiceAddress)
    {

        $address = $this->createAddress(
          $invoiceAddress->firstname,
          $invoiceAddress->lastname,
          $invoiceAddress->city,
          $invoiceAddress->address1,
          Tools::parseStreetNumber($invoiceAddress->address1, $invoiceAddress->address2),
          $invoiceAddress->postcode,
          'CONTACT'
        );

        $phone = $invoiceAddress->phone;

        // If phone is empty, try mobile
        if(empty($invoiceAddress->phone)) {
            $phone = $invoiceAddress->phone_mobile;
        }

        return array (
          'firstName' => $customer->firstname,
          'lastName' =>  $customer->lastname,
          'email' => $customer->email,
          'phone' => trim($phone),
          'addresses' => [$address],
          'tin' => $invoiceAddress->vat_number,
          'ipAddress' => \Tools::getRemoteAddr()
        );
    }

    /** Vytvo�� pole adresy
     * @param string $firstname
     * @param string $lastname
     * @param string $city
     * @param string $streetAddress
     * @param string $streetNumber
     * @param string $zip
     * @param string string $addressType
     * @return array
     */
    private function createAddress($firstname, $lastname, $city, $streetAddress, $streetNumber, $zip, $addressType){
        return array (
          'name' => $firstname . " " . $lastname,
          'city' => $city,
          'streetAddress' => $streetAddress,
          'streetNumber' => $streetNumber,
          'zip' => $zip,
          'addressType' => $addressType,
        );
    }

    /**
     * Vytvo�� jednotliv� polo�ky pro dal�� pr�ci z OneClickApi
     * @param Order $order
     * @param Currency $currency
     * @return array
     */
    private function createItems(Order $order, Currency $currency)
    {
        $items = [];
        foreach ($order->getProducts() as $orderItem) {

            $itemTotalPriceWithVat = $orderItem['total_price_tax_incl'];
            $itemTotalPriceWithoutVat = $orderItem['total_price_tax_excl'];
            $itemTotalVat = $itemTotalPriceWithVat - $itemTotalPriceWithoutVat;
            $itemVatRate = $orderItem['tax_rate'];
            $itemQuantity = $orderItem['product_quantity'];

            $manufacturer = new Manufacturer($orderItem['id_manufacturer']);

            $link = new Link();

            $image = \Image::getCover($orderItem['id_product']);

            $imageLink = "http://".$link->getImageLink(urlencode($orderItem['product_name']), $image["id_image"]);
            if(strpos($imageLink, "localhost") || strpos($imageLink, "127.0.0.1")){
                $imageLink = "https://via.placeholder.com/150";
            }

            $itemLink = $link->getProductLink(
              (int)$orderItem['id_product']
            );

            $items[] = array (
              'code' => $orderItem['id_product'],
              'ean' => $orderItem['ean13'],
              'name' => $orderItem['product_name'],
              'quantity' =>  $itemQuantity,
              'manufacturer' => $manufacturer->name,
              'unitPrice' =>
                array (
                  'amount' => Tools::convertNumberToMinorUnits(
                    $itemTotalPriceWithVat / $itemQuantity, $currency->iso_code
                  ),
                  'currency' => $currency->iso_code,
                ),
              'unitVat' =>
                array (
                  'amount' => Tools::convertNumberToMinorUnits(
                    $itemTotalVat / $itemQuantity, $currency->iso_code
                  ),
                  'currency' => $currency->iso_code,
                  'vatRate' => $itemVatRate,
                ),
              'totalPrice' =>
                array (
                  'amount' => Tools::convertNumberToMinorUnits($itemTotalPriceWithVat, $currency->iso_code),
                  'currency' => $currency->iso_code,
                ),
              'totalVat' =>
                array (
                  'amount' => Tools::convertNumberToMinorUnits($itemTotalVat, $currency->iso_code),
                  'currency' => $currency->iso_code,
                  'vatRate' => $itemVatRate,
                ),
              'image' =>
                array (
                  'filename' => basename($imageLink, "/"),
                  'url' => "$imageLink",
                ),
              'productUrl' => $itemLink,
            );
        }

        return $items;
    }

    /**
     * Vytvo�� objedn�vku pro dal�� pr�ci z OneClickApi
     * @param Order $order
     * @param Currency $currency
     * @param Address $addressDelivery
     * @param Address $addressInvoice
     * @return array
     */
    private function createOrder(
        Order $order,
        Currency $currency,
        Address $addressDelivery,
        Address $addressInvoice
    ) {

        $addressBilling = $this->createAddress(
          $addressInvoice->firstname,
          $addressInvoice->lastname,
          $addressInvoice->city,
          $addressInvoice->address1,
          Tools::parseStreetNumber($addressInvoice->address1, $addressInvoice->address2),
          $addressInvoice->postcode,
          'BILLING'
        );

        $addressDelivery = $this->createAddress(
          $addressDelivery->firstname,
          $addressDelivery->lastname,
          $addressDelivery->city,
          $addressDelivery->address1,
          Tools::parseStreetNumber($addressDelivery->address1, $addressDelivery->address2),
          $addressDelivery->postcode,
          'DELIVERY'
        );


        $cartTotalPriceWithVat = $order->total_paid;
        $cartTotalPriceWithoutVat = $order->total_paid_tax_excl;
        $cartTotalVat = $cartTotalPriceWithVat - $cartTotalPriceWithoutVat;

        $orderTotalWithVat = Tools::convertNumberToMinorUnits(
            $cartTotalPriceWithVat,
            $currency->iso_code
        );

        $orderTotalVat = Tools::convertNumberToMinorUnits(
            $cartTotalVat,
            $currency->iso_code
        );

        $orderTotalVatRate = Tools::calcVatRate($cartTotalPriceWithVat, $cartTotalVat);

        $totalPrice = array (
            'amount' => $orderTotalWithVat,
            'currency' => $currency->iso_code,
        );

        $totalVat = array (
            'amount' => $orderTotalVat,
            'currency' => $currency->iso_code,
            'vatRate' => $orderTotalVatRate,
          );

        return array(
          'number' => $order->reference,
          'totalPrice' =>  $totalPrice,
          'totalVat' => [$totalVat],
          'variableSymbols' => ["{$order->id}"],
          'addresses' => [$addressBilling, $addressDelivery],
          'items' => $this->createItems($order, $currency)
        );
    }

    /**
     * Notifika�n� url
     * @param Link $link
     * @return array
     */
    private function createMerchantUrl(Link $link)
    {
        $loanNotificationURL = $link->getModuleLink(MlcConfig::MODULE_NAME, 'loanNotification', [], true);

        return array (
          'approvedRedirect' => $loanNotificationURL,
          'rejectedRedirect' => $loanNotificationURL,
          'notificationEndpoint' => $loanNotificationURL,
        );

    }

    /**
     * Vytvo�� defaultn� nastaven� pro u�ivatele, pokud je mo�n� vezme z cookies pokud nen� je toto zvoleno a� v Myloan
     * @param Currency $currency
     * @return array
     */
    private function createSettingsInstallment(Currency $currency)
    {
        $fingerprintComponents = $this->createFingerprintComponents();

        $settingsInstallment =
          array (
            'productCode' => $fingerprintComponents["productCode"],
            'productSetCode' => MlcConfig::get(MlcConfig::API_PRODUCT_CODE),
          );

        if ($fingerprintComponents) {

            $settingsInstallment['preferredMonths'] = $fingerprintComponents['preferredMonths'];
            $settingsInstallment['preferredInstallment'] =
                array (
                  'amount' => $fingerprintComponents['preferredInstallment'],
                  'currency' => $currency->iso_code,
                );
            $settingsInstallment['preferredDownPayment'] =
                array (
                  'amount' => $fingerprintComponents['preferredDownPayment'],
                  'currency' => $currency->iso_code,
                );

        }



        return $settingsInstallment;

    }

    /**
     * Ozna�� objedn�vku jako doru�enou v Myloan
     * @param string $applicationId
     * @return array|null
     * @throws PrestaShopModuleException
     */
    public function markOrderAsDelivered($applicationId)
    {
        try {
            return $this->getClient()->markOrderAsDelivered($applicationId);
        } catch (Exception $e) {
            throw new PrestaShopModuleException("HomeCredit API - Cannot mark order as delivered.");
        }
    }

    /**
     * Ozna�� objedn�vku jako odeslanou v Myloan
     * @param string $applicationId
     * @return array|null
     * @throws PrestaShopModuleException
     */
    public function markOrderAsSent($applicationId)
    {
        try {
            return $this->getClient()->markOrderAsSent($applicationId);
        } catch (Exception $e) {
            throw new PrestaShopModuleException("HomeCredit API - Cannot mark order as sent.");
        }
    }

    /**
     * @param $loanID
     * @return array|null
     * @throws PrestaShopModuleException
     */
    public function getLoanDetail($loanID)
    {
        try {
            return $this->getClient()->getApplicationDetail($loanID);
        } catch (Exception $e) {
            throw new PrestaShopModuleException("HomeCredit API - Cannot get loan detail.");
        }
    }

    /**
     * @return HcApi
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param HcApi $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }
}
