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
Route::group(['prefix' => '/', 'middleware' => ['jsonify', 'cors']], function () {

    Route::get('/', function (Request $request) {
        return response()->json(array(
            'status' => 'API is running!',
            'version' => '1.0.0',
        ), 200);
    });
    Route::get('entity', 'App\Http\Controllers\EntityController@index');

    Route::post('login', 'App\Http\Controllers\LoginController@login');
    
    Route::middleware('auth:api')->get('user', 'App\Http\Controllers\UserController@index');
    
    Route::post('user', 'App\Http\Controllers\UserController@create');
    
    Route::get('user/verify/{id}', 'VerificationController@verify')->name('verification.verify');
    
    Route::post('user/resend', 'VerificationController@resend')->name('verification.resend');

    Route::fallback(function () {
        return response()->json([
            'message' => 'Path not found. If error persists, contact the administrator'], 404);
    });
});