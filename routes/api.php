<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/poll/{id}/view', 'PollController@index');

Route::post('/poll/{id}/vote', 'PollController@vote');

Route::post('/vote/{id}/verify', 'PollController@verify');

Route::get('/ongoing', 'MainController@ongoing');

Route::get('/closed', 'MainController@closed');

Route::post('/poll/create', 'MainController@createPoll');