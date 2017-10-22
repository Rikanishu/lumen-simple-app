<?php

namespace App\Http\Controllers;

use App\Helper\DateTimeFormatHelper;
use App\Models\BalanceEvent;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class ReportController extends Controller
{
    public function report(Request $request)
    {
        $formData = [
            'client' => null,
            'dateFrom' => null,
            'dateTo' => null
        ];

        $clients = Client::all();

        $error = null;
        $reportData = null;

        if ($request->get('client')) {

            try {
                $dateFrom = null;
                $dateTo = null;
                $formData['client'] = $request->get('client');
                if ($request->get('dateFrom')) {
                    $formData['dateFrom'] = $request->get('dateFrom');
                    $dateFrom = DateTimeFormatHelper::createFromDbDateFormat($request->get('dateFrom'));
                }
                if ($request->get('dateTo')) {
                    $formData['dateTo'] = $request->get('dateTo');
                    $dateTo = DateTimeFormatHelper::createFromDbDateFormat($request->get('dateTo'));
                }

                $rows = [];
                $totalWalletCurrency = 0;
                $totalUsd = 0;

                $client = Client::findOrFail($request->get('client'));
                $clientCurrency = $client->currency;
                $query = BalanceEvent::where('client_id', $client->id);
                if ($dateFrom) {
                    $query->where('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->where('date', '<=', $dateTo);
                }


                foreach ($query->cursor() as $event) {
                    $row = [];
                    $row['dateTime'] = $event->date;
                    $row['description'] = $event->getEventDescription();

                    if ($event->amount == 0) {
                        $row['operationClass'] = 'none';
                        $row['amountWalletCurrency'] = '0.00';
                        $row['amountUsd'] = '0.00';
                    } else {
                        $row['operationClass'] = ($event->isPositive()) ? 'positive' : 'negative';
                        $symbol = $event->isPositive() ? '+' : '-';
                        $walletCurrencyAmount =  $event->getAmountAsFloat();
                        $row['amountWalletCurrency'] = $symbol . $walletCurrencyAmount . ' ' . $clientCurrency->code;
                        $usdAmount = $event->getAmountInUsd();
                        $row['amountUsd'] =  (!$usdAmount) ? '' : $symbol . (round($usdAmount, 2) . ' USD');

                        $totalWalletCurrency += $walletCurrencyAmount;
                        $totalUsd += $usdAmount;
                    }

                    $rows[] = $row;
                }

                $reportData = [
                    'rows' => $rows,
                    'totalWalletCurrency' => $totalWalletCurrency . ' ' . $clientCurrency->code,
                    'totalUsd' => $totalUsd . ' USD'
                ];

            } catch (\Exception $e) {
                Log::error($e);
                $error = $e->getMessage();
            }

        }

        if ($request->get('download')) {
            return $this->_sendReport($reportData);
        }

        return View::make('report', [
            'clients' => $clients,
            'formData' => $formData,
            'reportData' => $reportData,
            'error' => $error,
            'downloadReportUrl' => '/?' . http_build_query($formData + ['download' => 'true'])
        ]);
    }

    protected function _sendReport($reportData)
    {
        $temp = tempnam(sys_get_temp_dir(), 'test_');
        $fh = fopen($temp, 'w');
        fputcsv($fh, ['Date/time', 'Operation', 'Amount (wallet currency)', 'Amount (USD)']);
        if ($reportData) {
            foreach ($reportData['rows'] as $row) {
                fputcsv($fh, [$row['dateTime'], $row['description'], $row['amountWalletCurrency'], $row['amountUsd']]);
            }
            fputcsv($fh, ['', '', 'Total (wallet currency):', $reportData['totalWalletCurrency']]);
            fputcsv($fh, ['', '', 'Total (USD):', $reportData['totalUsd']]);
        }

        $content = file_get_contents($temp);
        fclose($fh);
        if (is_file($temp)) {
            unlink($temp);
        }

        return response($content, 200, [
            'Content-Type' => 'application/csv',
            'Content-Disposition' => 'inline; filename="Report.csv"'
        ]);
    }
}