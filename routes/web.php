<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationNoticeController;
use App\Http\Controllers\Auth\EmailVerificationResendController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

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
    });
});
