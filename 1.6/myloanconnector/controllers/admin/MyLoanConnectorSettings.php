<?php

class MyLoanConnectorSettingsController extends ModuleAdminController
{

    public function __construct() {

        // P�esm�ruje u�ivatele na konfiguraci
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminModules').'&configure=myloanconnector');

    }


}