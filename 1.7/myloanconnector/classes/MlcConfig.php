<?php
/**
 * @author     HN Consulting Brno s.r.o
 * @copyright  2019-*
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 **/

use MyLoan\HomeCredit\OrderStateManager;
use MyLoan\HomeCredit\OrderStates\AbstractState;
use MyLoan\HomeCredit\OrderStates\UnclassifiedState;

/**
 * Class MlcConfig
 */
class MlcConfig extends Configuration
{
    /**
     *
     */
    const MODULE_NAME = "myloanconnector";
    /**
     *
     */
    const MODULE_PREFIX = "HC_";
    /**
     *
     */
    const API_COUNTRY = self::MODULE_PREFIX . "API_COUNTRY";
    /**
     *
     */
    const API_URL = self::MODULE_PREFIX . "API_URL";
    /**
     *
     */
    const API_CALC_URL = self::MODULE_PREFIX . "API_CALC_URL";
    /**
     *
     */
    const API_USER = self::MODULE_PREFIX . "API_USER";
    /**
     *
     */
    const API_PASSWORD = self::MODULE_PREFIX . "API_PASSWORD";
    /**
     *
     */
    const API_SECRETCODE = self::MODULE_PREFIX . "API_SECRETCODE";
    /**
     *
     */
    const API_CERTIFIED = self::MODULE_PREFIX . "API_CERTIFIED";
    /**
     *
     */
    const EXPORT_METHOD = self::MODULE_PREFIX . "EXPORT_METHOD";
    /**
     *
     */
    const API_PRODUCT_CODE = self::MODULE_PREFIX . "API_PRODUCT_CODE";

    /**
     *
     */
    const API_CALC_KEY = self::MODULE_PREFIX . "API_CALC_KEY";

    /**
     *
     */
    const CZ_VERSION = "CZ";
    /**
     *
     */
    const SK_VERSION = "SK";
    /**
     *
     */
    const CZ_TEST_VERSION = "CZ_TEST";
    /**
     *
     */
    const SK_TEST_VERSION = "SK_TEST";

    const OPTION_ID_GENERATE = -2;
    const OPTION_ID_UNCLASSIFIED = -1;

    /**
     * Metoda kter� naisntaluje v�e pot�ebn�
     * @return bool
     */
    public static function install()
    {
        return
          self::installDefault() &&
          self::registerHooks() &&
          self::createDatabaseTables() &&
          self::alterDatabaseTables();
    }

    /**
     * Vlo�� defaultn� nastaven�
     * @return bool
     */
    public static function installDefault()
    {
        $manager = new OrderStateManager();
        $default_data = self::getConfigArray($manager);

        foreach ($manager->getIdStates() as $id) {
            $default_data[$id] = self::OPTION_ID_GENERATE;
        }
        $default_data[self::API_USER] = "";
        $default_data[self::API_PASSWORD] = "";
        $default_data[self::API_SECRETCODE] = "";
        $default_data[self::API_CERTIFIED] = "0";
        $default_data[self::API_PRODUCT_CODE] = "";
        $default_data[self::API_CALC_KEY] = "calculator_test_key";
        $default_data[self::EXPORT_METHOD] = "0";

        switch (\Tools::strtoupper(\MyLoan\Tools::getTopGenericDomainFromUrl($_SERVER['SERVER_NAME']))) {
            case self::CZ_VERSION:
                $default_data[self::API_COUNTRY] = "CZ";
                $default_data[self::API_URL] = \MyLoan\HomeCredit\RequestAPI::END_POINT_CZ;
                $default_data[self::API_CALC_URL] = \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_PUBLIC_CZ;
                break;
            case self::SK_VERSION:
                $default_data[self::API_COUNTRY] = "SK";
                $default_data[self::API_URL] = \MyLoan\HomeCredit\RequestAPI::END_POINT_SK;
                $default_data[self::API_CALC_URL] = \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_PUBLIC_SK;
                break;
            default:
                $default_data[self::API_COUNTRY] = "CZ";
                $default_data[self::API_URL] = \MyLoan\HomeCredit\RequestAPI::END_POINT_CZ;
                $default_data[self::API_CALC_URL] = \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_PUBLIC_CZ;
                break;
        }

        return self::updateValues($default_data);
    }

    /**
     * Registrace hooks z prestahopu
     * @return bool
     */
    public static function registerHooks()
    {
        $module = Module::getInstanceByName(self::MODULE_NAME);

        return
          $module->registerHook('leftColumn') &&
          $module->registerHook('header') &&
          $module->registerHook('displayProductButtons') &&
          $module->registerHook('paymentOptions') &&
          $module->registerHook('actionPaymentConfirmation') &&
          $module->registerHook('actionOrderStatusPostUpdate') &&
          $module->registerHook('actionOrderStatusUpdate') &&
          $module->registerHook('actionGetExtraMailTemplateVars') &&
          $module->registerHook('displayAdminOrder') &&
          $module->registerHook('actionAdminOrdersListingResultsModifier') &&
          $module->registerHook('actionAdminOrdersListingFieldsModifier');
    }


    /**
     * Vytvo�� datab�zi
     * @return bool
     */
    public static function createDatabaseTables()
    {
        return
          \Db::getInstance()->Execute(
              'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hc_loan` (
            `id_order` int(10) unsigned NOT NULL,
            `id_order_down_payment` int(10) unsigned DEFAULT NULL,
            `withdrawal` tinyint(1) DEFAULT NULL,
            `down_payment` decimal(20,6) NOT NULL DEFAULT 0,
            `currency` varchar(32) DEFAULT NULL,
            `state_reason` ENUM("PROCESSING_REDIRECT_NEEDED","PROCESSING_APPROVED","PROCESSING_SIGNED","READY_TO_SHIP",
                "READY_SHIPPED","READY_DELIVERED","REJECTED","CANCELLED_RETURNED","CANCELLED_NOT_PAID") NOT NULL,
            `application_id` varchar(255) DEFAULT NULL,
            `application_url` varchar(255) DEFAULT NULL,
            `check_sum` varchar(1024) DEFAULT NULL,
            PRIMARY KEY (`id_order`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
          );
    }


    /** Nastav� povolen� typy stav� objedn�vek podle volby v administraci modulu.
     * @param int $type
     */
    public static function setExpeditionType($type = 1){

        switch ($type){
            case 0:
            case "DELIVERED":
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '1' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get("HC_READY_SHIPPED")."';"
                );
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '0' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get("HC_READY_DELIVERED")."';"
                );
                break;

            default:
            case 1:
            case "SHIPPED":
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '0' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get("HC_READY_SHIPPED")."';"
                );
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '1' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get("HC_READY_DELIVERED")."';"
                );
            break;
        }

    }

    /**
     * P�id�n� sloupce do tabulky objedn�vek
     * @return bool
     */
    public static function alterDatabaseTables()
    {
        $exists = \DB::getInstance()->query("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "orders` LIKE 'downpayment'");

        if ($exists->rowCount() == 0) {
            return \DB::getInstance()->Execute(
                "ALTER TABLE `" . _DB_PREFIX_ . "orders` ADD `downpayment` decimal(20,6) NOT NULL DEFAULT 0;"
            );
        }

        return true;
    }

    /**
     * Ulo�en� konfigurace
     * @return bool
     */
    public static function saveConfig()
    {
        $module = Module::getInstanceByName(self::MODULE_NAME);
        $context = \Context::getContext();

        if (!isset($context->employee) || !$context->employee->isLoggedBack()) {
            return false;
        }

        $mlcValidate = [
          self::API_COUNTRY => "isString",
          self::API_USER => "isString",
          self::API_PASSWORD => "isString",
          self::API_SECRETCODE => "isString",
          self::API_CERTIFIED => "isBool",
          self::API_PRODUCT_CODE => "isString",
          self::API_CALC_KEY => "isString",
          self::EXPORT_METHOD => "isBool"
        ];

        $manager = new OrderStateManager();
        foreach ($manager->getIdStates(false) as $id) {
            $mlcValidate[$id] = "isString";
        }

        $mlcData = \MyLoan\Validate::getDataAndValidate($mlcValidate);
        if (!$mlcData) {
            return $module->displayError($module->l('Please make sure you filled all fields in correct format.'));
        }

        $mlcData = self::generateOrderMapping($manager, $mlcData);

        if (!self::requiredFields($mlcData)) {
            return $module->displayError($module->l('Please fill in all the required fields.'));
        }

        $mlcData[self::API_URL] = self::getApiUrl($mlcData[self::API_COUNTRY]);
        $mlcData[self::API_CALC_URL] = $mlcData[self::API_CERTIFIED] ?
          self::getApiCalcCertifiedUrl($mlcData[self::API_COUNTRY]) :
          self::getApiCalcPublicUrl($mlcData[self::API_COUNTRY]);

        MlcConfig::setExpeditionType($mlcData[self::EXPORT_METHOD]);

        if (!self::updateValues($mlcData)) {
            return $module->displayError($module->l('An error occurred while updating your configuration. Please try again.'));
        }

        if (($testApi = self::testHCApiConnection()) !== true) {
            return $module->displayError($testApi);
        }

        return $module->displayConfirmation($module->l('Connection to Home Credit MyLoan API was successful!'));
    }


    /**
     * @return bool
     */
    public static function uninstall()
    {
        /*if (!Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'hc_loan`')) {
            return false;
        }*/

        $mlcData = self::getConfigArray(new OrderStateManager);
        foreach ($mlcData as $key => $config) {
            // Unset because of PS validator
            unset($config);
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Z�sk�n� nastaven�
     *
     * @param OrderStateManager $manager
     * @return array
     */
    public static function getConfigArray(OrderStateManager $manager = null)
    {
        $config = [
            self::API_COUNTRY => self::get(self::API_COUNTRY),
            self::API_URL => self::get(self::API_URL),
            self::API_CALC_URL => self::get(self::API_CALC_URL),
            self::API_USER => self::get(self::API_USER),
            self::API_PASSWORD => self::get(self::API_PASSWORD),
            self::API_SECRETCODE => self::get(self::API_SECRETCODE),
            self::API_CERTIFIED => self::get(self::API_CERTIFIED),
            self::API_PRODUCT_CODE => self::get(self::API_PRODUCT_CODE),
            self::API_CALC_KEY => self::get(self::API_CALC_KEY),
            self::EXPORT_METHOD => self::get(self::EXPORT_METHOD)
        ];

        if ($manager !== null) {
            foreach ($manager->getIdStates(false) as $idState) {
                $config[$idState] = self::get($idState);
            }
        }
        return $config;
    }

    /**
     * Vr�t� url pro dan� nastaven�
     * @param $countryIsoCode
     * @return string
     */
    public static function getApiUrl($countryIsoCode)
    {
        switch (Tools::strtoupper($countryIsoCode)) {
            case self::CZ_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CZ;
            case self::SK_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_SK;
            case self::CZ_TEST_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CZ_TEST;
            case self::SK_TEST_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_SK_TEST;
        }
    }

    /**
     * Vr�cen� url pro kalkula�ku
     * @param $countryIsoCode
     * @return string
     */
    public static function getApiCalcPublicUrl($countryIsoCode)
    {
        switch (Tools::strtoupper($countryIsoCode)) {
            case self::CZ_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_PUBLIC_CZ;
            case self::CZ_TEST_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_PUBLIC_TEST_CZ;
            case self::SK_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_PUBLIC_SK;
            case self::SK_TEST_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_PUBLIC_TEST_SK;
        }
    }

    /**
     * Vr�t� url pro certifikovan�ho prodejce
     * @param $countryIsoCode
     * @return string
     */
    public static function getApiCalcCertifiedUrl($countryIsoCode)
    {
        switch (Tools::strtoupper($countryIsoCode)) {
            case self::CZ_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_CZ;
            case self::SK_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_SK;
            case self::CZ_TEST_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_CZ_TEST;
            case self::SK_TEST_VERSION:
                return \MyLoan\HomeCredit\RequestAPI::END_POINT_CALCULATOR_SK_TEST;
        }
    }

    /**
     *
     */
    public static function renderForm()
    {
        $module = Module::getInstanceByName(self::MODULE_NAME);
        $currentLanguage = Context::getContext()->language;
        $orderStateManager = new OrderStateManager;

        // Init Fields form array
        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $module->l('Home Credit MyLoan configuration'),
            ],
            'input' => self::generateInputs($orderStateManager, $module, $currentLanguage),
            'submit' => [
                'title' => $module->l('Save'),
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $module->name;

        // Language
        $helper->default_form_language = $currentLanguage->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
          Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        // Title and toolbar
        $helper->title = $module->displayName;
        $helper->show_toolbar = false;
        $helper->toolbar_scroll = false;
        $helper->submit_action = 'submit' . $module->name;

        // Load current value
        $helper->fields_value = self::getConfigArray($orderStateManager);

        return $helper->generateForm($fields_form);
    }

    /**
     * Vyzkou�� p�ipojen� k Myloan
     * @return bool|string
     */
    public static function testHCApiConnection()
    {
        $module = Module::getInstanceByName(self::MODULE_NAME);

        try {
            $clientAPI = new \MyLoan\HomeCredit\AuthAPI();
            if (!$clientAPI->isLogged()) {
                return $module->l('Connection with Home Credit error!');
            }
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                return $module->l('You fill wrong data for login!');
            }

            return str_replace("%ex%", $e->getMessage(), $module->l('Unexpected error: %ex%'));
        }

        return true;
    }

    /**
     * Zkontroluje povinn� pole
     * @param $mlcData
     * @return bool
     */
    public static function requiredFields($mlcData)
    {
        foreach ($mlcData as $config) {
            if ($config == null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Zjist� jestli je modul spr�vn� nastaven
     * @return bool
     */
    public static function isModuleConfigured()
    {
        $mlcData = self::getConfigArray(new OrderStateManager);
        return self::requiredFields($mlcData);
    }

    /**
     * Aktualizuje konfiguraci
     * @param $values
     * @return bool
     */
    private static function updateValues($values)
    {
        foreach ($values as $key => $data) {
            if (!Configuration::updateValue($key, $data)) {
                return false;
            }
        }

        return true;
    }

    private static function generateOrderMapping(OrderStateManager $manager, $data) {
        $isStateUnclassifiedGenerated = self::isOrderStateGenerated(UnclassifiedState::ID);
        foreach ($manager->getStates() as $state) {
            switch ((int) $data[$state->getId()]) {
                case self::OPTION_ID_UNCLASSIFIED:
                    if (!$isStateUnclassifiedGenerated) {
                        $id = self::generateNewOrderState($manager->getState(UnclassifiedState::ID));
                    } else {
                        $id = (int) self::get(UnclassifiedState::ID . "_MAPPING");
                    }
                    self::updateValue($state->getId(), $id);
                    break;
                case self::OPTION_ID_GENERATE:
                    if (!self::isOrderStateGenerated($state->getId())) {
                        $id = self::generateNewOrderState($state);
                    } else {
                        $id = (int) self::get($state->getId() . "_MAPPING");
                    }
                    self::updateValue($state->getId(), $id);
                    break;
                default:
                    self::updateValue($state->getId(), (int)$data[$state->getId()]);
                    break;
            }
            unset($data[$state->getId()]);
        }

        return $data;
    }

    private static function generateNewOrderState(AbstractState $state) {
        $orderState = $state->toOrder();
        $orderState->add();
        $id = (int) $orderState->id;
        self::set($state->getId(), $id);
        self::set($state->getId() . "_GENERATED", true);
        return $id;
    }

    private static function isOrderStateGenerated($id) {
        return self::hasKey($id. "_GENERATED") && (bool) self::get($id);
    }

    /**
     * @param OrderStateManager $manager
     * @param Module            $module
     * @param Language          $language
     * @return array
     */
    private static function generateInputs(OrderStateManager $manager, Module $module, Language $language)
    {
        $inputsToPrepend = [
            [
                'type' => 'select',
                'label' => $module->l('Country'),
                'name' => self::API_COUNTRY,
                'required' => true,
                'options' => [
                    'query' => [
                        ['id' => self::CZ_VERSION, 'name' => $module->l(self::CZ_VERSION)],
                        ['id' => self::SK_VERSION, 'name' => $module->l(self::SK_VERSION)],
                        ['id' => self::CZ_TEST_VERSION, 'name' => $module->l(self::CZ_TEST_VERSION)],
                        ['id' => self::SK_TEST_VERSION, 'name' => $module->l(self::SK_TEST_VERSION)],
                    ],
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $module->l('Username'),
                'name' => self::API_USER,
                'size' => 20,
                'required' => true
            ],
            [
                'type' => 'password',
                'label' => $module->l('Password'),
                'name' => self::API_PASSWORD,
                'size' => 64,
                'required' => true,
            ],
            [
                'type' => 'password',
                'label' => $module->l('Secret code'),
                'name' => self::API_SECRETCODE,
                'size' => 64,
                'required' => true,
            ],
            [
                'type' => 'text',
                'label' => $module->l('Product code'),
                'name' => self::API_PRODUCT_CODE,
                'size' => 20,
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $module->l('Calculator API key'),
                'name' => self::API_CALC_KEY,
                'size' => 20,
                'required' => true
            ]
        ];
        $inputsToAppend = [
            [
                'type' => 'switch',
                'label' => $module->l('Are you certified Home Credit partner?'),
                'name' => self::API_CERTIFIED,
                'required' => true,
                'values' => [
                    [
                        'id' => 'certified_1',
                        'value' => '1',
                        'label' => $module->l('Yes'),
                    ],
                    [
                        'id' => 'certified_0',
                        'value' => '0',
                        'label' => $module->l('No'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $module->l('Inform Home Credit when shipped? (Else when delivered.)', __CLASS__),
                'name' => self::EXPORT_METHOD,
                'required' => true,
                'values' => [
                    [
                        'id' => 'shipped_1',
                        'value' => '1',
                        'label' => $module->l('Yes'),
                    ],
                    [
                        'id' => 'shipped_0',
                        'value' => '0',
                        'label' => $module->l('No'),
                    ],
                ],
            ]
        ];

        $orderStateInputs = [];
        $options = self::generateOrderStateOptions($manager, $module, $language);

        foreach ($manager->getStates() as $state) {
            $orderStateInputs[] = [
                'type' => 'select',
                'label' => $module->l($state->getName($language->iso_code)),
                'name' => $state->getId(),
                'required' => true,
                'options' => [
                    'query' => $options,
                    'id' => 'id',
                    'name' => 'name',
                ],
            ];
        }
        return array_merge($inputsToPrepend, $orderStateInputs, $inputsToAppend);
    }

    private static function generateOrderStateOptions(OrderStateManager $stateManager, Module $module, Language $language)
    {
        $unclassifiedState = $stateManager->getState(UnclassifiedState::ID);
        $options = [
            ['id' => self::OPTION_ID_GENERATE, 'name' => $module->l("Generate new state")],
            ['id' => self::OPTION_ID_UNCLASSIFIED, 'name' => $module->l($unclassifiedState->getName($language->iso_code))]
        ];

        $availableOrderStates = Db::getInstance(_PS_USE_SQL_SLAVE_)->query('
SELECT *
FROM `' . _DB_PREFIX_ . 'order_state_lang` osl 
WHERE osl.`id_lang` = ' . $language->id . '
ORDER BY `name` ASC')
            ->fetchAll(PDO::FETCH_ASSOC);

        $newOptions = array_map(function ($i) use ($module) {
            return ['id' => $i['id_order_state'], 'name' => $module->l($i['name'])];
        }, $availableOrderStates);

        return array_merge($options, $newOptions);
    }
}
