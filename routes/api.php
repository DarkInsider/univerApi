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

Route::post('/user', 'UserController@create');
Route::get('/user', 'UserController@block');

Route::get('/faculty', 'FacultyController@get');
Route::post('/faculty', 'FacultyController@create');
Route::put('/faculty', 'FacultyController@update');
Route::delete('/faculty', 'FacultyController@delete');

Route::post('/faculty/create', 'FacultyController@create');
Route::get('/faculty/create', 'UserController@block');


Route::get('/department', 'DepartmentController@get');
Route::post('/department', 'DepartmentController@create');
Route::put('/department', 'DepartmentController@update');

Route::get('/role', 'RoleController@get');


//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
