<?php

use Illuminate\Http\Request;
use Http\Controllers\FacultyController;
use Http\Controllers\UserController;


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

//Route::apiResource('/faculty', 'FacultyController');
//Route::apiResource('/user', 'UserController');
Route::post('/user/login', 'UserController@login');
Route::get('/user/login', 'UserController@block');

Route::get('/faculty', 'FacultyController@index');
Route::post('/faculty/create', 'FacultyController@create');
Route::get('/faculty/create', 'UserController@block');
//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
