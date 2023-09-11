<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Crawler as CrawlerController;  
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
Route::controller(CrawlerController::class)->group(function () {
    Route::get('/crawlers', 'index');
    Route::post('/crawler', 'store');
    Route::get('/crawler/fetch-by-parent', 'fetchByParent');
    Route::get('/crawler/seed-urls', 'getAllUniqueParents');

});
