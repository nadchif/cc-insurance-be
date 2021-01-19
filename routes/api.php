<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::group(['prefix' => '/', 'middleware' => ['jsonify']], function () {

    Route::get('/', function (Request $request) {
        return response()->json(array(
            'status' => 'API is running!',
            'version' => '1.0.0',
        ), 200);
    });

    // entity/organization
    Route::get('entity', 'App\Http\Controllers\EntityController@index');

    Route::middleware('auth:api')->post('entity', 'App\Http\Controllers\EntityController@store');

    Route::get('entity/{id}', 'App\Http\Controllers\EntityController@get');
    
    // insurance entry

    Route::middleware('auth:api')->get('entry', 'App\Http\Controllers\EntryController@index');

    Route::middleware('auth:api')->get('entry/{id}', 'App\Http\Controllers\EntryController@get');

    // login and user functions

    Route::post('login', 'App\Http\Controllers\LoginController@login');
    
    Route::middleware('auth:api')->get('user', 'App\Http\Controllers\UserController@index');
    
    Route::post('user', 'App\Http\Controllers\UserController@store');
    
    Route::get('user/verify/{id}', 'App\Http\Controllers\VerificationController@verify')->name('verification.verify');
    
    Route::post('user/resend', 'App\Http\Controllers\VerificationController@resend')->name('verification.resend');

    Route::fallback(function () {
        return response()->json([
            'message' => 'Path not found. If error persists, contact the administrator'], 404);
    });
});