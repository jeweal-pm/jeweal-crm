<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnquiriesController;
use App\Http\Controllers\GisEnquiriesController;
use App\Http\Controllers\GmsStoneEnquiriesController;
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

Route::middleware(['auth:sanctum', 'active'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/enquiry', [EnquiriesController::class, 'store'])->middleware('throttle:5,1');
Route::post('/gis-enquiry', [GisEnquiriesController::class, 'store'])->middleware('throttle:5,1');
Route::post('/gms-stone-enquiry', [GmsStoneEnquiriesController::class, 'store'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/enquiries', [EnquiriesController::class, 'filter'])
        ->middleware('permission:enquiry.filter');
    Route::get('/gis-enquiries', [GisEnquiriesController::class, 'filter'])
        ->middleware('permission:enquiry.filter');
    Route::post('/gms-stone-enquiries', [GmsStoneEnquiriesController::class, 'store']);
    Route::post('/gms-stone-enquiries/{id}/assign', [GmsStoneEnquiriesController::class, 'assign'])
        ->middleware('permission:enquiry.assign.to_sale|enquiry.assign.to_sale_manager');
    Route::apiResource('/gms-stone-enquiries', GmsStoneEnquiriesController::class)
        ->except(['store']);
    Route::post('/gms-stone-enquiries/{id}/restore', [GmsStoneEnquiriesController::class, 'restore']);
});
