<?php

namespace Api;

use TestCase;

class AddingMoneyTest extends TestCase
{
    public function testNonNumeric()
    {
        $clientId = $this->_createClient();
        $this->json('POST', '/api/add-money/' . $clientId, [
            'amount' => 'X123'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors(['amount']);
    }

    public function testUnknownClient()
    {
        $this->json('POST', '/api/add-money/' . 999999999, [
            'amount' => '4000'
        ]);

        $this->seeStatusCode(404);
    }

    public function testSuccessfullyAddMoney()
    {
        $clientId = $this->_createClient();
        $this->json('POST', '/api/add-money/' . $clientId, [
            'amount' => '900.00'
        ]);
        $this->seeStatusCode(200);
        $json = $this->getResponseJson();
        $this->assertTrue($json['balance'] == '900', 'Balance doesn\'t equal 900');
        $this->json('POST', '/api/add-money/' . $clientId, [
            'amount' => '900.00'
        ]);
        $this->seeStatusCode(200);
        $json = $this->getResponseJson();
        $this->assertTrue($json['balance'] == '1800', 'Balance doesn\'t equal 1800');
    }

    protected function _createClient()
    {
        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client 4',
            'city' => 'New York',
            'country' => 'USA',
            'currency' => 'USD'
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $clientId = $this->getResponseJson()['client_id'];

        return $clientId;
    }
}
