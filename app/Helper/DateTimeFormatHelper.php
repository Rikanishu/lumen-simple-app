<?php

namespace App\Helper;


class DateTimeFormatHelper
{
    public static function createFromDbDateFormat($string)
    {
        return \DateTime::createFromFormat('Y-m-d', $string);
    }

    public static function createFromDbDateTimeFormat($string)
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $string);
    }

    public static function formatToDbDate(\DateTime $dateTime)
    {
        return $dateTime->format('Y-m-d');
    }

    public static function formatToDbDateTime(\DateTime $dateTime)
    {
        return $dateTime->format('Y-m-d H:i:s');
    }
}