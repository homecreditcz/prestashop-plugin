<?php

class MyLoanConnectorTabController extends ModuleAdminController
{

    public function __construct() {

        $link = new Link();
        // Pøesmìruje uživatele na controller s produkty
        Tools::redirectAdmin($link->getAdminLink('MyLoanConnectorProducts'));

    }


}