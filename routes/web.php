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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function ($router) {
    // Extracts data from csv files and persists to DB
    $router->get('data/set', 'DataController@setMonthlyData');

    // Gets the total number of years and months uploaded to the DB
    $router->get('data/get/years-and-months', 'DataController@getYearsAndMonths');


    $router->get('data/get/{year}/{month}', 'DataController@getMonthlyData');
});