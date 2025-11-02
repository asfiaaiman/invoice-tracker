<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('agencies', \App\Http\Controllers\AgencyController::class);
    Route::post('agencies/{agency}/toggle-status', [\App\Http\Controllers\AgencyController::class, 'toggleStatus'])->name('agencies.toggle-status');
    Route::resource('clients', \App\Http\Controllers\ClientController::class);
    Route::resource('products', \App\Http\Controllers\ProductController::class);
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    
    Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/period', [\App\Http\Controllers\ReportController::class, 'period'])->name('reports.period');
    Route::get('reports/{agency}', [\App\Http\Controllers\ReportController::class, 'show'])->name('reports.show');
    
    Route::get('settings/application', [\App\Http\Controllers\Settings\ApplicationSettingsController::class, 'index'])->name('settings.application');
    Route::post('settings/application', [\App\Http\Controllers\Settings\ApplicationSettingsController::class, 'update'])->name('settings.application.update');
    
    Route::get('agencies/{agency}/settings', [\App\Http\Controllers\Settings\AgencySettingsController::class, 'index'])->name('agencies.settings');
    Route::post('agencies/{agency}/settings', [\App\Http\Controllers\Settings\AgencySettingsController::class, 'update'])->name('agencies.settings.update');
    
    Route::get('api/clients', function (\Illuminate\Http\Request $request) {
        $agencyId = $request->get('agency_id');
        if ($agencyId) {
            return \App\Models\Client::whereHas('agencies', function ($q) use ($agencyId) {
                $q->where('agencies.id', $agencyId)
                  ->where('agencies.is_active', true);
            })->get();
        }
        return \App\Models\Client::all();
    })->name('api.clients');
    
    Route::get('api/products', function (\Illuminate\Http\Request $request) {
        $agencyId = $request->get('agency_id');
        if ($agencyId) {
            $products = \App\Models\Product::whereHas('agencies', function ($q) use ($agencyId) {
                $q->where('agencies.id', $agencyId)
                  ->where('agencies.is_active', true);
            })->with(['agencies' => function ($q) use ($agencyId) {
                $q->where('agencies.id', $agencyId);
            }])->get();
            
            return $products->map(function ($product) use ($agencyId) {
                $agencyPivot = $product->agencies->firstWhere('id', $agencyId);
                $price = $agencyPivot?->pivot->price ?? $product->price;
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'description' => $product->description,
                    'price' => (float) $price,
                    'unit' => $product->unit,
                ];
            });
        }
        return \App\Models\Product::all();
    })->name('api.products');
});

require __DIR__.'/settings.php';
