<?php

namespace App\Http\Controllers;

use App\Events\MoneyAdding;
use App\Events\MoneyTransfer;
use App\Helper\DateTimeFormatHelper;
use App\Models\Client;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    public function create(Request $request, $id = null)
    {

        $jsonData = $request->json()->all();
        $validator = Validator::make($jsonData, [
            'name' => 'required|max:1024',
            'city' => 'required|max:512',
            'country' => 'required|max:512',
            'currency' => 'required|max:3|min:3'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $client = new Client();
        $client->name = $jsonData['name'];
        $client->city = $jsonData['city'];
        $client->country = $jsonData['country'];

        $currency = Currency::where('code', $jsonData['currency'])->first();
        if (!$currency) {
            $currency = new Currency();
            $currency->code = $jsonData['currency'];
            $currency->save();
        }

        $client->currency_id = $currency->id;
        $client->balance = 0;
        $client->save();

        return [
            'success' => true,
            'client_id' => $client->id
        ];
    }

    public function addMoney($clientId, Request $request)
    {
        $jsonData = $request->json()->all();
        $validator = Validator::make($jsonData, [
            'amount' => 'required|numeric|more_than_zero'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $money = (float) $jsonData['amount'];

        $client = Client::find($clientId);
        if (!$client) {
            abort(404);
        }

        $client->addMoney($money);
        $client->refresh();

        event(new MoneyAdding($client, $money));

        return [
            'success' => true,
            'balance' => $client->getBalanceAsFloat()
        ];
    }

    public function transferMoney(Request $request)
    {
        $jsonData = $request->json()->all();
        $validator = Validator::make($jsonData, [
            'senderId' => 'required|integer',
            'receiverId' => 'required|integer',
            'amount' => 'required|numeric|more_than_zero',
            'receiverCurrency' => 'boolean'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return [
                'success' => false,
                'errors' => $errors
            ];
        }



        return DB::transaction(function() use($jsonData) {

            $sender = Client::find($jsonData['senderId']);
            $receiver = Client::find($jsonData['receiverId']);

            if (!$sender || !$receiver) {
                abort(404);
            }

            $senderSubtractAmount = $jsonData['amount'];
            $receiverAddAmount = $senderSubtractAmount;
            if ($sender->currency_id != $receiver->currency_id) {

                $currentDate = new \DateTime();
                if (!$sender->currency) {
                    throw new \Exception('Sender\'s currency is not defined');
                }
                $senderUsdRate = $sender->currency->getUsdRateForDate($currentDate);

                if (!$senderUsdRate) {
                    throw new \Exception('USD conversion rate is not defined for currency ' . $sender->currency->code . ' and date ' . $currentDate->format('Y-m-d'));
                }

                if (!$receiver->currency) {
                    throw new \Exception('Receiver\'s currency is not defined');
                }
                $receiverUsdRate = $receiver->currency->getUsdRateForDate($currentDate);
                if (!$receiverUsdRate) {
                    throw new \Exception('USD conversion rate is not defined for currency ' . $sender->currency->code . ' and date ' . $currentDate->format('Y-m-d'));
                }

                if (!empty($jsonData['receiverCurrency'])) {
                    $senderSubtractAmount = (($receiverAddAmount * $receiverUsdRate) / $senderUsdRate);
                } else {
                    $receiverAddAmount = (($senderSubtractAmount * $senderUsdRate) / $receiverUsdRate);
                }
            }

            $currentSenderBalance = (float) $sender->balance;
            if ($currentSenderBalance < $senderSubtractAmount) {
                throw new \Exception('Sender has no enough money to transfer');
            }

            $sender->subtractMoney($senderSubtractAmount);
            $receiver->addMoney($receiverAddAmount);

            $sender->refresh();
            $receiver->refresh();

            event(new MoneyTransfer($sender, $receiver, $senderSubtractAmount, $receiverAddAmount));

            return [
                'success' => true,
                'sender' => [
                    'currency' => $sender->currency->code,
                    'balance' => $sender->getBalanceAsFloat()
                ],
                'receiver' => [
                    'currency' => $receiver->currency->code,
                    'balance' => $receiver->getBalanceAsFloat()
                ]
            ];

        });
    }

    public function loadCurrencyRates(Request $request)
    {
        $jsonData = $request->json()->all();

        $validator = Validator::make($jsonData, [
            'date' => 'required',
        ]);
        $validator->after(function ($validator) use ($jsonData) {
            if (isset($jsonData['currencies']) && is_array($jsonData['currencies'])) {
                foreach ($jsonData['currencies'] as $rate => $amount) {
                    $currencyValidator = Validator::make([
                        'rate' => $rate,
                        'amount' => $amount
                    ], [
                        'rate' => 'min:3|max:3',
                        'amount' => 'numeric|more_than_zero'
                    ]);
                    if ($currencyValidator->fails()) {
                        $validator->errors()->add('currencies', 'Invalid format of currencies: ' . $currencyValidator->errors()->first());
                    }
                }
            } else {
                $validator->errors()->add('currencies', 'Array of currencies is required field');
            }
        });
        if ($validator->fails()) {
            $errors = $validator->errors();
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $date = DateTimeFormatHelper::createFromDbDateFormat($jsonData['date']);
        if (!$date) {
            throw new \Exception('Invalid date format');
        }

        $currencies = $jsonData['currencies'];

        return DB::transaction(function() use ($date, $currencies) {

            foreach ($currencies as $currencyCode => $usdRate) {
                $currency = Currency::where('code', $currencyCode)->first();
                if (!$currency) {
                    $currency = new Currency();
                    $currency->code = $currencyCode;
                    $currency->save();
                }

                $currency->updateUsdRateForDate($date, $usdRate);
            }

            return [
                'success' => true
            ];
        });
    }
}