<?php

class MyLoanConnectorTabController extends ModuleAdminController
{

    public function __construct() {

        $link = new Link();
        // P�esm�ruje u�ivatele na controller s produkty
        Tools::redirectAdmin($link->getAdminLink('MyLoanConnectorProducts'));

    }


}