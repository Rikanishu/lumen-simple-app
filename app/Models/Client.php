<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    protected $table = 'clients';

    public $timestamps = false;

    public function currency()
    {
        return $this->belongsTo('App\Models\Currency', 'currency_id', 'id');
    }

    public function getBalanceAsDecimal()
    {
        return $this->balance;
    }

    public function getBalanceAsFloat()
    {
        return (float) $this->balance;
    }

    public function addMoney($amount)
    {
        DB::table($this->table)
            ->where('id', $this->id)
            ->update([
                'balance' => DB::raw('balance + ' . $amount)
            ]);
    }

    public function subtractMoney($amount)
    {
        DB::table($this->table)
            ->where('id', $this->id)
            ->update([
                'balance' => DB::raw('balance - ' . $amount)
            ]);
    }
}