<?php

namespace Api;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use TestCase;

class TransferTest extends TestCase
{

    public function testUnknownSender()
    {
        $this->json('POST', '/api/transfer-money', [
            'senderId' => 99324232,
            'receiverId' => 1,
            'amount' => '100.55'
        ]);
        $this->seeStatusCode(404);
    }

    public function testUnknownReceiver()
    {
        $this->json('POST', '/api/transfer-money', [
            'senderId' => 1,
            'receiverId' => 43423423,
            'amount' => '100.55'
        ]);
        $this->seeStatusCode(404);
    }

    public function testHasNoAmount()
    {
        $this->json('POST', '/api/transfer-money', [
            'senderId' => 1,
            'receiverId' => 2,
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasValidationErrors(['amount']);
    }

    public function testTransferWithoutRates()
    {
        list($client1, $client2) = $this->_createTwoClientsSet();

        $this->json('POST', '/api/transfer-money', [
            'senderId' => $client1,
            'receiverId' => $client2,
            'amount' => '100.55'
        ]);

        $this->seeStatusCode(500);
        $this->seeJsonHasExceptionSubstr('USD conversion rate is not defined for currency');
    }

    public function testTransferWithRatesButWithoutBalanceEnough()
    {
        list($client1, $client2) = $this->_createTwoClientsSet();
        $this->_loadCurrencyRates();

        $this->json('POST', '/api/transfer-money', [
            'senderId' => $client1,
            'receiverId' => $client2,
            'amount' => '100.55'
        ]);

        $this->seeStatusCode(500);
        $this->seeJsonHasException('Sender has no enough money to transfer');
    }

    public function testStillHasNoEnoughMoney()
    {
        list($client1, $client2) = $this->_createTwoClientsSet();
        $this->_loadCurrencyRates();

        $this->json('POST', '/api/add-money/' . $client1, [
            'amount' => 1000
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $this->json('POST', '/api/transfer-money', [
            'senderId' => $client1,
            'receiverId' => $client2,
            'amount' => '10000000.00'
        ]);

        $this->seeStatusCode(500);
        $this->seeJsonHasException('Sender has no enough money to transfer');
    }

    public function testUsdTransfer()
    {
        list($client1, $client2) = $this->_createTwoUsdClients();
        $this->json('POST', '/api/add-money/' . $client1, [
            'amount' => 100
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $this->json('POST', '/api/transfer-money', [
            'senderId' => $client1,
            'receiverId' => $client2,
            'amount' => '45.00'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();
        $json = $this->getResponseJson();
        $this->assertTrue($json['sender']['balance'] == '55', 'Sender\'s balance is not equal 55: ' . $json['sender']['balance']);
        $this->assertTrue($json['receiver']['balance'] == '45', 'Receiver\'s balance is not equal 45: ' . $json['receiver']['balance']);
    }

    public function testOneCurrencyTransfer()
    {
        list($client1, $client2) = $this->_createTwoEurClients();
        $this->json('POST', '/api/add-money/' . $client1, [
            'amount' => 100
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $this->json('POST', '/api/transfer-money', [
            'senderId' => $client1,
            'receiverId' => $client2,
            'amount' => '10.00',
            'receiverCurrency' => true // should not affect the result
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();
        $json = $this->getResponseJson();
        $this->assertTrue($json['sender']['balance'] == '90', 'Sender\'s balance is not equal 90: ' . $json['sender']['balance']);
        $this->assertTrue($json['receiver']['balance'] == '10', 'Receiver\'s balance is not equal 10: ' . $json['receiver']['balance']);
    }

    public function testSuccessTransferDifferentCurrencies()
    {
        list($client1, $client2) = $this->_createTwoClientsSet();
        $this->_loadCurrencyRates();

        $this->json('POST', '/api/add-money/' . $client1, [
            'amount' => 1000
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $this->json('POST', '/api/transfer-money', [
            'senderId' => $client1,
            'receiverId' => $client2,
            'amount' => '999.00'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();
        $json = $this->getResponseJson();
        $this->assertTrue($json['sender']['balance'] == '1', 'Sender\'s balance is not equal 1: ' . $json['sender']['balance']);
        $this->assertTrue($json['receiver']['balance'] == '14.6052135', 'Receiver\'s balance is not equal 14.6052135: ' . $json['receiver']['balance']);
    }

    public function testSuccessTransferReceiverCurrency()
    {
        list($client1, $client2) = $this->_createTwoClientsSet();
        $this->_loadCurrencyRates();

        $this->json('POST', '/api/add-money/' . $client1, [
            'amount' => 1000
        ]);
        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $this->json('POST', '/api/transfer-money', [
            'senderId' => $client1,
            'receiverId' => $client2,
            'amount' => '1.50',
            'receiverCurrency' => true
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();
        $json = $this->getResponseJson();
        $this->assertTrue($json['sender']['balance'] == '897.3996511588', 'Sender\'s balance is not equal 897.3996511588: ' . $json['sender']['balance']);
        $this->assertTrue($json['receiver']['balance'] == '1.5', 'Receiver\'s balance is not equal 1.5: ' . $json['receiver']['balance']);
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

    protected function _createTwoClientsSet()
    {
        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client 1',
            'city' => 'Moscow',
            'country' => 'Russia',
            'currency' => 'RUB'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $client1 = $this->getResponseJson()['client_id'];

        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client 2',
            'city' => 'London',
            'country' => 'UK',
            'currency' => 'GBP'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $client2 = $this->getResponseJson()['client_id'];

        return [$client1, $client2];
    }

    protected function _createTwoUsdClients()
    {
        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client 5',
            'city' => 'Washington',
            'country' => 'USA',
            'currency' => 'USD'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $client1 = $this->getResponseJson()['client_id'];

        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client 6',
            'city' => 'New York',
            'country' => 'USA',
            'currency' => 'USD'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $client2 = $this->getResponseJson()['client_id'];

        return [$client1, $client2];
    }

    protected function _createTwoEurClients()
    {
        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client 7',
            'city' => 'Madrid',
            'country' => 'Spain',
            'currency' => 'EUR'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $client1 = $this->getResponseJson()['client_id'];

        $this->json('POST', '/api/create-client', [
            'name' => 'Test Client 8',
            'city' => 'Berlin',
            'country' => 'Germany',
            'currency' => 'EUR'
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonHasSuccessResponse();

        $client2 = $this->getResponseJson()['client_id'];

        return [$client1, $client2];
    }
}
