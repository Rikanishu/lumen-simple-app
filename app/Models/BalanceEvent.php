<?php

namespace App\Models;

use App\Helper\DateTimeFormatHelper;
use Illuminate\Database\Eloquent\Model;

class BalanceEvent extends Model
{
    const TYPE_ADDING = 1;
    const TYPE_TRANSFER_ADDING = 2;
    const TYPE_TRANSFER_SUBTRACT = 3;

    protected $table = 'events';

    public $timestamps = false;

    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'client_id', 'id');
    }

    public function participant()
    {
        return $this->belongsTo('App\Models\Client', 'participant_client_id', 'id');
    }

    public function getAmountAsDecimal()
    {
        return $this->amount;
    }

    public function getAmountAsFloat()
    {
        return (float) $this->amount;
    }


    public function getEventDescription()
    {
        if ($this->event_type == static::TYPE_ADDING) {
            return 'Added';
        }
        if ($this->event_type == static::TYPE_TRANSFER_ADDING) {
            return 'Received from ' . ($this->participant ? $this->participant->name : 'Unknown client');
        }
        if ($this->event_type == static::TYPE_TRANSFER_SUBTRACT) {
            return 'Sent to ' . ($this->participant ? $this->participant->name : 'Unknown client');
        }

        return 'Unknown action';
    }

    public function isPositive()
    {
        return ($this->event_type == static::TYPE_ADDING || $this->event_type == static::TYPE_TRANSFER_ADDING);
    }

    public function getAmountInUsd()
    {
        $date = DateTimeFormatHelper::createFromDbDateTimeFormat($this->date);
        $rate = $this->client->currency->getUsdRateForDate($date);
        if ($rate) {
            $amount = (float) $this->amount;
            return ($amount * $rate);
        }

        return null;
    }
}