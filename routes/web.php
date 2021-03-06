<?php

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


Route::get('/', 'RouteController@Index');
Route::group(['middleware' => 'auth'], function () {
    Route::get('/CreateNewJob','RouteController@CreateNewJob');
    Route::post('/CreateNewJob','AddressController@StoreAddress');
    Route::get('/Adresses','RouteController@ShowAddresses');
    Route::get('/StartSearch','AddressController@StartSearch');
    Route::get('/check/{ID}' , 'AddressController@check');
    Route::get('/C/{ID}' , 'AddressController@check_address');
});
Route::post('/GetDataFromSQl' , 'AddressController@GetDataFromSQl');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
