<?php

class MyLoanConnectorSettingsController extends ModuleAdminController
{

    public function __construct() {

        // Přesměruje uživatele na konfiguraci
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminModules').'&configure=myloanconnector');

    }


}