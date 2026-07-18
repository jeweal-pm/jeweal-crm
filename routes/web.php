<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnquiriesController;
use App\Http\Controllers\GisEnquiriesController;
use App\Http\Controllers\GmsStoneEnquiriesController;
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

Route::get('/', function () {
    return abort(404);
});



Route::get('/crm-login-system', function () {
    return view('administrator.login');
});



Route::middleware('guest')->group(function () {
    Route::post('/crm-login-system', [LoginController::class, 'authenticate'])->name('login');

});
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/add-user', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/enquiry', [EnquiriesController::class, 'index'])->name('enquiry.index');
    Route::get('/gis-enquiry', [GisEnquiriesController::class, 'index'])->name('gisEnquiry');
    Route::get('/gms-enquiry', [GmsStoneEnquiriesController::class, 'index'])->name('gms-enquiries.index');
    Route::get('/gms-enquiry/create', [GmsStoneEnquiriesController::class, 'create'])->name('gms-enquiries.create');
    Route::post('/gms-enquiry', [GmsStoneEnquiriesController::class, 'store'])->name('gms-enquiries.store');
    Route::get('/gms-enquiry/{id}', [GmsStoneEnquiriesController::class, 'show'])->name('gms-enquiries.show');
    Route::get('/gms-enquiry/{id}/edit', [GmsStoneEnquiriesController::class, 'edit'])->name('gms-enquiries.edit');
    Route::put('/gms-enquiry/{id}', [GmsStoneEnquiriesController::class, 'update'])->name('gms-enquiries.update');
    Route::delete('/gms-enquiry/{id}', [GmsStoneEnquiriesController::class, 'destroy'])->name('gms-enquiries.destroy');
    Route::post('/gms-enquiry/{id}/restore', [GmsStoneEnquiriesController::class, 'restore'])->name('gms-enquiries.restore');
    Route::post('/gms-enquiry/{id}/assign', [GmsStoneEnquiriesController::class, 'assign'])
        ->middleware('permission:enquiry.assign.to_sale|enquiry.assign.to_sale_manager')
        ->name('gms-enquiries.assign');

    Route::post('/enquiries/{id}/assign', [EnquiriesController::class, 'assign'])
        ->middleware('permission:enquiry.assign.to_sale|enquiry.assign.to_sale_manager')
        ->name('enquiries.assign');
    Route::patch('/enquiries/{id}/status', [EnquiriesController::class, 'updateStatus'])
        ->middleware('permission:enquiry.update_status')
        ->name('enquiries.status');
    Route::delete('/enquiries/{id}', [EnquiriesController::class, 'destroy'])
        ->middleware('permission:enquiry.delete')
        ->name('enquiries.destroy');
    Route::post('/enquiries/bulk-delete', [EnquiriesController::class, 'bulkDelete'])
        ->middleware('permission:enquiry.bulk_delete')
        ->name('enquiries.bulk-delete');
    Route::post('/enquiries/{id}/restore', [EnquiriesController::class, 'restore'])
        ->middleware('permission:enquiry.restore')
        ->name('enquiries.restore');
    Route::patch('/enquiries/{id}/spam-status', [EnquiriesController::class, 'updateSpamStatus'])
        ->middleware('permission:enquiry.restore')
        ->name('enquiries.spam-status');

    Route::post('/gis-enquiries/{id}/assign', [GisEnquiriesController::class, 'assign'])
        ->middleware('permission:enquiry.assign.to_sale|enquiry.assign.to_sale_manager')
        ->name('gis-enquiries.assign');
    Route::patch('/gis-enquiries/{id}/status', [GisEnquiriesController::class, 'updateStatus'])
        ->middleware('permission:enquiry.update_status')
        ->name('gis-enquiries.status');
    Route::delete('/gis-enquiries/{id}', [GisEnquiriesController::class, 'destroy'])
        ->middleware('permission:enquiry.delete')
        ->name('gis-enquiries.destroy');
    Route::post('/gis-enquiries/bulk-delete', [GisEnquiriesController::class, 'bulkDelete'])
        ->middleware('permission:enquiry.bulk_delete')
        ->name('gis-enquiries.bulk-delete');
    Route::post('/gis-enquiries/{id}/restore', [GisEnquiriesController::class, 'restore'])
        ->middleware('permission:enquiry.restore')
        ->name('gis-enquiries.restore');
    Route::patch('/gis-enquiries/{id}/spam-status', [GisEnquiriesController::class, 'updateSpamStatus'])
        ->middleware('permission:enquiry.restore')
        ->name('gis-enquiries.spam-status');


    Route::get('logout', [LoginController::class, 'destroy'])
                ->name('logout');

});
