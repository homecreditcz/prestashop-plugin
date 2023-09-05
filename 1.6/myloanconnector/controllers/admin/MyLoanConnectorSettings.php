<?php

class MyLoanConnectorSettingsController extends ModuleAdminController
{

    public function __construct() {

        // Pøesmìruje uživatele na konfiguraci
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminModules').'&configure=myloanconnector');

    }


}