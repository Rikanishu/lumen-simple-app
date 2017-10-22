@extends('main')

@section('content')
    <div class="header-form">
        <form class="form-inline" method="GET">
            <div class="form-group">
                <label for="">Client</label>
                <select class="form-control" name="client" value="{{$formData['client']}}">
                    @foreach($clients as $client)
                        @if($formData['client'] == $client->id)
                            <option value="{{$client->id}}" selected>{{$client->name}}</option>
                        @else
                            <option value="{{$client->id}}">{{$client->name}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Date From (optional)</label>
                <input type="text" class="form-control" name="dateFrom" placeholder="YYYY-MM-DD" value="{{$formData['dateFrom']}}">
            </div>
            <div class="form-group">
                <label>Date To (optional)</label>
                <input type="text" class="form-control" name="dateTo" placeholder="YYYY-MM-DD" value="{{$formData['dateTo']}}">
            </div>
            <button type="submit" class="btn btn-primary">Show Report</button>
        </form>
    </div>
    @isset($error)
        <div class="alert alert-danger">{{$error}}</div>
    @endisset
    @isset($reportData)
        <div class="report-data">
            <table class="table">
                <thead>
                    <th>Date/time</th>
                    <th>Operation</th>
                    <th>Amount (wallet currency)</th>
                    <th>Amount (USD)</th>
                </thead>
                <tbody>
                    @forelse($reportData['rows'] as $row)
                        <tr class="operation-balance-{{$row['operationClass']}}">
                            <td>
                                {{$row['dateTime']}}
                            </td>
                            <td>
                                {{$row['description']}}
                            </td>
                            <td>
                                {{$row['amountWalletCurrency']}}
                            </td>
                            <td>
                                {{$row['amountUsd']}}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Activity not found</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="totals">
                <div>
                    <span class="title">Total (wallet currency):</span> {{$reportData['totalWalletCurrency']}}
                </div>
                <div>
                    <span class="title">Total (USD):</span> {{$reportData['totalUsd']}}
                </div>
            </div>
            @if($reportData['rows'])
            <div class="action-buttons">
                <a class="btn btn-default" href="{{$downloadReportUrl}}">Download Report (CSV)</a>
            </div>
            @endif
        </div>
    @endisset
@endsection