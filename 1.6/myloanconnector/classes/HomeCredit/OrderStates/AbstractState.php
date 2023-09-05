<?php

namespace MyLoan\HomeCredit\OrderStates;

use OrderState;
use Tools;

class AbstractState
{
    private $id = "";
    private $names = [];
    private $origNames = [];
    private $color = "#000000";
    private $hidden = false;
    private $delivery = false;
    private $logAble = false;
    private $invoice = false;
    private $send_email = false;
    private $templates = [];
    private $origTemplates = [];
    private $isoLangPairs = [];

    /**
     * AbstractState constructor.
     *
     * @param string $id
     * @param array  $names
     * @param string $color
     * @param bool   $hidden
     * @param bool   $delivery
     * @param bool   $loggable
     * @param bool   $invoice
     * @param bool   $send_email
     * @param array  $templates
     */
    public function __construct($id, array $names, $color, $hidden, $delivery, $loggable, $invoice, $send_email, array $templates)
    {
        $this->id = Tools::strtoupper($id);
        $this->color = $color;
        $this->hidden = $hidden;
        $this->delivery = $delivery;
        $this->logAble = $loggable;
        $this->invoice = $invoice;
        $this->send_email = $send_email;

        $this->setNames($names);
        $this->setTemplates($templates);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName($isoLang, $default = 'en') {
        $isoLang = Tools::strtolower($isoLang);
        if (array_key_exists($isoLang, $this->origNames)) {
            return $this->origNames[$isoLang];
        } else if (array_key_exists($default, $this->origNames)) {
            return $this->origNames[$default];
        } else return "";
    }

    /**
     * @return OrderState
     */
    public function toOrder()
    {
        $this->validateValuesByLang();

        $order = new OrderState();

        $order->name = $this->names;
        $order->color = $this->color;
        $order->hidden = $this->hidden;
        $order->delivery = $this->delivery;
        $order->logable = $this->logAble;
        $order->invoice = $this->invoice;
        $order->send_email = $this->send_email;
        $order->template = $this->templates;
        $order->module_name = $this->id;

        return $order;
    }

    public function validateValuesByLang()
    {
        foreach($this->isoLangPairs as $isoCode => $id) {
            $isoCode = Tools::strtolower($isoCode);
            if (array_key_exists($isoCode, $this->origNames)) {
                $this->names[$id] = $this->origNames[$isoCode];
            }
            if (array_key_exists($isoCode, $this->origTemplates)) {
                $this->origTemplates[$id] = $this->origTemplates[$isoCode];
            }
        }
    }

    public function setIsoLangPairs(array $pairs) {
        $this->isoLangPairs = $pairs;
    }

    private function setNames(array $names = [])
    {
        foreach ($names as $isoCode => $value) {
            $code = Tools::strtolower($isoCode);
            $this->origNames[$code] = $value;
        }
    }

    private function setTemplates(array $languages = [])
    {
        foreach ($languages as $isoCode => $value) {
            $code = Tools::strtolower($isoCode);
            $this->origTemplates[$code] = $value;
        }
    }
}