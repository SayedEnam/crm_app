<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\AssigneeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandingSettingsController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ClientPortalController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\EmailSettingsController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PublicIntakeController;
use App\Http\Controllers\Api\V1\ReportsController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\UserAccessController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('configured.mail')->group(function () {
    Route::get('/public/services', [PublicIntakeController::class, 'services'])->name('public.services');
    Route::get('/public/branding', [BrandingSettingsController::class, 'publicShow'])->name('public.branding');
    Route::post('/client-portal/auth/login', [ClientPortalController::class, 'login'])->middleware('throttle:login');
    Route::post('/client-portal/auth/register', [ClientPortalController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/client-portal/auth/forgot-password', [ClientPortalController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/client-portal/auth/reset-password', [ClientPortalController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user', fn (Request $request) => $request->user()->load('roles:id,name,slug', 'roles.permissions:id,name,slug'));
        Route::get('/assignees', [AssigneeController::class, 'index']);
        Route::patch('/profile', [UserAccessController::class, 'profile']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/reports', [ReportsController::class, 'index']);
        Route::get('/reports/export', [ReportsController::class, 'export']);
        Route::apiResource('clients', ClientController::class);
        Route::post('/clients/{client}/portal-invite', [ClientPortalController::class, 'invite']);
        Route::apiResource('applications', ApplicationController::class);
        Route::apiResource('follow-ups', FollowUpController::class)->parameters(['follow-ups' => 'followUp']);
        Route::apiResource('payments', PaymentController::class);
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::apiResource('documents', DocumentController::class);
        Route::apiResource('services', ServiceController::class);
        Route::get('/users', [UserAccessController::class, 'index']);
        Route::get('/settings/email', [EmailSettingsController::class, 'index']);
        Route::get('/settings/branding', [BrandingSettingsController::class, 'show']);
        Route::post('/settings/branding', [BrandingSettingsController::class, 'update']);
        Route::put('/settings/email', [EmailSettingsController::class, 'update']);
        Route::post('/settings/email/test', [EmailSettingsController::class, 'test']);
        Route::post('/users', [UserAccessController::class, 'store']);
        Route::get('/roles', [UserAccessController::class, 'roleCatalog']);
        Route::patch('/users/{user}/status', [UserAccessController::class, 'status']);
        Route::put('/users/{user}/roles', [UserAccessController::class, 'roles']);
    });
    Route::middleware('auth:client_portal')->prefix('client-portal')->group(function () {
        Route::post('/auth/logout', [ClientPortalController::class, 'logout']);
        Route::get('/user', [ClientPortalController::class, 'user']);
        Route::get('/dashboard', [ClientPortalController::class, 'dashboard']);
        Route::post('/applications/intake', [PublicIntakeController::class, 'storeForClient'])->middleware('throttle:5,1');
        Route::post('/documents', [ClientPortalController::class, 'uploadDocument']);
        Route::get('/documents/{document}/download', [ClientPortalController::class, 'downloadDocument']);
    });
});
