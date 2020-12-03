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
    Route::get('entity', 'App\Http\Controllers\EntityController@index');

    Route::fallback(function () {
        return response()->json([
            'message' => 'Path not found. If error persists, contact the administrator'], 404);
    });
});