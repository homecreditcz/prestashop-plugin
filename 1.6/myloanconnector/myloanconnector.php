<?php
/**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
**/

if (!defined('_PS_VERSION_')) {
    exit;
}

include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php');

class MyLoanConnector extends PaymentModule
{
    public function __construct()
    {
        $this->name = "myloanconnector";
        $this->tab = 'payments_gateways';
        $this->version = '1.0.2';
        $this->author = 'HN Consulting Brno, s.r.o.';
        $this->controllers = array('downPayment', 'changePayment', 'loanNotification', 'loanUpdate');
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6.1.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Home Credit MyLoan', __CLASS__);
        $this->description = $this->l('Home Credit MyLoan integration', __CLASS__);
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?', __CLASS__);

        if (isset($this->context->employee) && $this->context->employee->isLoggedBack()) {
            if (!MlcConfig::isModuleConfigured()) {
                $this->warning = $this->l('Module must be configured first!', __CLASS__);
            }
        }

        $installedVersion = $this->getInstalledVersion();
        if ($this->version !== $installedVersion) {
            $this->makeMigrationToNewVersion($installedVersion);
        }
        MlcConfig::updateValue(MlcConfig::MODULE_VERSION, $this->version);

        //aby to prestashop přidal do lokalizací
        $this->l('Refresh', __CLASS__);
        $this->l('State', __CLASS__);
        $this->l('Id', __CLASS__);

        if($this->context->cookie->myErrors && is_array($this->context->controller->errors)) {

            // Vytáhnutí custom errorů v případě, kdy standardní metoda selhává
            $this->context->controller->errors = array_merge(
              $this->context->controller->errors,
              (array)json_decode($this->context->cookie->myErrors)
            );

            unset($this->context->cookie->myErrors);
            $this->context->cookie->write();

        }

        self::handleUtmDiscount();

    }
    /**
     * Kontroluje výskyt utm_source v url a nastavuje cookie se slevou
     * @return void
     */
    private static function handleUtmDiscount(){

        $cookie_name = MlcConfig::REFERRAL_COOKIE_NAME;
        if(Tools::getValue(MlcConfig::REFERRAL_COOKIE_NAME) === MlcConfig::get(MlcConfig::DISCOUNT_UTM_STRING)) {
            $cookie = new Cookie(MlcConfig::REFERRAL_COOKIE_NAME);

            $cookie->setExpire(time() + MlcConfig::REFERRAL_COOKIE_EXPIRE);

            $cookie->$cookie_name = Tools::getValue(MlcConfig::REFERRAL_COOKIE_NAME);
            $cookie->write();
        }

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

        return parent::install() && MlcConfig::install();
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

        $this->context->controller->addCSS($this->_path .  "/views/css/myloan.css");
        $this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/jquery.cooki-plugin.js');
        $this->context->controller->addJS(dirname(__FILE__) . "/views/js/iframeResizer.min.js");
        $this->context->controller->addJS(dirname(__FILE__) . "/views/js/certified.js");

        switch (MlcConfig::get(MlcConfig::API_COUNTRY)) {
            case MlcConfig::CZ_VERSION:
            case MlcConfig::CZ_TEST_VERSION:
                $this->context->controller->addCSS(
                    $this->_path . "/dist/hc-calc-CZ/style/style.css"
                );
                $this->context->controller->addJS(
                    $this->_path . "/dist/hc-calc-CZ/js/resize.js"
                );
                $this->context->controller->addJS(dirname(__FILE__) . "/views/js/appLoader-cz.js");
                break;
            case MlcConfig::SK_VERSION:
            case MlcConfig::SK_TEST_VERSION:
                $this->context->controller->addCSS(
                    $this->_path . "/dist/hc-calc-SK/style/style.css"
                );
                $this->context->controller->addJS(
                    $this->_path . "/dist/hc-calc-SK/js/resize.js"
                );
                $this->context->controller->addJS($this->_path . "/views/js/appLoader-sk.js");
        }
        $cartOrderTotal = $this->context->cart->getOrderTotal();

        if (!\MyLoan\Tools::shouldHookModule($cartOrderTotal)) {
            return false;
        }

        $cartOrderTotal = \MyLoan\Tools::convertNumberToMinorUnits(
            $cartOrderTotal,
            $this->context->currency->iso_code
        );

        $productSetCode = \MyLoan\Tools::getCartProductsSetCode($this->context->cart->getProducts());
        $productId = "";
        $productPrice = 0;
        if(isset($hookParams["product"])) {
            $productId = $hookParams["product"]->id;
            $productPrice = \MyLoan\Tools::getProductPriceInMinorUnits($productId);
        }
        $this->context->controller->addCSS(dirname(__FILE__) . "/views/css/myloan.css");

        $this->context->smarty->assign(
            [
                "isCertified" => MlcConfig::get(MlcConfig::API_CERTIFIED),
                "loanOverview" => \MyLoan\Tools::getLoanOverview($cartOrderTotal),
                "calcUrl" => \MyLoan\Tools::genCalculatorUrl($cartOrderTotal, $productSetCode),
                "calcPostUrl" => Context::getContext()->link->getModuleLink(
                    MlcConfig::MODULE_NAME,
                    'payment'
                ),
                "productId" => $productId,
                "productPrice" => $productPrice,
                "productSetCode" => $productSetCode,
                "apiKey" => \MlcConfig::get(\MlcConfig::API_CALC_KEY),
                'hcLogo' => \MyLoan\Tools::getImagePath("hc-logo.svg"),
                'actionUrl' => $this->context->link->getModuleLink(
                    MlcConfig::MODULE_NAME,
                    'payment',
                    ['cartID' => $this->context->cart->id],
                    true
                ),
                'cartOrderTotal' => $cartOrderTotal,
                "minimalPrice" => array("CZK" => \Loan::MINIMAL_PRICE_CZK, "EUR" => \Loan::MINIMAL_PRICE_EUR)
            ]
        );
        return $this->display(__FILE__, '/views/templates/front/header.tpl');
    }

    /**
     * @param $hookParams
     * @return bool|void
     */
    public function hookDisplayProductButtons($hookParams)
    {
        if (!\MyLoan\Tools::shouldHookModule(false)) {
            return false;
        }

        $productId = $hookParams["product"]->id;
        $productPrice = \MyLoan\Tools::getProductPriceInMinorUnits($productId);
        $productSetCode = \MyLoan\Tools::getProductSetCode($productId);

        $this->smarty->assign(array(
          "isCertified" => MlcConfig::get(MlcConfig::API_CERTIFIED),
          "hcLogo" => \MyLoan\Tools::getImagePath("hc-logo.svg"),
          "calcButton" => \MyLoan\Tools::getImagePath("hc-calculator.svg"),
          "productId" => $productId,
          "productPrice" => $productPrice,
          "calcUrl" => MyLoan\Tools::genCalculatorUrl("%price_placeholder%", $productSetCode),
          "calcPostUrl" => Context::getContext()->link->getModuleLink(
              MlcConfig::MODULE_NAME,
              'payment'
          ),
          "productSetCode" => $productSetCode,
          "apiKey" => \MlcConfig::get(\MlcConfig::API_CALC_KEY),
          "minimalPrice" => array("CZK" => \Loan::MINIMAL_PRICE_CZK, "EUR" => \Loan::MINIMAL_PRICE_EUR)
        ));
        $this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/jquery.cooki-plugin.js');
        $this->context->controller->addJS(dirname(__FILE__) . "/views/js/iframeResizer.min.js");
        $this->context->controller->addJS(dirname(__FILE__) . "/views/js/calculator.js");
        return $this->display(__FILE__, "calculator.tpl");
    }

    /**
     * @return bool|void
     */
    public function hookPayment()
    {
        $cartOrderTotal = $this->context->cart->getOrderTotal();

        if (!\MyLoan\Tools::shouldHookModule($cartOrderTotal)) {
            return false;
        }

        $cartOrderTotal = \MyLoan\Tools::convertNumberToMinorUnits(
            $cartOrderTotal,
            $this->context->currency->iso_code
        );

        $productSetCode = \MyLoan\Tools::getCartProductsSetCode($this->context->cart->getProducts());

        $this->context->controller->addCSS(dirname(__FILE__) . "/views/css/myloan.css");

        $this->context->smarty->assign(
            [
            "isCertified" => MlcConfig::get(MlcConfig::API_CERTIFIED),
            "loanOverview" => \MyLoan\Tools::getLoanOverview($cartOrderTotal),
            "calcUrl" => \MyLoan\Tools::genCalculatorUrl($cartOrderTotal, $productSetCode),
            "calcPostUrl" => Context::getContext()->link->getModuleLink(
                MlcConfig::MODULE_NAME,
                'payment'
            ),
            "productSetCode" => $productSetCode,
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

        //if(MlcConfig::get(MlcConfig::API_CERTIFIED)){
        /*}else{
            $this->context->controller->addJS(dirname(__FILE__) . "/views/js/notCertified.js");
        }*/
        return $this->display(__FILE__, 'payment.tpl');
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
    public function hookActionOrderStatusUpdate($params)
    {
        $loan = new Loan($params["id_order"]);
        $response = null;

        if ($loan->getApplicationId()) {
            if ($params["newOrderStatus"]->id == MlcConfig::get(MlcConfig::getIdOfOrderStateMapping(MyLoan\HomeCredit\OrderStates\ReadyToShippedState::ID))) {
                try {
                    $api = new \MyLoan\HomeCredit\RequestAPI();
                    $response = (array)$api->markOrderAsSent($loan->getApplicationId());
                } catch (Exception $e) {
                    \MyLoan\Tools::addMyError($this->l('State change error! Please contact Home Credit', __CLASS__));
                }
            } else {
                if ($params["newOrderStatus"]->id == MlcConfig::get(MlcConfig::getIdOfOrderStateMapping(MyLoan\HomeCredit\OrderStates\ReadyToDeliveredState::ID))) {
                    try {
                        $api = new \MyLoan\HomeCredit\RequestAPI();
                        $response = (array)$api->markOrderAsDelivered($loan->getApplicationId());
                    } catch (Exception $e) {
                        \MyLoan\Tools::addMyError($this->l('State change error! Please contact Home Credit', __CLASS__));
                    }
                }
            }

            if (array_key_exists("errors", $response)) {
                \MyLoan\Tools::addMyError(
                  $this->l("HomeCredit change order stare error", __CLASS__) . ":" .
                  $response["errors"][0]["message"]
                );
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

        $cancelLink = $this->context->link->getModuleLink(
          MlcConfig::MODULE_NAME,
          "loanUpdate",
          [
            'orderReference' => $order->reference,
            'secure_key' => $order->secure_key,
            'action' => "cancel"
          ],
          true
        );


        $this->context->smarty->assign([
          "loanDetail" => $loan,
          "amount" => $loan->getDownPayment(),
          "currency" => $loan->getCurrency(),
          "refreshLink" => $link,
          "cancelLink" => $cancelLink,
          "text" => [
            "id" => $this->l("Id", __CLASS__). ": ",
            "state" => $this->l("State", __CLASS__). ": ",
            "downpayment" => $this->l("Downpayment", __CLASS__). ": ",
            "button" => $this->l("Refresh", __CLASS__)
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
            'title' => $this->l('Downpayment', __CLASS__),
            'search' => false,
          ],
        ];
    }

    private function getInstalledVersion()
    {
        if (MlcConfig::hasKey(MlcConfig::MODULE_VERSION)) {
            return MlcConfig::get(MlcConfig::MODULE_VERSION);
        } else if (MlcConfig::hasKey(Tools::strtoupper($this->name . "_ORDER_STATES_INSTALLED"))) {
            return "1.0.0";
        } else {
            return $this->version;
        }
    }

    private function makeMigrationToNewVersion($installedVersion)
    {
        switch ($installedVersion) {
            case "1.0.0":
                $this->migrationTo_1_0_1($installedVersion);
            case "1.0.1":
            default:
                return true;
        }
    }

    private function migrationTo_1_0_1($installedVersion = "1.0.0")
    {
        if (version_compare($installedVersion, "1.0.1") >= 0)
            return; // Pokud nainstalovaná verze je 1.0.1 nebo novější tak migraci neprováděj
        $orderState = new MyLoan\HomeCredit\OrderStateManager();
        foreach ($orderState->getIdStates(false) as $idState) {
            MlcConfig::updateValue(MlcConfig::getIdOfOrderStateMapping($idState), MlcConfig::get($idState));
            MlcConfig::updateValue(MlcConfig::getIdOfOrderStateGenerated($idState), 1);
        }
        // Vytvořeni stavu "Nezařazeno"
        $id = MlcConfig::generateNewOrderState($orderState->getState(MyLoan\HomeCredit\OrderStates\UnclassifiedState::ID));
        MlcConfig::updateValue(MlcConfig::getIdOfOrderStateMapping(MyLoan\HomeCredit\OrderStates\UnclassifiedState::ID), $id);
    }
}
