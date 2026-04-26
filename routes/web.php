<?php

declare(strict_types=1);

use App\Auth\Controllers\EmailVerificationNoticeController;
use App\Auth\Controllers\EmailVerificationResendController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\NewPasswordController;
use App\Auth\Controllers\PasswordResetLinkController;
use App\Auth\Controllers\RegisterController;
use App\Auth\Controllers\SocialAuthController;
use App\Auth\Controllers\VerifyEmailController;
use App\Billing\Controllers\BillingPortalController;
use App\Billing\Controllers\BuyCancelController;
use App\Billing\Controllers\BuyController;
use App\Billing\Controllers\BuySuccessController;
use App\Billing\Controllers\StripeWebhookController;
use App\Character\Controllers\CharacterController;
use App\Chat\Controllers\ChatController;
use App\Chat\Controllers\MessageController;
use App\Chat\Controllers\MessageStreamController;
use App\Home\Controllers\HomeController;
use App\User\Controllers\PasswordController;
use App\User\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');

    Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('auth.social');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', [EmailVerificationNoticeController::class, 'show'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/verify-email/resend', [EmailVerificationResendController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/me', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('/me', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/me', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::patch('/me/password', [PasswordController::class, 'update'])->name('password.update');
        Route::get('/me/limits', [ProfileController::class, 'limits'])->name('profile.limits');
        Route::get('/me/billing', BillingPortalController::class)->name('billing.portal');

        Route::get('/characters/create', [CharacterController::class, 'create'])->name('character.create');
        Route::post('/characters', [CharacterController::class, 'store'])->name('character.store');

        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
        Route::get('/chat/{chat}', [ChatController::class, 'show'])->name('chat.show');

        Route::post('/chat/{chat}/messages', [MessageController::class, 'store'])->name('message.store');
        Route::get('/chat/{chat}/messages/stream', MessageStreamController::class)->name('message.stream');

        Route::get('/buy', [BuyController::class, 'index'])->name('buy.index');
        Route::post('/buy/{package}', [BuyController::class, 'store'])->name('buy.store');
        Route::get('/buy/success', BuySuccessController::class)->name('buy.success');
        Route::get('/buy/cancel', BuyCancelController::class)->name('buy.cancel');
    });
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('cashier.webhook');
