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

Route::get('reset_password/{token}', ['as' => 'password.reset', function($token)
{
    // implement your reset password route here!
}]);
Route::get('/greet', 'UserController@greet');
Route::get('/', function () {
    return view('welcome');

});
Route::get('/ca_export', 'FinanceController@download_ca_export');
 Route::get('/ca_list_all', function () {
        return view('ca_list');

    });
Route::get('/print', 'CustomerController@printPDF')->name('print');
Route::prefix('asset_registration')->group(function () {
    Route::get('/', 'AssetRegistrationController@index')->name('ar-index');
    Route::get('/aaa', 'AssetRegistrationController@get_qr_code')->name('qr-code');

});

