<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/**
 * @var \Laravel\Lumen\Routing\Router $router $router
 */

$router->get('/', 'ReportController@report');
$router->post('/', 'ReportController@report');

$router->post('/api/create-client', 'ApiController@create');
$router->post('/api/add-money/{clientId}', 'ApiController@addMoney');
$router->post('/api/transfer-money', 'ApiController@transferMoney');
$router->post('/api/load-currency-rates', 'ApiController@loadCurrencyRates');
