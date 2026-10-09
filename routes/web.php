<?php

use App\Http\Controllers\Api\CompanyApiKeyController;
use App\Http\Controllers\Web\CompanySettingsController;
use App\Http\Controllers\Web\PortalController;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/boletas/manual', function () {
    return view('boletas.manual');
})->name('boletas.manual');

Route::get('/login', [PortalController::class, 'showLogin'])->name('login');
Route::post('/login', [PortalController::class, 'login'])->middleware('throttle:8,1')->name('login.store');
Route::post('/logout', [PortalController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', ResolveTenantContext::class])->group(function () {
    Route::get('/portal', [PortalController::class, 'home'])->name('portal.home');
    Route::get('/admin/companies', [PortalController::class, 'companies'])->name('portal.admin.companies');

    Route::get('/portal/company', [CompanySettingsController::class, 'editOwn'])->name('portal.company.settings');
    Route::put('/portal/company', [CompanySettingsController::class, 'updateOwn'])->name('portal.company.settings.update');

    Route::get('/admin/companies/{company}/settings', [CompanySettingsController::class, 'editAdmin'])->name('portal.admin.company.settings');
    Route::put('/admin/companies/{company}/settings', [CompanySettingsController::class, 'updateAdmin'])->name('portal.admin.company.settings.update');

    // One-time key issuance is returned as JSON for the portal's create/revoke controls.
    Route::get('/portal/companies/{company}/api-keys', [CompanyApiKeyController::class, 'index'])->name('portal.company.api-keys');
    Route::post('/portal/companies/{company}/api-keys', [CompanyApiKeyController::class, 'store'])->name('portal.company.api-keys.store');
    Route::delete('/portal/companies/{company}/api-keys/{apiKey}', [CompanyApiKeyController::class, 'destroy'])->name('portal.company.api-keys.destroy');
});
