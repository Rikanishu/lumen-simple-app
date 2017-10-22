<?php

namespace App\Events;

class MoneyAdding extends Event
{
    protected $client;
    protected $amount;

    public function __construct($client, $amount)
    {
        $this->client = $client;
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

}
