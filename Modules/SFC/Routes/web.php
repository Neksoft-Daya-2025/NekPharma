<?php

use Illuminate\Support\Facades\Route;
use Modules\SFC\Http\Controllers\SFCController;

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

Route::group(['middleware' => 'auth', 'prefix' => 'account'], function () {
    Route::resource('sfc-charts', SFCController::class)->names('sfc-charts');
    Route::get('sfc-charts/stockists/by-headquarter/{headquarterId}', [SFCController::class, 'getStockistsByHeadquarter'])->name('sfc-charts.stockists.by-headquarter');
});
