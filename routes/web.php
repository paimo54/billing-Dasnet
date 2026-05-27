<?php

use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\MitraReportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\CoverageAreaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/landing');

Auth::routes();

/*
|--------------------------------------------------------------------------
| Landing Page Routes (Public)
|--------------------------------------------------------------------------
*/
Route::name('landing.')->group(function () {
    Route::get('/landing', [LandingController::class, 'index'])->name('index');
    Route::get('/coverage', [LandingController::class, 'coverage'])->name('coverage');
    Route::get('/packages', [LandingController::class, 'packages'])->name('packages');
    Route::get('/about', [LandingController::class, 'about'])->name('about');
    Route::get('/register', [LandingController::class, 'register'])->name('register');
    Route::post('/register', [LandingController::class, 'submitRegistration'])->name('register.submit');
    Route::post('/check-coverage', [LandingController::class, 'checkCoverage'])->name('check-coverage');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Payment Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/invoice/{invoice}', [PaymentController::class, 'show'])->name('show');
    Route::get('/success', [PaymentController::class, 'success'])->name('success');
    Route::get('/failed', [PaymentController::class, 'failed'])->name('failed');
});

// Route untuk SuperAdmin
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:superadmin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    
    // Manajemen Admin
    Route::get('/admins', [SuperAdminController::class, 'adminIndex'])->name('admins.index');
    Route::get('/admins/create', [SuperAdminController::class, 'adminCreate'])->name('admins.create');
    Route::post('/admins', [SuperAdminController::class, 'adminStore'])->name('admins.store');
    Route::get('/admins/{admin}/edit', [SuperAdminController::class, 'adminEdit'])->name('admins.edit');
    Route::put('/admins/{admin}', [SuperAdminController::class, 'adminUpdate'])->name('admins.update');
    Route::delete('/admins/{admin}', [SuperAdminController::class, 'adminDestroy'])->name('admins.destroy');
    
    // Manajemen Teknisi
    Route::get('/technicians', [SuperAdminController::class, 'technicianIndex'])->name('technicians.index');
    Route::get('/technicians/create', [SuperAdminController::class, 'technicianCreate'])->name('technicians.create');
    Route::post('/technicians', [SuperAdminController::class, 'technicianStore'])->name('technicians.store');
    Route::get('/technicians/{technician}/edit', [SuperAdminController::class, 'technicianEdit'])->name('technicians.edit');
    Route::put('/technicians/{technician}', [SuperAdminController::class, 'technicianUpdate'])->name('technicians.update');
    Route::delete('/technicians/{technician}', [SuperAdminController::class, 'technicianDestroy'])->name('technicians.destroy');
    
    // Manajemen Paket Internet
    Route::get('/packages', [SuperAdminController::class, 'packageIndex'])->name('packages.index');
    Route::get('/packages/create', [SuperAdminController::class, 'packageCreate'])->name('packages.create');
    Route::post('/packages', [SuperAdminController::class, 'packageStore'])->name('packages.store');
    Route::get('/packages/{package}/edit', [SuperAdminController::class, 'packageEdit'])->name('packages.edit');
    Route::put('/packages/{package}', [SuperAdminController::class, 'packageUpdate'])->name('packages.update');
    Route::delete('/packages/{package}', [SuperAdminController::class, 'packageDestroy'])->name('packages.destroy');
    
    // Manajemen Invoice
    Route::get('/invoices', [SuperAdminController::class, 'invoiceIndex'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [SuperAdminController::class, 'invoiceShow'])->name('invoices.show');
    Route::get('/invoices/{invoice}/print', [SuperAdminController::class, 'invoicePrint'])->name('invoices.print');
    Route::delete('/invoices/{invoice}', [SuperAdminController::class, 'invoiceDestroy'])->name('invoices.destroy');
    
    // Laporan Keuangan
    Route::get('/financial/report', [SuperAdminController::class, 'financialReport'])->name('financial.report');
    Route::get('/financial/report/print', [SuperAdminController::class, 'financialReportPrint'])->name('financial.report.print');
    
    // Manajemen Pelanggan
    Route::get('/customers', [SuperAdminController::class, 'customerIndex'])->name('customers.index');
    Route::get('/customers/{customer}', [SuperAdminController::class, 'customerShow'])->name('customers.show');
    Route::delete('/customers/{customer}', [SuperAdminController::class, 'customerDestroy'])->name('customers.destroy');

    // Manajemen Router
    Route::resource('routers', RouterController::class);
    Route::post('/routers/check-health', [RouterController::class, 'checkHealth'])->name('routers.check-health');
    Route::post('/routers/{router}/check-health', [RouterController::class, 'checkRouterHealth'])->name('routers.check-router-health');
    Route::get('/routers/statistics', [RouterController::class, 'statistics'])->name('routers.statistics');
    Route::post('/routers/{router}/maintenance', [RouterController::class, 'setMaintenance'])->name('routers.maintenance');
    Route::post('/routers/{router}/activate', [RouterController::class, 'activate'])->name('routers.activate');

    // Manajemen Coverage Area
    Route::resource('coverage-areas', CoverageAreaController::class);
    Route::get('/coverage-areas/geojson', [CoverageAreaController::class, 'geojson'])->name('coverage-areas.geojson');
    Route::get('/coverage-areas/by-region', [CoverageAreaController::class, 'byRegion'])->name('coverage-areas.by-region');
});

// Route untuk Admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Manajemen Teknisi
    Route::get('/technicians', [AdminController::class, 'technicianIndex'])->name('technicians.index');
    Route::get('/technicians/create', [AdminController::class, 'technicianCreate'])->name('technicians.create');
    Route::post('/technicians', [AdminController::class, 'technicianStore'])->name('technicians.store');
    Route::get('/technicians/{technician}/edit', [AdminController::class, 'technicianEdit'])->name('technicians.edit');
    Route::put('/technicians/{technician}', [AdminController::class, 'technicianUpdate'])->name('technicians.update');
    Route::delete('/technicians/{technician}', [AdminController::class, 'technicianDestroy'])->name('technicians.destroy');
    
    // Manajemen Paket (hanya untuk edit harga)
    Route::get('/packages', [AdminController::class, 'packageIndex'])->name('packages.index');
    Route::get('/packages/{package}/edit', [AdminController::class, 'packageEdit'])->name('packages.edit');
    Route::put('/packages/{package}/price', [AdminController::class, 'packageUpdatePrice'])->name('packages.update.price');
    
    // Manajemen Invoice
    Route::get('/invoices', [AdminController::class, 'invoiceIndex'])->name('invoices.index');
    Route::get('/invoices/create', [AdminController::class, 'invoiceCreate'])->name('invoices.create');
    Route::post('/invoices', [AdminController::class, 'invoiceStore'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [AdminController::class, 'invoiceShow'])->name('invoices.show');
    Route::get('/invoices/{invoice}/print', [AdminController::class, 'invoicePrint'])->name('invoices.print');
    Route::put('/invoices/{invoice}/status', [AdminController::class, 'invoiceUpdateStatus'])->name('invoices.update.status');
    Route::put('/invoices/{invoice}/reset-print-status', [AdminController::class, 'invoiceResetPrintStatus'])->name('invoices.reset.print.status');
    
    // Laporan Keuangan
    Route::get('/financial/report', [AdminController::class, 'financialReport'])->name('financial.report');
    Route::get('/financial/report/print', [AdminController::class, 'financialReportPrint'])->name('financial.report.print');
    Route::post('/financial/report/reset-print-status', [MitraReportController::class, 'resetPrintStatus'])->name('financial.report.reset.print.status');
    Route::post('/financial/report/update-payment-status', [MitraReportController::class, 'updatePaymentStatus'])->name('financial.report.update.payment.status');
    
    // Manajemen Pelanggan
    Route::get('/customers', [AdminController::class, 'customerIndex'])->name('customers.index');
    Route::get('/customers/create', [AdminController::class, 'customerCreate'])->name('customers.create');
    Route::post('/customers', [AdminController::class, 'customerStore'])->name('customers.store');
    Route::get('/customers/{customer}', [AdminController::class, 'customerShow'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [AdminController::class, 'customerEdit'])->name('customers.edit');
    Route::put('/customers/{customer}', [AdminController::class, 'customerUpdate'])->name('customers.update');
    Route::delete('/customers/{customer}', [AdminController::class, 'customerDestroy'])->name('customers.destroy');
    
    // Export customers
    Route::post('/customers/export', [AdminController::class, 'exportCustomers'])->name('customers.export');

    // Manajemen Router
    Route::resource('routers', RouterController::class);
    Route::post('/routers/check-health', [RouterController::class, 'checkHealth'])->name('routers.check-health');
    Route::post('/routers/{router}/check-health', [RouterController::class, 'checkRouterHealth'])->name('routers.check-router-health');
    Route::get('/routers/statistics', [RouterController::class, 'statistics'])->name('routers.statistics');
    Route::post('/routers/{router}/maintenance', [RouterController::class, 'setMaintenance'])->name('routers.maintenance');
    Route::post('/routers/{router}/activate', [RouterController::class, 'activate'])->name('routers.activate');

    // Manajemen Coverage Area
    Route::resource('coverage-areas', CoverageAreaController::class);
    Route::get('/coverage-areas/geojson', [CoverageAreaController::class, 'geojson'])->name('coverage-areas.geojson');
    Route::get('/coverage-areas/by-region', [CoverageAreaController::class, 'byRegion'])->name('coverage-areas.by-region');

    // WhatsApp Gateway
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        // Templates
        Route::get('/templates', [\App\Http\Controllers\WhatsappTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [\App\Http\Controllers\WhatsappTemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [\App\Http\Controllers\WhatsappTemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}', [\App\Http\Controllers\WhatsappTemplateController::class, 'show'])->name('templates.show');
        Route::get('/templates/{template}/edit', [\App\Http\Controllers\WhatsappTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{template}', [\App\Http\Controllers\WhatsappTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [\App\Http\Controllers\WhatsappTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('/templates/{template}/toggle', [\App\Http\Controllers\WhatsappTemplateController::class, 'toggleActive'])->name('templates.toggle');

        // Messages
        Route::get('/messages', [\App\Http\Controllers\WhatsappMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [\App\Http\Controllers\WhatsappMessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/send', [\App\Http\Controllers\WhatsappMessageController::class, 'send'])->name('messages.send');
        Route::post('/messages/send-direct', [\App\Http\Controllers\WhatsappMessageController::class, 'sendDirect'])->name('messages.send-direct');
        Route::post('/messages/bulk-send', [\App\Http\Controllers\WhatsappMessageController::class, 'bulkSend'])->name('messages.bulk-send');
        Route::post('/messages/{message}/resend', [\App\Http\Controllers\WhatsappMessageController::class, 'resend'])->name('messages.resend');
        Route::delete('/messages/{message}', [\App\Http\Controllers\WhatsappMessageController::class, 'destroy'])->name('messages.destroy');

        // Providers
        Route::get('/providers', [\App\Http\Controllers\WhatsappProviderController::class, 'index'])->name('providers.index');
        Route::get('/providers/create', [\App\Http\Controllers\WhatsappProviderController::class, 'create'])->name('providers.create');
        Route::post('/providers', [\App\Http\Controllers\WhatsappProviderController::class, 'store'])->name('providers.store');
        Route::get('/providers/{provider}', [\App\Http\Controllers\WhatsappProviderController::class, 'show'])->name('providers.show');
        Route::get('/providers/{provider}/edit', [\App\Http\Controllers\WhatsappProviderController::class, 'edit'])->name('providers.edit');
        Route::put('/providers/{provider}', [\App\Http\Controllers\WhatsappProviderController::class, 'update'])->name('providers.update');
        Route::delete('/providers/{provider}', [\App\Http\Controllers\WhatsappProviderController::class, 'destroy'])->name('providers.destroy');
        Route::post('/providers/{provider}/toggle', [\App\Http\Controllers\WhatsappProviderController::class, 'toggleActive'])->name('providers.toggle');
        Route::post('/providers/{provider}/set-default', [\App\Http\Controllers\WhatsappProviderController::class, 'setDefault'])->name('providers.set-default');
        Route::post('/providers/{provider}/reset-counter', [\App\Http\Controllers\WhatsappProviderController::class, 'resetCounter'])->name('providers.reset-counter');
        Route::post('/providers/{provider}/test', [\App\Http\Controllers\WhatsappProviderController::class, 'testConnection'])->name('providers.test');
    });
});

// Route untuk Teknisi
Route::prefix('technician')->name('technician.')->middleware(['auth', 'role:technician'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [TechnicianController::class, 'dashboard'])->name('dashboard');
    
    // Manajemen Pelanggan
    Route::get('/customers', [TechnicianController::class, 'customerIndex'])->name('customers.index');
    Route::get('/customers/create', [TechnicianController::class, 'customerCreate'])->name('customers.create');
    Route::post('/customers', [TechnicianController::class, 'customerStore'])->name('customers.store');
    Route::get('/customers/{customer}', [TechnicianController::class, 'customerShow'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [TechnicianController::class, 'customerEdit'])->name('customers.edit');
    Route::put('/customers/{customer}', [TechnicianController::class, 'customerUpdate'])->name('customers.update');
    Route::delete('/customers/{customer}', [TechnicianController::class, 'customerDestroy'])->name('customers.destroy');
    
    // Laporan Keuangan Teknisi
    Route::get('/financial/report', [TechnicianController::class, 'financialReport'])->name('financial.report');

    // Print Invoice Teknisi
    Route::get('/invoices/{invoice}/print', [TechnicianController::class, 'invoicePrint'])->name('invoices.print');
    // Tambahkan route untuk update status lunas/belum lunas
    Route::put('/invoices/{invoice}/status', [TechnicianController::class, 'invoiceUpdateStatus'])->name('invoices.update.status');
});

Route::post('technician/customers/import', [App\Http\Controllers\TechnicianController::class, 'import'])->name('technician.customers.import');
