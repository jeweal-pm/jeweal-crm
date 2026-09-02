<?php

use App\Http\Controllers\EmailManagementController;
use App\Http\Controllers\EmailSubscriptionController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\EnquiriesController;
use App\Http\Controllers\GisEnquiriesController;
use App\Http\Controllers\GisFairCampaignController;
use App\Http\Controllers\GisFairDashboardController;
use App\Http\Controllers\GisFairLeadAdminController;
use App\Http\Controllers\GisFairRedirectController;
use App\Http\Controllers\GisFairTrackingLinkController;
use App\Http\Controllers\GmsStoneEnquiriesController;
use App\Http\Controllers\IpSecurityController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\TwilioConfigurationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappMessageAdminController;
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

Route::get('/', function () {
    return abort(404);
});

Route::get('/r/{code}', GisFairRedirectController::class)
    ->where('code', '[A-Za-z0-9_-]+')
    ->middleware('throttle:120,1')
    ->name('gis-fair.redirect');

Route::middleware('throttle:120,1')->group(function () {
    Route::get('/email-track/open/{messageId}', [EmailTrackingController::class, 'open'])->name('email.track.open');
    Route::get('/email-track/click/{messageId}', [EmailTrackingController::class, 'click'])->name('email.track.click');
    Route::get('/unsubscribe/{token}', [EmailSubscriptionController::class, 'show'])->name('email.unsubscribe');
    Route::post('/unsubscribe/{token}', [EmailSubscriptionController::class, 'unsubscribe']);
});

Route::get('/crm-login-system', function () {
    return view('administrator.login');
});

Route::middleware('guest')->group(function () {
    Route::post('/crm-login-system', [LoginController::class, 'authenticate'])->name('login');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::prefix('gis-fair')->name('gis-fair.')->group(function () {
        Route::get('/dashboard', GisFairDashboardController::class)->name('dashboard');
        Route::get('/leads', [GisFairLeadAdminController::class, 'index'])->name('leads.index');
        Route::get('/leads/{lead}', [GisFairLeadAdminController::class, 'show'])->name('leads.show');
        Route::get('/leads/{lead}/reply', [GisFairLeadAdminController::class, 'reply'])->name('leads.reply');
        Route::post('/leads/{lead}/reply', [GisFairLeadAdminController::class, 'sendReply'])->name('leads.reply.send');
        Route::post('/leads/{lead}/assign', [GisFairLeadAdminController::class, 'assign'])->name('leads.assign');
        Route::patch('/leads/{lead}/status', [GisFairLeadAdminController::class, 'updateStatus'])->name('leads.status');
        Route::patch('/leads/{lead}/spam-status', [GisFairLeadAdminController::class, 'updateSpamStatus'])->name('leads.spam-status');
        Route::post('/leads/{lead}/resend', [GisFairLeadAdminController::class, 'resend'])->name('leads.resend');
        Route::post('/leads/{lead}/withdraw-marketing', [GisFairLeadAdminController::class, 'withdrawMarketing'])->name('leads.withdraw-marketing');
        Route::delete('/leads/{lead}', [GisFairLeadAdminController::class, 'destroy'])->name('leads.destroy');
        Route::post('/leads/{lead}/restore', [GisFairLeadAdminController::class, 'restore'])->name('leads.restore');
        Route::post('/leads/bulk-actions/apply', [GisFairLeadAdminController::class, 'bulkAction'])->name('leads.bulk-action');

        Route::get('/campaigns', [GisFairCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [GisFairCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [GisFairCampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}', [GisFairCampaignController::class, 'show'])->name('campaigns.show');
        Route::get('/campaigns/{campaign}/edit', [GisFairCampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('/campaigns/{campaign}', [GisFairCampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campaigns/{campaign}', [GisFairCampaignController::class, 'destroy'])->name('campaigns.destroy');
        Route::post('/campaigns/{campaign}/links', [GisFairTrackingLinkController::class, 'store'])->name('links.store');
        Route::put('/campaigns/{campaign}/links/{link}', [GisFairTrackingLinkController::class, 'update'])->name('links.update');
        Route::delete('/campaigns/{campaign}/links/{link}', [GisFairTrackingLinkController::class, 'destroy'])->name('links.destroy');
    });
    Route::prefix('email')->name('email.')->group(function () {
        Route::get('/', [EmailManagementController::class, 'dashboard'])->middleware('permission:email.view')->name('dashboard');
        Route::get('/templates', [EmailManagementController::class, 'templates'])->middleware('permission:email.view')->name('templates');
        Route::get('/templates/create', [EmailManagementController::class, 'createTemplate'])->middleware('permission:email.template.manage')->name('templates.create');
        Route::post('/templates', [EmailManagementController::class, 'storeTemplate'])->middleware('permission:email.template.manage')->name('templates.store');
        Route::get('/templates/{id}/edit', [EmailManagementController::class, 'editTemplate'])->middleware('permission:email.template.manage')->name('templates.edit');
        Route::put('/templates/{id}', [EmailManagementController::class, 'updateTemplate'])->middleware('permission:email.template.manage')->name('templates.update');
        Route::post('/templates/{id}/publish', [EmailManagementController::class, 'publishTemplate'])->middleware('permission:email.template.publish')->name('templates.publish');
        Route::get('/templates/{id}/preview', [EmailManagementController::class, 'previewTemplate'])->middleware('permission:email.view')->name('templates.preview');
        Route::post('/templates/{id}/test-send', [EmailManagementController::class, 'testSend'])->middleware('permission:email.template.manage')->name('templates.test-send');
        Route::post('/templates/{id}/duplicate', [EmailManagementController::class, 'duplicateTemplate'])->middleware('permission:email.template.manage')->name('templates.duplicate');
        Route::post('/templates/{id}/versions/{version}/restore', [EmailManagementController::class, 'restoreTemplateVersion'])->middleware('permission:email.template.manage')->name('templates.versions.restore');
        Route::get('/config', [EmailManagementController::class, 'config'])->middleware('permission:email.view')->name('config');
        Route::put('/config/{type}', [EmailManagementController::class, 'updateConfig'])->middleware('permission:email.config.manage')->name('config.update');
        Route::get('/segments', [EmailManagementController::class, 'segments'])->middleware('permission:email.view')->name('segments');
        Route::post('/segments', [EmailManagementController::class, 'storeSegment'])->middleware('permission:email.segment.manage')->name('segments.store');
        Route::get('/campaigns', [EmailManagementController::class, 'campaigns'])->middleware('permission:email.view')->name('campaigns');
        Route::get('/campaigns/create', [EmailManagementController::class, 'createCampaign'])->middleware('permission:email.campaign.manage')->name('campaigns.create');
        Route::post('/campaigns', [EmailManagementController::class, 'storeCampaign'])->middleware('permission:email.campaign.manage')->name('campaigns.store');
        Route::post('/campaigns/{id}/approve', [EmailManagementController::class, 'approveCampaign'])->middleware('permission:email.campaign.approve')->name('campaigns.approve');
        Route::post('/campaigns/{id}/run', [EmailManagementController::class, 'runCampaign'])->middleware('permission:email.campaign.send')->name('campaigns.run');
        Route::post('/campaigns/{id}/variants', [EmailManagementController::class, 'storeVariant'])->middleware('permission:email.campaign.manage')->name('campaigns.variants.store');
        Route::get('/sequences', [EmailManagementController::class, 'sequences'])->middleware('permission:email.view')->name('sequences');
        Route::get('/sequences/create', [EmailManagementController::class, 'createSequence'])->middleware('permission:email.sequence.manage')->name('sequences.create');
        Route::post('/sequences', [EmailManagementController::class, 'storeSequence'])->middleware('permission:email.sequence.manage')->name('sequences.store');
        Route::get('/sequences/{id}', [EmailManagementController::class, 'showSequence'])->middleware('permission:email.view')->name('sequences.show');
        Route::put('/sequences/{id}', [EmailManagementController::class, 'updateSequence'])->middleware('permission:email.sequence.manage')->name('sequences.update');
        Route::post('/sequences/{id}/steps', [EmailManagementController::class, 'storeSequenceStep'])->middleware('permission:email.sequence.manage')->name('sequences.steps.store');
        Route::put('/sequences/{id}/steps/{stepId}', [EmailManagementController::class, 'updateSequenceStep'])->middleware('permission:email.sequence.manage')->name('sequences.steps.update');
        Route::delete('/sequences/{id}/steps/{stepId}', [EmailManagementController::class, 'destroySequenceStep'])->middleware('permission:email.sequence.manage')->name('sequences.steps.destroy');
        Route::get('/enrollments', [EmailManagementController::class, 'enrollments'])->middleware('permission:email.view')->name('enrollments');
        Route::post('/enrollments', [EmailManagementController::class, 'enroll'])->middleware('permission:email.sequence.manage')->name('enrollments.store');
        Route::delete('/enrollments/{id}', [EmailManagementController::class, 'destroyEnrollment'])->middleware('permission:email.sequence.manage')->name('enrollments.destroy');
        Route::get('/logs', [EmailManagementController::class, 'logs'])->middleware('permission:email.view')->name('logs');
    });
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/add-user', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/messages', [WhatsappMessageAdminController::class, 'index'])
            ->middleware('permission:whatsapp.view')->name('messages.index');
        Route::post('/messages/{id}/retry', [WhatsappMessageAdminController::class, 'retry'])
            ->middleware('permission:whatsapp.message.manage')->name('messages.retry');
        Route::delete('/messages/{id}', [WhatsappMessageAdminController::class, 'destroy'])
            ->middleware('permission:whatsapp.message.manage')->name('messages.destroy');
        Route::get('/config', [TwilioConfigurationController::class, 'edit'])
            ->middleware('permission:whatsapp.config.manage')->name('config.edit');
        Route::put('/config', [TwilioConfigurationController::class, 'update'])
            ->middleware('permission:whatsapp.config.manage')->name('config.update');
    });
    Route::prefix('security/ip-controls')->name('security.ip.')->group(function () {
        Route::get('/', [IpSecurityController::class, 'index'])
            ->middleware('permission:security.ip.view')->name('index');
        Route::post('/blacklist', [IpSecurityController::class, 'storeBlacklist'])
            ->middleware('permission:security.ip.manage')->name('blacklist.store');
        Route::delete('/blacklist/{id}', [IpSecurityController::class, 'destroyBlacklist'])
            ->middleware('permission:security.ip.manage')->name('blacklist.destroy');
        Route::put('/rate-limits/{id}', [IpSecurityController::class, 'updateRateLimit'])
            ->middleware('permission:security.ip.manage')->name('rate-limits.update');
    });
    Route::get('/enquiry', [EnquiriesController::class, 'index'])->name('enquiry.index');
    Route::get('/gis-enquiry', [GisEnquiriesController::class, 'index'])->name('gisEnquiry');
    Route::get('/gms-enquiry', [GmsStoneEnquiriesController::class, 'index'])->name('gms-enquiries.index');
    Route::get('/gms-enquiry/create', [GmsStoneEnquiriesController::class, 'create'])->name('gms-enquiries.create');
    Route::post('/gms-enquiry', [GmsStoneEnquiriesController::class, 'store'])->name('gms-enquiries.store');
    Route::get('/gms-enquiry/{id}/reply', [GmsStoneEnquiriesController::class, 'reply'])->name('gms-enquiries.reply');
    Route::post('/gms-enquiry/{id}/reply', [GmsStoneEnquiriesController::class, 'sendReply'])->name('gms-enquiries.reply.send');
    Route::get('/gms-enquiry/{id}', [GmsStoneEnquiriesController::class, 'show'])->name('gms-enquiries.show');
    Route::get('/gms-enquiry/{id}/edit', [GmsStoneEnquiriesController::class, 'edit'])->name('gms-enquiries.edit');
    Route::put('/gms-enquiry/{id}', [GmsStoneEnquiriesController::class, 'update'])->name('gms-enquiries.update');
    Route::delete('/gms-enquiry/{id}', [GmsStoneEnquiriesController::class, 'destroy'])->name('gms-enquiries.destroy');
    Route::post('/gms-enquiry/{id}/restore', [GmsStoneEnquiriesController::class, 'restore'])->name('gms-enquiries.restore');
    Route::post('/gms-enquiry/{id}/assign', [GmsStoneEnquiriesController::class, 'assign'])
        ->middleware('permission:enquiry.assign.to_sale|enquiry.assign.to_sale_manager')
        ->name('gms-enquiries.assign');
    Route::patch('/gms-enquiry/{id}/status', [GmsStoneEnquiriesController::class, 'updateStatus'])
        ->middleware('permission:enquiry.update_status')
        ->name('gms-enquiries.status');
    Route::post('/gms-enquiry/bulk-actions', [GmsStoneEnquiriesController::class, 'bulkAction'])
        ->name('gms-enquiries.bulk-action');

    Route::post('/enquiries/{id}/assign', [EnquiriesController::class, 'assign'])
        ->middleware('permission:enquiry.assign.to_sale|enquiry.assign.to_sale_manager')
        ->name('enquiries.assign');
    Route::get('/enquiries/{id}/reply', [EnquiriesController::class, 'reply'])
        ->name('enquiries.reply');
    Route::post('/enquiries/{id}/reply', [EnquiriesController::class, 'sendReply'])
        ->name('enquiries.reply.send');
    Route::patch('/enquiries/{id}/status', [EnquiriesController::class, 'updateStatus'])
        ->middleware('permission:enquiry.update_status')
        ->name('enquiries.status');
    Route::delete('/enquiries/{id}', [EnquiriesController::class, 'destroy'])
        ->middleware('permission:enquiry.delete')
        ->name('enquiries.destroy');
    Route::post('/enquiries/bulk-delete', [EnquiriesController::class, 'bulkDelete'])
        ->middleware('permission:enquiry.bulk_delete')
        ->name('enquiries.bulk-delete');
    Route::post('/enquiries/bulk-actions', [EnquiriesController::class, 'bulkAction'])
        ->name('enquiries.bulk-action');
    Route::post('/enquiries/{id}/restore', [EnquiriesController::class, 'restore'])
        ->middleware('permission:enquiry.restore')
        ->name('enquiries.restore');
    Route::patch('/enquiries/{id}/spam-status', [EnquiriesController::class, 'updateSpamStatus'])
        ->middleware('permission:enquiry.restore')
        ->name('enquiries.spam-status');

    Route::post('/gis-enquiries/{id}/assign', [GisEnquiriesController::class, 'assign'])
        ->middleware('permission:enquiry.assign.to_sale|enquiry.assign.to_sale_manager')
        ->name('gis-enquiries.assign');
    Route::get('/gis-enquiries/{id}/reply', [GisEnquiriesController::class, 'reply'])
        ->name('gis-enquiries.reply');
    Route::post('/gis-enquiries/{id}/reply', [GisEnquiriesController::class, 'sendReply'])
        ->name('gis-enquiries.reply.send');
    Route::patch('/gis-enquiries/{id}/status', [GisEnquiriesController::class, 'updateStatus'])
        ->middleware('permission:enquiry.update_status')
        ->name('gis-enquiries.status');
    Route::delete('/gis-enquiries/{id}', [GisEnquiriesController::class, 'destroy'])
        ->middleware('permission:enquiry.delete')
        ->name('gis-enquiries.destroy');
    Route::post('/gis-enquiries/bulk-delete', [GisEnquiriesController::class, 'bulkDelete'])
        ->middleware('permission:enquiry.bulk_delete')
        ->name('gis-enquiries.bulk-delete');
    Route::post('/gis-enquiries/bulk-actions', [GisEnquiriesController::class, 'bulkAction'])
        ->name('gis-enquiries.bulk-action');
    Route::post('/gis-enquiries/{id}/restore', [GisEnquiriesController::class, 'restore'])
        ->middleware('permission:enquiry.restore')
        ->name('gis-enquiries.restore');
    Route::patch('/gis-enquiries/{id}/spam-status', [GisEnquiriesController::class, 'updateSpamStatus'])
        ->middleware('permission:enquiry.restore')
        ->name('gis-enquiries.spam-status');

    Route::get('logout', [LoginController::class, 'destroy'])
                ->name('logout');
});
