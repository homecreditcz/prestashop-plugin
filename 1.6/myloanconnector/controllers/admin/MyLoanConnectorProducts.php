<?php

class MyLoanConnectorProductsController extends ModuleAdminController
{

    const DISABLED = 0;
    const ENABLED = 1;

    public function __construct()
    {
        global $currentIndex;

        $this->table = "product";
        $this->className = 'Product';
        $this->_defaultOrderBy = false;

        $this->bootstrap = true;
        $this->shopLink = false;
        $this->_pagination = array(5, 10, 20, 50, 100);
        $this->controller_name = "MyLoanConnectorProducts";

        $module = Module::getInstanceByName("myloanconnector");

        // sloupce tabulky
        $this->fields_list = array(
          'id_product' => array(
            'title' => $module->l('ID', $this->controller_name),
            'align' => 'center',
            'width' => 'auto',
            'filter_key' => 'id_product',
          ),
          'name' => array(
            'title' => $module->l('Name', $this->controller_name),
            'width' => 'auto',
            'align' => 'center',
            'filter_key' => 'name',
          ),
          'discount' => array(
            'title' => $module->l('Discount product', $this->controller_name),
            'align' => 'center',
            'search' => false,
            'width' => 'auto',
            'callback' => 'displayDiscount',
          ),
          'referral' => array(
            'title' => $module->l('Referral product', $this->controller_name),
            'align' => 'center',
            'search' => false,
            'width' => 'auto',
            'callback' => 'displayReferral',
          ),
        );

        $this->list_no_link = true;

        $this->_join = '
            LEFT JOIN `'._DB_PREFIX_.'hc_product` h ON (h.`id_product` = a.`id_product`)
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (a.`id_product` = pl.`id_product`  AND pl.id_shop = 1 )
            ';

        $this->_where = 'AND a.`active` = 1 AND pl.`id_lang` = ' . Context::getContext()->language->id;
        $this->_select = 'pl.name as name, IFNULL(h.`discount`, 0) as `discount`, IFNULL(h.`referral`, 0) as `referral`';

        parent::__construct();

    }

    /**
     * Callback funkce pro vygenerování checkboxu slevy v tabulce
     * @param $state int aktuální stav checkboxu
     * @param $data array data produktu
     * @return string
     */
    public function displayDiscount($state, $data){
        return $this->generateCheckbox($data["id_product"], $state, $this->getNewState($state), $data["referral"]);
    }

    /**
     * Callback funkce pro vygenerování checkboxu reference v tabulce
     * @param $state int aktuální stav checkboxu
     * @param $data array data produktu
     * @return string
     */
    public function displayReferral($state, $data){
        return $this->generateCheckbox($data["id_product"], $state, $data["discount"], $this->getNewState($state));
    }

    /**
     * Generuje nový stav po kliknutí na checkbox
     * @param $state int aktuální stav checkboxu
     * @return string
     */
    private static function getNewState($state){
        $newState = self::DISABLED;

        if((int)$state === self::DISABLED) {
            $newState = self::ENABLED;
        }

        return $newState;
    }

    /**
     * Generuje nový chekbox
     * @param $id_product int
     * @param $state int
     * @param $discount int
     * @param $referral int
     * @return string
     */
    public function generateCheckbox($id_product, $state, $discount, $referral){

        $link = self::$currentIndex . '&token=' . $this->token . '&referral='. $referral . '&discount='. $discount .'&id=' . $id_product ;

        $class = (int)$state !== self::DISABLED ? "icon-check action-enabled" : "icon-remove action-disabled";
        return '<a href="'. $link .'"><i class="'.$class.' list-action-enable"></i></a>';
    }

    /**
     * Zpracování zachycených zmìn v tabulce (GET / POST)
     * @return void
     */
    public function postProcess()
    {
        parent::postProcess();

        if(
          Tools::getValue('id', null) !== null &&
          Tools::getValue('discount', null) !== null &&
          Tools::getValue('referral', null) !== null
        )
        {
            $this->updateProductData(Tools::getValue('id'), Tools::getValue('discount'), Tools::getValue('referral'));
        }
    }

    /**
     * Callback funkce pro vygenerování checkboxu slevy v tabulce
     * @param $id_product
     * @param $discount
     * @param $referral
     */
    private function updateProductData($id_product, $discount, $referral){

        // Vložit nový produkt
        try {
            $r = Db::getInstance()->insert('hc_product', array(
              'id_product' => (int)$id_product,
              'discount' => $discount,
              'referral' => $referral,
            ));
            
            if(!$r)
                throw new Exception('Insert failed.');
                
        } catch (Exception $e) {
            // Pokud již existuje, nejde vložit, updatuju
            Db::getInstance()->update('hc_product', array(
              'discount' => $discount,
              'referral' => $referral,
            ), "id_product = $id_product");
        }
    }


}