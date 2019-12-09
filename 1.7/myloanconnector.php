<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

use MyLoan\HomeCredit\ResponseAPI;

if (!defined('_PS_VERSION_')) {
    exit;
}

include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php');

class MyLoanConnector extends PaymentModule
{
    private $isoLangIdPairs = array();

    public function __construct()
    {

        $this->name = "myloanconnector";
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'HN Consulting Brno, s.r.o.';
        $this->controllers = array('downPayment', 'changePayment', 'loanNotification', 'loanUpdate');
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6.1.23', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Home Credit MyLoan');
        $this->description = $this->l('Home Credit MyLoan integration');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (isset($this->context->employee) && $this->context->employee->isLoggedBack()) {
            if (!MlcConfig::isModuleConfigured()) {
                $this->warning = $this->l('Module must be configured first!');
            }
        }

        //aby to prestashop přidal do lokalizací
        $this->l('Refresh');
        $this->l('State');
        $this->l('Id');
    }

    /**
     * Vytvoří nové stavy
     * @param string $identificator
     * @param array $name
     * @param string $color
     * @param bool $hidden
     * @param bool $delivery
     * @param bool $logable
     * @param bool $invocice
     * @param bool $send_email
     * @param array $templates
     */
    private function createNewOrderState(
        $identificator = "myloanconnector",
        $name = array(),
        $color = "#000000",
        $hidden = false,
        $delivery = false,
        $logable = false,
        $invocice = false,
        $send_email = false,
        $templates = array()
    ) {
        $orderState = new OrderState();

        // Remove translations and templates for non existing language
        unset($name["empty"]);
        unset($templates["empty"]);

        $orderState->name = $name;
        $orderState->color = $color;
        $orderState->hidden = $hidden;
        $orderState->delivery = $delivery;
        $orderState->logable = $logable;
        $orderState->invoice = $invocice;
        $orderState->send_email = $send_email;
        $orderState->template = $templates;
        $orderState->module_name = Tools::strtoupper($identificator);

        $orderState->add();
        Configuration::updateValue(Tools::strtoupper($identificator), (int)$orderState->id);
    }

    /**
     * Provede instalaci potřebných součástí
     * @return bool|void
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $ORDER_STATES_INSTALLED = Tools::strtoupper($this->name . "_ORDER_STATES_INSTALLED");

        // Check if module was already installed in past, if so, reuse states.
        if (MlcConfig::get($ORDER_STATES_INSTALLED) != true) {
            $this->createNewOrderState(
                "HC_PROCESSING",
                array(
                  $this->getLangIdFromIso("en") => "HC - Processing",
                  $this->getLangIdFromIso("cs") => "HC - Čeká na schválení",
                  $this->getLangIdFromIso("sk") => "HC - Čeká na schválení",
                ),
                "#0000FF",
                false,
                false,
                false,
                false,
                false,
                array(
                  $this->getLangIdFromIso("en") => "hc_processing",
                  $this->getLangIdFromIso("cs") => "hc_processing",
                  $this->getLangIdFromIso("sk") => "hc_processing",
                )
            );

            $this->createNewOrderState(
                "HC_READY",
                array(
                  $this->getLangIdFromIso("en") => "HC - Approved",
                  $this->getLangIdFromIso("cs") => "HC - Schváleno",
                  $this->getLangIdFromIso("sk") => "HC - Schváleno",
                ),
                "#00FF00",
                false,
                false,
                false,
                false,
                false,
                array(
                  $this->getLangIdFromIso("en") => "hc_ready",
                  $this->getLangIdFromIso("cs") => "hc_ready",
                  $this->getLangIdFromIso("sk") => "hc_ready",
                )
            );

            $this->createNewOrderState(
                "HC_REJECTED",
                array(
                  $this->getLangIdFromIso("en") => "HC - Rejected",
                  $this->getLangIdFromIso("cs") => "HC - Zamítnuto",
                  $this->getLangIdFromIso("sk") => "HC - Zamítnuto",
                ),
                "#FF0000",
                false,
                false,
                false,
                false,
                false,
                array(
                  $this->getLangIdFromIso("en") => "hc_rejected",
                  $this->getLangIdFromIso("cs") => "hc_rejected",
                  $this->getLangIdFromIso("sk") => "hc_rejected",
                )
            );
            $this->createNewOrderState(
                "HC_CANCELLED",
                array(
                $this->getLangIdFromIso("en") => "HC - Order cancled",
                $this->getLangIdFromIso("cs") => "HC - Objednávka stornována",
                $this->getLangIdFromIso("sk") => "HC - Objednávka stornována",
                ),
                "#FF0000",
                false,
                false,
                false,
                false,
                false,
                array(
                $this->getLangIdFromIso("en") => "hc_canclled",
                $this->getLangIdFromIso("cs") => "hc_canclled",
                $this->getLangIdFromIso("sk") => "hc_canclled",
                )
            );

            $this->createNewOrderState(
                "HC_READY_TO_SHIP",
                array(
                $this->getLangIdFromIso("en") => "HC - Order is ready for shipping",
                $this->getLangIdFromIso("cs") => "HC - Objednávka připravena k odeslání",
                $this->getLangIdFromIso("sk") => "HC - Objednávka připravena k odeslání",
                ),
                "#00FF00",
                false,
                false,
                false,
                false,
                false,
                array(
                  $this->getLangIdFromIso("en") => "hc_shipping",
                  $this->getLangIdFromIso("cs") => "hc_shipping",
                  $this->getLangIdFromIso("sk") => "hc_shipping",
                )
            );

            $this->createNewOrderState(
                "HC_READY_SHIPPED",
                array(
                $this->getLangIdFromIso("en") => "HC - Order was shipped",
                $this->getLangIdFromIso("cs") => "HC - Objednávka odeslána",
                $this->getLangIdFromIso("sk") => "HC - Objednávka odeslána",
                ),
                "#00DD00",
                false,
                true,
                false,
                false,
                false,
                array(
                $this->getLangIdFromIso("en") => "hc_shipped",
                $this->getLangIdFromIso("cs") => "hc_shipped",
                $this->getLangIdFromIso("sk") => "hc_shipped",
                )
            );

            $this->createNewOrderState(
                "HC_READY_DELIVERED",
                array(
                $this->getLangIdFromIso("en") => "HC - Order was delivered",
                $this->getLangIdFromIso("cs") => "HC - Objednávka byla doručena",
                $this->getLangIdFromIso("sk") => "HC - Objednávka byla doručena",
                ),
                "#00DD00",
                false,
                true,
                false,
                false,
                false,
                array(
                $this->getLangIdFromIso("en") => "hc_delivered",
                $this->getLangIdFromIso("cs") => "hc_delivered",
                $this->getLangIdFromIso("sk") => "hc_delivered",
                )
            );

            $this->createNewOrderState(
                "HC_READY_DELIVERING",
                array(
                $this->getLangIdFromIso("en") => "HC - Order is being deliverd",
                $this->getLangIdFromIso("cs") => "HC - Objednávka doručována",
                $this->getLangIdFromIso("sk") => "HC - Objednávka doručována",
                ),
                "#009900",
                false,
                true,
                false,
                false,
                false,
                array(
                  $this->getLangIdFromIso("en") => "hc_delivering",
                  $this->getLangIdFromIso("cs") => "hc_delivering",
                  $this->getLangIdFromIso("sk") => "hc_delivering",
                )
            );

            $this->createNewOrderState(
                "HC_READY_PAID",
                array(
                $this->getLangIdFromIso("en") => "HC - Order is paid",
                $this->getLangIdFromIso("cs") => "HC - Objednávka zaplacena",
                $this->getLangIdFromIso("sk") => "HC - Objednávka zaplacena",
                ),
                "#009900",
                false,
                true,
                false,
                true,
                false,
                array(
                $this->getLangIdFromIso("en") => "hc_paid",
                $this->getLangIdFromIso("cs") => "hc_paid",
                $this->getLangIdFromIso("sk") => "hc_paid",
                )
            );

            Configuration::updateValue($ORDER_STATES_INSTALLED, true);
        }

        return parent::install() && MlcConfig::install();
    }

    /**
     * Vratí id jazyka pokud je v prestashopu nainstalován
     * @param $iso
     * @return mixed
     */
    public function getLangIdFromIso($iso)
    {
        if (empty($this->isoLangIdPairs)) {
            foreach (Language::getLanguages() as $language) {
                $this->isoLangIdPairs[$language["iso_code"]] = $language["id_lang"];
            }
        }

        // if language don't exist, assign empty ID
        if (array_key_exists($iso, $this->isoLangIdPairs)) {
            return $this->isoLangIdPairs[$iso];
        } else {
            return "empty";
        }
    }

    /**
     * Odinstaluje modul
     * @return bool|void
     */
    public function uninstall()
    {
        if (!parent::uninstall() || !MlcConfig::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * @return string|void
     */
    public function getContent()
    {

        if (Tools::isSubmit('submit' . $this->name)) {
            return MlcConfig::saveConfig() . MlcConfig::renderForm();
        }

        return MlcConfig::renderForm();
    }

    /**
     * @param $hookParams
     * @return bool
     */
    public function hookHeader($hookParams)
    {
        if (!\MyLoan\Tools::shouldHookModule()) {
            return false;
        }

        $this->context->controller->addCSS($this->_path . "/views/css/myloan.css");
        $this->context->controller->addJS($this->_path . "/views/js/jquery.cookie.js");


        switch (MlcConfig::get(MlcConfig::API_COUNTRY)) {
            case MlcConfig::CZ_VERSION:
            case MlcConfig::CZ_TEST_VERSION:
                $this->context->controller->addCSS(
                  $this->_path . "/vendor/homecredit/widget-calculator-cz/hc-calc/style/style.css"
                );
                $this->context->controller->addJS(
                  $this->_path . "/vendor/homecredit/widget-calculator-cz/hc-calc/js/resize.js"
                );
                $this->context->controller->addJS($this->_path . "/views/js/appLoader-cz.js");
                break;
            case MlcConfig::SK_VERSION:
            case MlcConfig::SK_TEST_VERSION:
                $this->context->controller->addCSS(
                  $this->_path . "/vendor/homecredit/widget-calculator-sk/hc-calc/style/style.css"
                );
                $this->context->controller->addJS(
                  $this->_path . "/vendor/homecredit/widget-calculator-sk/hc-calc/js/resize.js"
                );
                $this->context->controller->addJS($this->_path . "/views/js/appLoader-sk.js");
        }
    }

    /**
     * @param $hookParams
     * @return bool|void
     */
    public function hookDisplayProductButtons($hookParams)
    {
        $priceByCurrency = \Product::getPriceStatic(\Tools::getValue('id_product'), true, null, 6);

        if (!\MyLoan\Tools::shouldHookModule($priceByCurrency)) {
            return false;
        }

        $productId = \Tools::getValue('id_product');
        $productPrice = \MyLoan\Tools::getProductPriceInMinorUnits($productId);

        $this->smarty->assign(array(
          "isCertified" => MlcConfig::get(MlcConfig::API_CERTIFIED),
          "hcLogo" => \MyLoan\Tools::getImagePath("hc-logo.svg"),
          "calcButton" => \MyLoan\Tools::getImagePath("hc-calculator.svg"),
          "productId" => $productId,
          "productPrice" => $productPrice,
          "calcUrl" => MyLoan\Tools::genCalculatorUrl($productPrice),
          "productSetCode" => \MlcConfig::get(\MlcConfig::API_PRODUCT_CODE),
          "apiKey" => \MlcConfig::get(\MlcConfig::API_CALC_KEY),
        ));

        return $this->display(__FILE__, "calculator.tpl");
    }

    /**
     * @return bool|void
     * @throws \Nette\Utils\JsonException
     * @return array|void
     */
    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return;
        }

        $cartOrderTotal = $this->context->cart->getOrderTotal();
        if (!\MyLoan\Tools::shouldHookModule($cartOrderTotal)) {
            return;
        }

        $cartOrderTotal = \MyLoan\Tools::convertNumberToMinorUnits(
            $cartOrderTotal,
            $this->context->currency->iso_code
        );

        $this->context->smarty->assign(
            [
            'module_templates' => dirname(__FILE__).'/views/templates/',
            "isCertified" => MlcConfig::get(MlcConfig::API_CERTIFIED),
            "loanOverview" => \MyLoan\Tools::getLoanOverview($cartOrderTotal),
            "calcUrl" => \MyLoan\Tools::genCalculatorUrl($cartOrderTotal),
            "productSetCode" => \MlcConfig::get(\MlcConfig::API_PRODUCT_CODE),
            "apiKey" => \MlcConfig::get(\MlcConfig::API_CALC_KEY),
            'hcLogo' => \MyLoan\Tools::getImagePath("hc-logo.svg"),
            'actionUrl' => $this->context->link->getModuleLink(
                MlcConfig::MODULE_NAME,
                'payment',
                ['cartID' => $this->context->cart->id],
                true
            ),
            'cartOrderTotal' => $cartOrderTotal
            ]
        );

        $paymentForm = $this->fetch('module:myloanconnector/views/templates/hook/payment.tpl');

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->l('Installment purchase Home Credit'))
          ->setCallToActionText($this->l('Installment purchase Home Credit'))
          ->setForm($paymentForm);

        $payment_options = array(
          $newOption
        );
        return $payment_options;
    }

    /**
     * Vygeneruje link na změnu platební metody
     * Returns link for changing payment method
     * @param $orderId
     * @return string
     */
    public function getOrderChangePaymentLink($orderId)
    {
        $order = new Order($orderId);

        $link = $this->context->link->getModuleLink(
            MlcConfig::MODULE_NAME,
            'changePayment',
            [
            'orderReference' => $order->reference,
            'secure_key' => $order->secure_key,
            ],
            true
        );

        return $link;
    }

    /**
     *
     * @param $params
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $loan = new Loan($params["id_order"]);

        if ($loan->getApplicationId()) {
            if ($params["newOrderStatus"]->module_name == "HC_READY_SHIPPED") {
                try {
                    $api = new \MyLoan\HomeCredit\RequestAPI();
                    $api->markOrderAsSent($loan->getApplicationId());
                } catch (Exception $e) {
                    $this->context->controller->errors[] = $this->l('State change error! Please contact Home Credit');
                }
            } else {
                if ($params["newOrderStatus"]->module_name == "HC_READY_DELIVERED") {
                    try {
                        $api = new \MyLoan\HomeCredit\RequestAPI();
                        $api->markOrderAsDelivered($loan->getApplicationId());
                    } catch (Exception $e) {
                        $this->context->controller->errors[] = $this->l('State change error! Please contact Home Credit');
                    }
                }
            }
        }
    }

    /**
     * @param $params
     * @return string
     */
    public function hookDisplayAdminOrder($params)
    {
        $loan = new Loan($params["id_order"]);
        $order = new Order($params["id_order"]);

        if ($loan->getApplicationId() === null) {
            return "";
        }

         $link = $this->context->link->getModuleLink(
             MlcConfig::MODULE_NAME,
             "loanUpdate",
             [
             'orderReference' => $order->reference,
             'secure_key' => $order->secure_key,
             'action' => "update"
             ],
             true
         );

        $this->context->smarty->assign([
          "loanDetail" => $loan,
          "amount" => $loan->getDownPayment(),
          "currency" => $loan->getCurrency(),
          "refreshLink" => $link,
          "text" => [
            "id" => $this->l("Id"). ": ",
            "state" => $this->l("HcState"). ": ",
            "downpayment" => $this->l("Downpayment"). ": ",
            "button" => $this->l("Refresh")
          ]
        ]);

        return $this->display(__FILE__, 'adminOrder.tpl');
    }

    /**
     * @param $params
     */
    public function hookActionAdminOrdersListingResultsModifier($params)
    {
        foreach ($params['list'] as &$order) {
            $loan = new Loan($order["id_order"]);
            if ($loan->getApplicationId() !== null) {
                $order['downpayment'] = round($loan->getDownPayment(), -2) . " " . $loan->getCurrency();
            } else {
                $order['downpayment'] = "---";
            }
        }
    }

    /**
     * @param array $params
     */
    public function hookActionAdminOrdersListingFieldsModifier(array $params)
    {
        $params['fields'] += [
          'downpayment' => [
            'title' => $this->l('Downpayment'),
            'search' => false,
          ],
        ];
    }
}
