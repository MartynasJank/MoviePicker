<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController@index');
Route::get('/userinput', 'AjaxController@index');
Route::get('/criteria', 'CriteriaController@index');
Route::post('/movie', 'RandomMovieController@show');
Route::get('/movie', 'RandomMovieController@show');
Route::get('/movie/{id}', 'MovieController@show')->name('movie');
Route::get('/multiple', 'RandomMovieController@multiple');
Route::post('/multiple', 'RandomMovieController@multiple');

Route::get('/asdsadasdasdsadsaghfgh', function (){
    Artisan::call('schedule:run');
    die();
});
