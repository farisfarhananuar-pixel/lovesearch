<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

/*
|--------------------------------------------------------------------------
| User: Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| User: App (perlu login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/match/search', [MatchController::class, 'search'])->name('match.search');
    Route::get('/match/waiting', [MatchController::class, 'waiting'])->name('match.waiting');
    Route::get('/match/waiting/poll', [MatchController::class, 'waitingPoll'])->name('match.waiting.poll');
    Route::post('/match/waiting/cancel', [MatchController::class, 'cancelWaiting'])->name('match.waiting.cancel');

    Route::get('/match/{match}', [MatchController::class, 'show'])->name('match.show');
    Route::post('/match/{match}/message', [MatchController::class, 'sendMessage'])->name('match.message');
    Route::get('/match/{match}/poll', [MatchController::class, 'poll'])->name('match.poll');
    Route::post('/match/{match}/love', [MatchController::class, 'love'])->name('match.love');
    Route::post('/match/{match}/leave', [MatchController::class, 'leave'])->name('match.leave');

    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::post('/payment', [PaymentController::class, 'submit'])->name('payment.submit');
    Route::get('/payment/history', [PaymentController::class, 'history'])->name('payment.history');
});

/*
|--------------------------------------------------------------------------
| Admin (developer)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Login admin sekarang guna page /login yang sama dengan user (lihat AuthController::login).
    // Route /admin/login dikekalkan sebagai redirect untuk bookmark lama.
    Route::get('/login', function () {
        return redirect()->route('login');
    })->name('login');

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments');
        Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');

        Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings');
        Route::post('/settings/qr', [AdminSettingsController::class, 'updateQr'])->name('settings.qr');
        Route::post('/settings/password', [AdminSettingsController::class, 'updatePassword'])->name('settings.password');
    });
});
