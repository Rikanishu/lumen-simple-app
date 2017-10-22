<?php

namespace Api;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use TestCase;

class CreateClientTest extends TestCase
{
    public function testEmptyData()
    {
        $this->json('POST', '/api/create-client', [
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors([
            'name', 'country', 'city', 'currency'
        ]);
    }

    public function testNotFullData()
    {
        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client',
            'city' => 'Moscow',
            'currency' => 'EUR'
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors([
            'country',
        ]);
    }

    public function testCurrencyLimit()
    {
        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client',
            'city' => 'Moscow',
            'country' => 'Russia',
            'currency' => 'EURO'
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors([
            'currency',
        ]);
    }

    public function testSuccessfullyCreate()
    {
        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client',
            'city' => 'Madrid',
            'country' => 'Spain',
            'currency' => 'EUR'
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();
    }
}
