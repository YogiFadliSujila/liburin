<?php

use App\Http\Controllers\DestinationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripMemberController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('trips.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Trip routes
    Route::resource('trips', TripController::class);

    // Trip Members routes
    Route::prefix('trips/{trip}/members')->name('trips.members.')->group(function () {
        Route::get('/', [TripMemberController::class, 'index'])->name('index');
        Route::post('/', [TripMemberController::class, 'store'])->name('store');
        Route::post('/accept', [TripMemberController::class, 'accept'])->name('accept');
        Route::post('/decline', [TripMemberController::class, 'decline'])->name('decline');
        Route::post('/leave', [TripMemberController::class, 'leave'])->name('leave');
        Route::patch('/{member}', [TripMemberController::class, 'update'])->name('update');
        Route::delete('/{member}', [TripMemberController::class, 'destroy'])->name('destroy');
    });

    // Destinations routes
    Route::prefix('trips/{trip}/destinations')->name('trips.destinations.')->group(function () {
        Route::get('/', [DestinationController::class, 'index'])->name('index');
        Route::post('/', [DestinationController::class, 'store'])->name('store');
        Route::post('/reorder', [DestinationController::class, 'reorder'])->name('reorder');
        Route::patch('/{destination}', [DestinationController::class, 'update'])->name('update');
        Route::delete('/{destination}', [DestinationController::class, 'destroy'])->name('destroy');
    });

    // Savings/Payment routes
    Route::prefix('trips/{trip}/savings')->name('trips.savings.')->group(function () {
        Route::get('/', [SavingsController::class, 'index'])->name('index');
        Route::get('/create', [SavingsController::class, 'create'])->name('create');
        Route::post('/', [SavingsController::class, 'store'])->name('store');
        Route::get('/{savings}', [SavingsController::class, 'show'])->name('show');
        Route::post('/{savings}/approve', [SavingsController::class, 'approve'])->name('approve');
        Route::get('/{savings}/status', [SavingsController::class, 'checkStatus'])->name('status');
        Route::delete('/{savings}', [SavingsController::class, 'destroy'])->name('destroy');
    });

    // Expenses routes
    Route::prefix('trips/{trip}/expenses')->name('trips.expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::patch('/{expense}', [ExpenseController::class, 'update'])->name('update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
    });

    // Withdrawals routes
    Route::prefix('trips/{trip}/withdrawals')->name('trips.withdrawals.')->group(function () {
        Route::get('/', [WithdrawalController::class, 'index'])->name('index');
        Route::post('/', [WithdrawalController::class, 'store'])->name('store');
        Route::post('/{withdrawal}/vote', [WithdrawalController::class, 'vote'])->name('vote');
        Route::post('/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->name('complete');
        Route::delete('/{withdrawal}', [WithdrawalController::class, 'destroy'])->name('destroy');
    });
});

// API routes for webhooks (no CSRF)
Route::post('/api/midtrans/webhook', [WebhookController::class, 'midtrans'])
    ->withoutMiddleware(['web'])
    ->name('webhook.midtrans');

require __DIR__.'/auth.php';



