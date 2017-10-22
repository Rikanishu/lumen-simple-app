<?php

namespace App\Models;

use App\Helper\DateTimeFormatHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Currency extends Model
{
    const CURRENCY_CODE_USD = 'USD';

    protected $table = 'currencies';

    public $timestamps = false;

    public function getUsdRateForDate(\DateTime $dateTime)
    {
        if (strtoupper($this->code) === static::CURRENCY_CODE_USD) {
            return 1;
        }

        $usdRate = DB::table('rates_on_dates')
            ->where('currency_id', $this->id)
            ->where('date', DateTimeFormatHelper::formatToDbDate($dateTime))
            ->value('usd_rate');

        if ($usdRate === null) {
            return null;
        }

        return (float) $usdRate;
    }

    public function updateUsdRateForDate(\DateTime $dateTime, $usdRate)
    {
        $dbDate = DateTimeFormatHelper::formatToDbDate($dateTime);
        $count = DB::table('rates_on_dates')
            ->where('currency_id', $this->id)
            ->where('date', $dbDate)
            ->count();
        if ($count > 0) {
            DB::table('rates_on_dates')
                ->where('currency_id', $this->id)
                ->where('date', $dbDate)
                ->update([
                   'usd_rate' => $usdRate
                ]);
        } else {
            DB::table('rates_on_dates')
                ->insert([
                    'currency_id' => $this->id,
                    'date' => $dbDate,
                    'usd_rate' => $usdRate
                ]);
        }
    }
}