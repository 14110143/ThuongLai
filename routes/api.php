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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'user', 'middleware' => 'jwt.auth'], function() {
    Route::get('/info', 'AuthController@getUserInfo');
});

Route::group(['prefix' => 'auth'], function () {
	Route::post('register', ['as' => 'register', 'uses' => 'AuthController@register']);
	Route::get('login', 'AuthController@showLoginForm');
	Route::post('login', 'AuthController@login');
});
