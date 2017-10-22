<?php

namespace Api;

use TestCase;

class LoadCurrencyRatesTest extends TestCase
{
    public function testWithoutRequiredData()
    {
        $dateTime = new \DateTime();
        $dateTime = $dateTime->format('Y-m-d');
        $this->json('POST', '/api/load-currency-rates', [
            'date' => $dateTime,
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors(['currencies']);
    }

    public function testInvalidDataFormat()
    {
        $this->json('POST', '/api/load-currency-rates', [
            'date' => 'ZZ00123DDEF32',
            'currencies' => [
                'ABC' => 50
            ]
        ]);

        $this->seeStatusCode(500);
        $this->seeJsonHasException('Invalid date format');
    }

    public function testInvalidCurrencyCode()
    {
        $dateTime = new \DateTime();
        $dateTime = $dateTime->format('Y-m-d');
        $this->json('POST', '/api/load-currency-rates', [
            'date' => $dateTime,
            'currencies' => [
                'ABCD' => 50
            ]
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors(['currencies']);
    }

    public function testZeroRate()
    {
        $dateTime = new \DateTime();
        $dateTime = $dateTime->format('Y-m-d');
        $this->json('POST', '/api/load-currency-rates', [
            'date' => $dateTime,
            'currencies' => [
                'ABC' => 0
            ]
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors(['currencies']);
    }

    public function testSuccessfullyLoadedRates()
    {
        $dateTime = new \DateTime();
        $dateTime = $dateTime->format('Y-m-d');
        $this->json('POST', '/api/load-currency-rates', [
            'date' => $dateTime,
            'currencies' => [
                'RUB' => 0.0175438,
                'GBP' => 1.2,
                'EUR' => 1.184324114,
            ]
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();
    }

    protected function _loadCurrencyRates()
    {
        $dateTime = new \DateTime();
        $dateTime = $dateTime->format('Y-m-d');
        $this->json('POST', '/api/load-currency-rates', [
            'date' => $dateTime,
            'currencies' => [
                'RUB' => 0.0175438,
                'GBP' => 1.2
            ]
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();
    }
}
