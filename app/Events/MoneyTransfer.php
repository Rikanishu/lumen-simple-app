<?php

namespace App\Events;

class MoneyTransfer extends Event
{
    protected $sender;
    protected $receiver;
    protected $amountSubtracted;
    protected $amountAdded;

    public function __construct($sender, $receiver, $amountSubtracted, $amountAdded)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->amountAdded = $amountAdded;
        $this->amountSubtracted = $amountSubtracted;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return mixed
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @return mixed
     */
    public function getAmountSubtracted()
    {
        return $this->amountSubtracted;
    }

    /**
     * @return mixed
     */
    public function getAmountAdded()
    {
        return $this->amountAdded;
    }


}