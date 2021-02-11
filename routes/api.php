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

    Route::middleware('auth:api')->post('entity', 'App\Http\Controllers\EntityController@post');

    Route::get('entity/{id}', 'App\Http\Controllers\EntityController@get');

    Route::middleware('auth:api')->put('entity/{id}', 'App\Http\Controllers\EntityController@put');

    Route::middleware('auth:api')->delete('entity/{id}', 'App\Http\Controllers\EntityController@delete');

    Route::middleware('auth:api')->delete('entity', 'App\Http\Controllers\EntityController@batchDelete');

    // insurance record

    Route::middleware('auth:api')->get('record', 'App\Http\Controllers\RecordController@index');

    Route::middleware('auth:api')->get('record/{id}', 'App\Http\Controllers\RecordController@get');

    Route::middleware('auth:api')->post('record', 'App\Http\Controllers\RecordController@post');

    Route::middleware('auth:api')->put('record/{id}', 'App\Http\Controllers\RecordController@put');

    Route::middleware('auth:api')->delete('record/{id}', 'App\Http\Controllers\RecordController@delete');

    Route::middleware('auth:api')->delete('record', 'App\Http\Controllers\RecordController@batchDelete');

    // login and user functions

    Route::post('auth', 'App\Http\Controllers\LoginController@login');

    Route::middleware('auth:api')->get('user', 'App\Http\Controllers\UserController@index');

    Route::middleware('auth:api')->get('user/{id}', 'App\Http\Controllers\UserController@get');
    
    Route::middleware('auth:api')->patch('user/{id}', 'App\Http\Controllers\UserController@patch');

    Route::middleware('auth:api')->get('auth', function (Request $request) {
        return response(null, 204);
    });

    Route::post('user', 'App\Http\Controllers\UserController@store');

    Route::middleware('auth:api')->delete('user/{id}', 'App\Http\Controllers\UserController@delete');

    Route::middleware('auth:api')->delete('user', 'App\Http\Controllers\UserController@batchDelete');

    Route::get('user/verify/{id}', 'App\Http\Controllers\VerificationController@verify')->name('verification.verify');

    Route::post('user/resend', 'App\Http\Controllers\VerificationController@resend')->name('verification.resend');

    Route::post('user/forgot-password', 'App\Http\Controllers\ForgotPasswordController@requestLink')->middleware('guest')->name('password.email');

    Route::get('user/reset-password', 'App\Http\Controllers\ForgotPasswordController@resetPasswordToken')->middleware('guest')->name('password.reset');

    Route::post('user/reset-password', 'App\Http\Controllers\ForgotPasswordController@setNewPassword')->middleware('guest')->name('password.update');


    // settings
    Route::middleware('auth:api')->get('settings', 'App\Http\Controllers\SettingController@get');

    Route::middleware('auth:api')->patch('settings', 'App\Http\Controllers\SettingController@patch');


    Route::fallback(function () {
        return response()->json([
            'message' => 'Path not found. If error persists, contact the administrator'], 404);
    });
});
