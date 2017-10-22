<?php

namespace App\Console\Commands;

use App\Console\Helper\InternalRequestClient;
use Illuminate\Console\Command;

class Data extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'data:fill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(InternalRequestClient $client)
    {

        \Illuminate\Support\Facades\DB::table('clients')->delete();
        \Illuminate\Support\Facades\DB::table('currencies')->delete();
        \Illuminate\Support\Facades\DB::table('events')->delete();
        \Illuminate\Support\Facades\DB::table('rates_on_dates')->delete();

        $lastNames = [
            'SMITH',
            'JOHNSON',
            'WILLIAMS',
            'BROWN',
            'JONES',
            'MILLER',
            'DAVIS',
            'GARCIA',
            'RODRIGUEZ',
            'WILSON'
        ];

        $firstNames = [
            'JAMES',
            'JOHN',
            'ROBERT',
            'MICHAEL',
            'WILLIAM',
            'MARY',
            'PATRICIA',
            'LINDA',
            'BARBARA'
        ];

        $geoSets = [
            ['New York', 'USA', 'USD'],
            ['Madrid', 'Spain', 'EUR'],
            ['Yerevan', 'Armenia', 'AMD'],
            ['Minsk', 'Belarus', 'BYN'],
            ['Moscow', 'Russia', 'RUB'],
            ['Astana', 'Kazakhstan', 'KZN'],
            ['Mexico', 'Mexico', 'MXN'],
            ['Oslo', 'Norway', 'NOK'],
            ['Ankara', 'Turkey', 'TRY'],
            ['Ankara', 'Turkey', 'TRY'],
            ['Tashkent', 'Uzbekistan', 'UZN'],
        ];

        $clientIds = [];
        for ($i = 0; $i < 100; $i++) {
            $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
            $set = $geoSets[array_rand($geoSets)];
            $res = $client->json('POST', '/api/create-client', [
                'name' => $name,
                'city' => $set[0],
                'country' => $set[1],
                'currency' => $set[2]
            ]);
            $data = json_decode($res->getContent(), true);
            $clientIds[] = $data['client_id'];
        }

        foreach ($clientIds as $clientId) {
            $client->json('POST', '/api/add-money/' . $clientId, [
               'amount' => (mt_rand(1, 100000) * 0.87654321)
            ]);
        }

        $dateTime = new \DateTime();
        $dateTime = $dateTime->format('Y-m-d');
        $currencies = [];
        foreach ($geoSets as $geoSet) {
            $currencies[$geoSet[2]] = (mt_rand(1, 100) * 0.992);
        }
        $client->json('POST', '/api/load-currency-rates', [
            'date' => $dateTime,
            'currencies' => $currencies
        ]);

        for ($i = 0; $i < 1000; $i++) {
            $randClient1 = $clientIds[array_rand($clientIds)];
            do {
                $randClient2 = $clientIds[array_rand($clientIds)];
            } while($randClient1 == $randClient2);
            $useReceiverCurrency = mt_rand(0, 1);
            $data = [
                'senderId' => $randClient1,
                'receiverId' => $randClient2,
                'amount' => (mt_rand(1, 1000) * 0.87654321)
            ];
            if ($useReceiverCurrency) {
                $data['receiverCurrency'] = true;
            }
            $client->json('POST', '/api/transfer-money', $data);

        }
    }
}
