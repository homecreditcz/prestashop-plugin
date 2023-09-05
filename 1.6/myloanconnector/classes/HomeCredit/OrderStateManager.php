<?php

namespace MyLoan\HomeCredit;

use Language;
use MyLoan\HomeCredit\OrderStates\AbstractState;
use MyLoan\HomeCredit\OrderStates\ProcessingState;
use MyLoan\HomeCredit\OrderStates\ReadyPaidState;
use MyLoan\HomeCredit\OrderStates\ReadyState;
use MyLoan\HomeCredit\OrderStates\ReadyToDeliveredState;
use MyLoan\HomeCredit\OrderStates\ReadyToDeliveringState;
use MyLoan\HomeCredit\OrderStates\ReadyToShippedState;
use MyLoan\HomeCredit\OrderStates\ReadyToShipState;
use MyLoan\HomeCredit\OrderStates\RejectedState;
use MyLoan\HomeCredit\OrderStates\UnclassifiedState;
use Tools;

class OrderStateManager
{
    /** @var AbstractState[] */
    private $states = [];
    private $isoLangIdPairs = [];

    public function __construct()
    {
        $this->generateIsoLangPairs();

        $this->addState(new ProcessingState);
        $this->addState(new ReadyPaidState);
        $this->addState(new ReadyState);
        $this->addState(new ReadyToDeliveredState);
        $this->addState(new ReadyToDeliveringState);
        $this->addState(new ReadyToShippedState);
        $this->addState(new ReadyToShipState);
        $this->addState(new RejectedState);
        $this->addState(new UnclassifiedState);
    }

    public function getIdStates($withUnclassified = true)
    {
        return array_keys($this->getStates($withUnclassified));
    }

    /**
     * @param $id
     * @return AbstractState|null
     */
    public function getState($id)
    {
        if (array_key_exists($id, $this->states))
            return $this->states[$id];
        else return null;
    }

    /**
     * @param bool $withUnclassified
     * @return AbstractState[]
     */
    public function getStates($withUnclassified = true)
    {
        $states = $this->states;
        if (!$withUnclassified) {
            $states = array_filter($states, function ($id) {
                return $id !== UnclassifiedState::ID;
            }, ARRAY_FILTER_USE_KEY);
        }
        return $states;
    }

    private function addState(AbstractState $state)
    {
        $this->states[$state->getId()] = $state;
        $state->setIsoLangPairs($this->isoLangIdPairs);
    }

    private function generateIsoLangPairs()
    {
        foreach (Language::getLanguages() as $language) {
            $this->isoLangIdPairs[Tools::strtolower($language["iso_code"])] = $language["id_lang"];
        }
    }
}