<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChessController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\UnoMultiplayerController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
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
    Route::post('/match/{match}/block', [MatchController::class, 'block'])->name('match.block');
    Route::post('/match/{match}/unblock', [MatchController::class, 'unblock'])->name('match.unblock');
    Route::post('/match/{match}/unfriend', [MatchController::class, 'unfriend'])->name('match.unfriend');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/game', [GameController::class, 'hub'])->name('game.hub');
    Route::get('/game/uno', [GameController::class, 'unoMenu'])->name('game.uno.menu');
    Route::get('/game/uno/solo', [GameController::class, 'unoSolo'])->name('game.uno.solo');

    Route::post('/game/uno/rooms', [UnoMultiplayerController::class, 'store'])->name('game.uno.store');
    Route::get('/game/uno/rooms/{room}', [UnoMultiplayerController::class, 'show'])->name('game.uno.room');
    Route::get('/game/uno/rooms/{room}/poll', [UnoMultiplayerController::class, 'poll'])->name('game.uno.poll');
    Route::post('/game/uno/rooms/{room}/join', [UnoMultiplayerController::class, 'join'])->name('game.uno.join');
    Route::post('/game/uno/rooms/{room}/decline', [UnoMultiplayerController::class, 'decline'])->name('game.uno.decline');
    Route::post('/game/uno/rooms/{room}/leave', [UnoMultiplayerController::class, 'leave'])->name('game.uno.leave');
    Route::post('/game/uno/rooms/{room}/start', [UnoMultiplayerController::class, 'start'])->name('game.uno.start');
    Route::post('/game/uno/rooms/{room}/play', [UnoMultiplayerController::class, 'play'])->name('game.uno.play');
    Route::post('/game/uno/rooms/{room}/draw', [UnoMultiplayerController::class, 'draw'])->name('game.uno.draw');
    Route::post('/game/uno/rooms/{room}/pass', [UnoMultiplayerController::class, 'pass'])->name('game.uno.pass');
    Route::post('/game/uno/rooms/{room}/call-uno', [UnoMultiplayerController::class, 'callUno'])->name('game.uno.call-uno');

    // Chess
    Route::get('/game/chess', [ChessController::class, 'menu'])->name('game.chess.menu');
    Route::post('/game/chess/solo', [ChessController::class, 'startSolo'])->name('game.chess.solo');
    Route::post('/game/chess/invite/{targetUser}', [ChessController::class, 'invite'])->name('game.chess.invite');
    Route::get('/game/chess/rooms/{room}', [ChessController::class, 'show'])->name('game.chess.show');
    Route::post('/game/chess/rooms/{room}/accept', [ChessController::class, 'accept'])->name('game.chess.accept');
    Route::post('/game/chess/rooms/{room}/decline', [ChessController::class, 'decline'])->name('game.chess.decline');
    Route::get('/game/chess/rooms/{room}/poll', [ChessController::class, 'poll'])->name('game.chess.poll');
    Route::post('/game/chess/rooms/{room}/legal-moves', [ChessController::class, 'legalMoves'])->name('game.chess.legal-moves');
    Route::post('/game/chess/rooms/{room}/move', [ChessController::class, 'move'])->name('game.chess.move');
    Route::post('/game/chess/rooms/{room}/resign', [ChessController::class, 'resign'])->name('game.chess.resign');

    // Kawan / Contacts
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::get('/friends/search', [FriendController::class, 'searchForm'])->name('friends.search');
    Route::post('/friends/search', [FriendController::class, 'search'])->name('friends.search.submit');
    Route::post('/friends/request/{targetUser}', [FriendController::class, 'sendRequest'])->name('friends.request.send');
    Route::post('/friends/request/{friendRequest}/cancel', [FriendController::class, 'cancelSentRequest'])->name('friends.request.cancel');
    Route::post('/friends/request/{friendRequest}/accept', [FriendController::class, 'accept'])->name('friends.request.accept');
    Route::post('/friends/request/{friendRequest}/decline', [FriendController::class, 'decline'])->name('friends.request.decline');

    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo/remove', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');

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
    // Page login admin yang berasingan (ada butang "Log Masuk sebagai Admin" di page login user).
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    });

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
