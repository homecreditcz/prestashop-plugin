<?php
/**
 * @author     HN Consulting Brno s.r.o
 * @copyright  2019-*
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 **/

use MyLoan\HomeCredit\EndPointManager;
use MyLoan\HomeCredit\OrderStateManager;
use MyLoan\HomeCredit\OrderStates\AbstractState;
use MyLoan\HomeCredit\OrderStates\ReadyToDeliveredState;
use MyLoan\HomeCredit\OrderStates\ReadyToShippedState;
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
    const API_DISCOUNT_PRODUCT_CODE = self::MODULE_PREFIX . "API_DISCOUNT_PRODUCT_CODE";

    /**
     *
     */

    const DISCOUNT_UTM_STRING = self::MODULE_PREFIX . "DISCOUNT_UTM_STRING";

    const REFERRAL_COOKIE_NAME = "utm_source";
    const REFERRAL_COOKIE_EXPIRE = 60*60*24*7;

    /**
     * Výchozí slevová kategorie
     */
    const WITHOUT_DISCOUNT = 0;

    /**
     *
     */
    const API_CALC_KEY = self::MODULE_PREFIX . "API_CALC_KEY";
    /**
     * Nainstalovaná verze modulu
     */
    const MODULE_VERSION = self::MODULE_PREFIX . "VERSION";

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


    const TABS = array(
      "name" => [
          "en" => "Home Credit",
          "cs" => "Home Credit",
          "sk" => "Home Credit",
      ],
      "class_name" => "MyLoanConnectorTab",
      "subTabs" => array(
        array(
          "name" => [
              "en" => "Products",
              "cs" => "Produkty",
              "sk" => "Produkty",
          ],
          "class_name" => "MyLoanConnectorProducts"
        ),
        array(
          "name" => [
              "en" => "Settings",
              "cs" => "Nastavení",
              "sk" => "Nastavení",
          ],
          "class_name" => "MyLoanConnectorSettings"
        )
      )
    );

    /**
     * Nastavení požadovaných a volitelných položek na stránce s nastavením
     */

    private static function getRequiredFieldsArray(){
        return array(
            self::API_USER => true,
            self::API_PASSWORD => empty(MlcConfig::get(self::API_PASSWORD)),
            self::API_SECRETCODE => empty(MlcConfig::get(self::API_SECRETCODE)),
            self::API_PRODUCT_CODE => true,
            self::API_DISCOUNT_PRODUCT_CODE => false,
            self::DISCOUNT_UTM_STRING => false,
            self::API_CALC_KEY => true,
            self::API_CERTIFIED => true,
            self::API_COUNTRY => true,
            self::EXPORT_METHOD => true
        );
    }

    /**
     * Metoda která naisntaluje vše potřebné
     * @return bool
     */
    public static function install()
    {
        return
          self::installDefault() &&
          self::registerHooks() &&
          self::createDatabaseTables() &&
          self::alterDatabaseTables() &&
          self::installTabs();
    }

    /**
     * Vloží defaultní nastavení
     * @return bool
     */
    public static function installDefault()
    {
        $manager = new OrderStateManager();
        $default_data = self::getConfigArray($manager);

        foreach ($manager->getIdStates(false) as $id) {
            $default_data[$id] = self::OPTION_ID_GENERATE;
        }
        $default_data[self::API_USER] = "";
        $default_data[self::API_PASSWORD] = "";
        $default_data[self::API_SECRETCODE] = "";
        $default_data[self::API_CERTIFIED] = "0";
        $default_data[self::API_PRODUCT_CODE] = "";
        $default_data[self::API_DISCOUNT_PRODUCT_CODE] = "";
        $default_data[self::DISCOUNT_UTM_STRING] = "";
        $default_data[self::API_CALC_KEY] = "calculator_test_key";
        $default_data[self::EXPORT_METHOD] = "0";

        $version = self::CZ_VERSION;
        $manager = EndPointManager::getInstance();
        if (\Tools::strtoupper(\MyLoan\Tools::getTopGenericDomainFromUrl($_SERVER['SERVER_NAME'])) === self::SK_VERSION) {
            $version = self::SK_VERSION;
        }

        $default_data[self::API_COUNTRY] = $version;
        $default_data[self::API_URL] = $manager->getApiUrl($version);
        $default_data[self::API_CALC_URL] = $manager->getApiCalcPublicUrl($version);


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
          $module->registerHook('payment') &&
          $module->registerHook('actionPaymentConfirmation') &&
          $module->registerHook('actionOrderStatusPostUpdate') &&
          $module->registerHook('actionOrderStatusUpdate') &&
          $module->registerHook('actionGetExtraMailTemplateVars') &&
          $module->registerHook('displayAdminOrder') &&
          $module->registerHook('actionAdminOrdersListingResultsModifier') &&
          $module->registerHook('actionAdminOrdersListingFieldsModifier');
    }


    /**
     * Vytvoří databázi
     * @return bool
     */
    public static function createDatabaseTables()
    {
        $res = true;

          $res = \Db::getInstance()->Execute(
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

        $res &= \Db::getInstance()->Execute(
          'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hc_product` (
            `id_product` int(10) UNSIGNED NOT NULL,
            `discount` int(32) DEFAULT 0,
            `referral` int(32) DEFAULT 0,
            PRIMARY KEY (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );

        return $res;
    }


    /** Nastaví povolené typy stavů objednávek podle volby v administraci modulu.
     * @param int $type
     */
    public static function setExpeditionType($type = 1){

        switch ($type){
            case 0:
            case "DELIVERED":
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '1' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get(ReadyToShippedState::ID)."';"
                );
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '0' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get(ReadyToDeliveredState::ID)."';"
                );
                break;

            default:
            case 1:
            case "SHIPPED":
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '0' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get(ReadyToShippedState::ID)."';"
                );
                \Db::getInstance()->Execute(
                  "UPDATE `" . _DB_PREFIX_ . "order_state` SET `deleted` = '1' WHERE `" . _DB_PREFIX_ . "order_state`.`id_order_state` = '"
                  .MlcConfig::get(ReadyToDeliveredState::ID)."';"
                );
                break;
        }

    }

    /**
     * Přidání sloupce do tabulky objednávek
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
     * Uložení konfigurace
     * @return bool
     */
    public static function saveConfig()
    {
        $module = Module::getInstanceByName(self::MODULE_NAME);
        $context = \Context::getContext();
        $checkConnection = false;

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
          self::API_DISCOUNT_PRODUCT_CODE => "isString",
          self::DISCOUNT_UTM_STRING => "isString",
          self::API_CALC_KEY => "isString",
          self::EXPORT_METHOD => "isBool"
        ];

        $manager = new OrderStateManager();
        foreach ($manager->getIdStates(false) as $id) {
            $mlcValidate[$id] = "isString";
        }

        $mlcData = \MyLoan\Validate::getDataAndValidate($mlcValidate);
        $ApiUserChanged = $mlcData[self::API_USER] !== MlcConfig::get(self::API_USER);
        $credentialsNotProvided = empty($mlcData[self::API_PASSWORD]) || empty($mlcData[self::API_SECRETCODE]);

        if (!$mlcData) {
            return $module->displayError($module->l('Please make sure you filled all fields in correct format.', __CLASS__));
        }

        if ($ApiUserChanged && $credentialsNotProvided){
            return $module->displayError($module->l('Home Credit password and secret key are required when username is changed.', __CLASS__));
        }

        $mlcData = self::generateOrderMapping($manager, $mlcData);

        // Pokud bylo změněno jméno uživatele API
        if($ApiUserChanged){
            $checkConnection = true;
        }

        // Pokud už je heslo nastaveno a nebylo zadáno nové, nechat
        if(self::getRequiredFieldsArray()[self::API_PASSWORD] === false && empty($mlcData[self::API_PASSWORD])){
            unset($mlcData[self::API_PASSWORD]);
        } else {
            $checkConnection = true;
        }

        // Pokud už je tajný kód nastaven a nebyl zadán nový, nechat
        if(self::getRequiredFieldsArray()[self::API_SECRETCODE] === false && empty($mlcData[self::API_SECRETCODE])) {
            unset($mlcData[self::API_SECRETCODE]);
        } else {
            $checkConnection = true;
        }

        if (!self::requiredFields($mlcData)) {
            return $module->displayError($module->l('Please fill in all the required fields.', __CLASS__));
        }

        MlcConfig::setExpeditionType($mlcData[self::EXPORT_METHOD]);

        $endPointManager = EndPointManager::getInstance();
        $mlcData[self::API_URL] = $endPointManager->getApiUrl($mlcData[self::API_COUNTRY]);
        $mlcData[self::API_CALC_URL] = $mlcData[self::API_CERTIFIED] ?
            $endPointManager->getApiCalcCertifiedUrl($mlcData[self::API_COUNTRY]) :
            $endPointManager->getApiCalcPublicUrl($mlcData[self::API_COUNTRY]);

        if (!self::updateValues($mlcData)) {
            return $module->displayError($module->l('An error occurred while updating your configuration. Please try again.', __CLASS__));
        }

        // Ověřit spojení s HC API
        if($checkConnection === true) {

            if (($testApi = self::testHCApiConnection()) !== true) {
                return $module->displayError($testApi);
            } else {
                return $module->displayConfirmation($module->l('Connection to Home Credit MyLoan API was successful!', __CLASS__));
            }
        }

        return $module->displayConfirmation($module->l('Settings was successfully saved!', __CLASS__));
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

        self::uninstallTabs();

        return true;
    }

    /**
     * Získání nastavení
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
          self::API_DISCOUNT_PRODUCT_CODE => self::get(self::API_DISCOUNT_PRODUCT_CODE),
          self::DISCOUNT_UTM_STRING => self::get(self::DISCOUNT_UTM_STRING),
          self::API_CALC_KEY => self::get(self::API_CALC_KEY),
          self::EXPORT_METHOD => self::get(self::EXPORT_METHOD)
        ];

        if ($manager !== null) {
            foreach ($manager->getIdStates(false) as $idState) {
                $config[$idState] = self::getMappingOrderState($idState);
            }
        }
        return $config;
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
            'title' => $module->l('Home Credit MyLoan configuration', __CLASS__),
          ],
          'input' => self::generateInputs($orderStateManager, $module, $currentLanguage),
          'submit' => [
            'title' => $module->l('Save', __CLASS__),
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
     * Vyzkouší připojení k Myloan
     * @return bool|string
     */
    public static function testHCApiConnection()
    {
        $module = Module::getInstanceByName(self::MODULE_NAME);

        try {
            $clientAPI = new \MyLoan\HomeCredit\AuthAPI();
            if (!$clientAPI->isLogged()) {
                return $module->l('Connection with Home Credit error!', __CLASS__);
            }
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                return $module->l('You fill wrong data for login!', __CLASS__);
            }

            return str_replace("%ex%", $e->getMessage(), $module->l('Unexpected error: %ex%', __CLASS__));
        }

        return true;
    }

    /**
     * Zkontroluje povinná pole
     * @param $mlcData
     * @return bool
     */
    public static function requiredFields($mlcData)
    {
        foreach ($mlcData as $key => $value) {
            if ($value == null && self::getRequiredFieldsArray()[$key]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Zjistí jestli je modul správně nastaven
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

    private static function generateOrderMapping(OrderStateManager $manager, $data)
    {
        $isStateUnclassifiedGenerated = self::isOrderStateGenerated(UnclassifiedState::ID);
        foreach ($manager->getStates(false) as $state) {
            switch ((int)$data[$state->getId()]) {
                case self::OPTION_ID_UNCLASSIFIED:
                    if (!$isStateUnclassifiedGenerated) {
                        $id = self::generateNewOrderState($manager->getState(UnclassifiedState::ID));
                        $isStateUnclassifiedGenerated = true;
                    } else {
                        $id = self::getMappingOrderState(UnclassifiedState::ID);
                    }
                    self::setMappingOrderState($state->getId(), $id);
                    break;
                case self::OPTION_ID_GENERATE:
                    if (!self::isOrderStateGenerated($state->getId())) {
                        $id = self::generateNewOrderState($state);
                    } else {
                        $id = self::getMappingOrderState($state->getId());
                    }
                    self::setMappingOrderState($state->getId(), $id);
                    break;
                default:
                    self::setMappingOrderState($state->getId(), (int)$data[$state->getId()]);
                    break;
            }
            unset($data[$state->getId()]);
        }

        return $data;
    }

    public static function generateNewOrderState(AbstractState $state)
    {
        $orderState = $state->toOrder();
        $orderState->add();
        $id = (int)$orderState->id;
        self::updateValue($state->getId(), $id);
        self::updateValue(self::getIdOfOrderStateGenerated($state->getId()), 1);
        return $id;
    }

    public static function getIdOfOrderStateGenerated($id)
    {
        return $id . '_GENERATED';
    }

    public static function getIdOfOrderStateMapping($id)
    {
        return $id . '_MAPPING';
    }

    public static function getMappingOrderState($id)
    {
        return (int)self::get(self::getIdOfOrderStateMapping($id));
    }

    private static function setMappingOrderState($id, $value)
    {
        self::updateValue(self::getIdOfOrderStateMapping($id), $value);
    }

    public static function isOrderStateGenerated($id)
    {
        return self::hasKey(self::getIdOfOrderStateGenerated($id)) && (bool)self::get($id);
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
            'label' => $module->l('Country', __CLASS__),
            'name' => self::API_COUNTRY,
            'required' => self::getRequiredFieldsArray()[self::API_COUNTRY],
            'options' => [
              'query' => array_map(function($id) use($module) {
                  return ['id' => $id, 'name' => $module->l($id, __CLASS__)];
              }, EndPointManager::getInstance()->getVersionList()),
              'id' => 'id',
              'name' => 'name',
            ],
          ],
          [
            'type' => 'text',
            'label' => $module->l('Username', __CLASS__),
            'name' => self::API_USER,
            'size' => 20,
            'required' => self::getRequiredFieldsArray()[self::API_USER],
          ],
          [
            'type' => 'text',
            'label' => $module->l('Password', __CLASS__),
            'name' => self::API_PASSWORD,
            'size' => 64,
            'required' => self::getRequiredFieldsArray()[self::API_PASSWORD],
          ],
          [
            'type' => 'text',
            'label' => $module->l('Secret code', __CLASS__),
            'name' => self::API_SECRETCODE,
            'size' => 64,
            'required' => self::getRequiredFieldsArray()[self::API_SECRETCODE],
          ],
          [
            'type' => 'text',
            'label' => $module->l('Product code', __CLASS__),
            'name' => self::API_PRODUCT_CODE,
            'size' => 20,
            'required' => self::getRequiredFieldsArray()[self::API_PRODUCT_CODE],
          ],
          [
            'type' => 'text',
            'label' => $module->l('Discount product code', __CLASS__),
            'name' => self::API_DISCOUNT_PRODUCT_CODE,
            'size' => 20,
            'required' => self::getRequiredFieldsArray()[self::API_DISCOUNT_PRODUCT_CODE],
          ],
          [
            'type' => 'text',
            'label' => $module->l('Discount utm source string', __CLASS__),
            'name' => self::DISCOUNT_UTM_STRING,
            'size' => 20,
            'required' => self::getRequiredFieldsArray()[self::DISCOUNT_UTM_STRING],
          ],
          [
            'type' => 'text',
            'label' => $module->l('Calculator API key', __CLASS__),
            'name' => self::API_CALC_KEY,
            'size' => 20,
            'required' => self::getRequiredFieldsArray()[self::API_CALC_KEY],
          ]
        ];
        $inputsToAppend = [
          [
            'type' => 'switch',
            'label' => $module->l('Are you certified Home Credit partner?', __CLASS__),
            'name' => self::API_CERTIFIED,
            'required' => self::getRequiredFieldsArray()[self::API_CERTIFIED],
            'values' => [
              [
                'id' => 'certified_1',
                'value' => '1',
                'label' => $module->l('Yes', __CLASS__),
              ],
              [
                'id' => 'certified_0',
                'value' => '0',
                'label' => $module->l('No', __CLASS__),
              ],
            ],
          ],
          [
            'type' => 'switch',
            'label' => $module->l('Inform Home Credit when shipped? (Else when delivered.)', __CLASS__),
            'name' => self::EXPORT_METHOD,
            'required' => self::getRequiredFieldsArray()[self::EXPORT_METHOD],
            'values' => [
              [
                'id' => 'shipped_1',
                'value' => '1',
                'label' => $module->l('Yes', __CLASS__),
              ],
              [
                'id' => 'shipped_0',
                'value' => '0',
                'label' => $module->l('No', __CLASS__),
              ],
            ],
          ]
        ];

        $orderStateInputs = [];
        $options = self::generateOrderStateOptions($manager, $module, $language);

        foreach ($manager->getStates(false) as $state) {
            $orderStateInputs[] = [
              'type' => 'select',
              'label' => $module->l($state->getName($language->iso_code), __CLASS__),
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
          ['id' => self::OPTION_ID_GENERATE, 'name' => $module->l("Generate new state", __CLASS__)],
          ['id' => self::OPTION_ID_UNCLASSIFIED, 'name' => $module->l($unclassifiedState->getName($language->iso_code), __CLASS__)]
        ];

        $availableOrderStates = Db::getInstance(_PS_USE_SQL_SLAVE_)->query('
SELECT *
FROM `' . _DB_PREFIX_ . 'order_state_lang` osl 
WHERE osl.`id_lang` = ' . $language->id . '
ORDER BY `name` ASC')
          ->fetchAll(PDO::FETCH_ASSOC);

        $newOptions = array_map(function ($i) use ($module) {
            return ['id' => $i['id_order_state'], 'name' => $module->l($i['name'], __CLASS__)];
        }, $availableOrderStates);

        return array_merge($options, $newOptions);
    }


    /**
     * Aktivuje položky menu při povolení pluginu
     * @param bool $force_all
     * @return bool
     */
    public function enable($force_all = false)
    {
        return
          $this->installTabs() &&
          parent::enable($force_all)
          ;
    }

    /**
     * Deaktivuje položky menu při zakázání pluginu
     * @param bool $force_all
     * @return bool
     */
    public function disable($force_all = false)
    {
        return
          $this->uninstallTabs() &&
          parent::disable($force_all)
          ;
    }

    /**
     * Instaluje nové položky menu
     * @param $class_name
     * @param $id_parent
     * @param $name
     * @return bool|int
     */
    private static function installTab($class_name, $id_parent, $name){

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $class_name;

        $tab->name = array();

        $module = Module::getInstanceByName(self::MODULE_NAME);

        foreach (Language::getIsoIds(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name[$lang['iso_code']];
        }

        $tab->id_parent = $id_parent;
        $tab->module = self::MODULE_NAME;
        if(! $tab->add()){
            return false;
        }

        return $tab->id;

    }

    /**
     * Instaluje všechny položky menu
     * @return bool
     */
    public static function installTabs()
    {
        $res = true;
        $parent = self::TABS;

        $id = self::installTab(
          $parent['class_name'],
          (int) Tab::getIdFromClassName('IMPROVE'),
          $parent["name"]
        );

        foreach ($parent["subTabs"] as $subTab) {
            $res &= self::installTab($subTab["class_name"], $id, $subTab["name"] ) !== false;
        }

        return $res;

    }

    /**
     * Odinstaluje položku menu
     * @return bool
     */
    private static function uninstallTab($tab_name){

        $tabId = (int) Tab::getIdFromClassName($tab_name);
        if (!$tabId) {
            return true;
        }

        $tab = new Tab($tabId);
        return $tab->delete();

    }

    /**
     * Odinstaluje všechny položky menu
     * @return bool
     */
    public static function uninstallTabs()
    {
        $res = true;

        $parent = self::TABS;

        $res &= self::uninstallTab($parent["class_name"]);
        foreach ($parent["subTabs"] as $subTab) {
            $res &= self::uninstallTab($subTab["class_name"]);
        }

        return $res;
    }


}