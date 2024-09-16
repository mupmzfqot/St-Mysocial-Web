<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('home');

Route::get('registration-success', [UserController::class, 'registrationSuccess'])
    ->middleware('auth')->name('registration-success');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::name('admin.')->prefix('admin')->group(function () {
        Route::get('/', [UserController::class, 'adminIndex'])->name('index');
        Route::get('form/{id?}', [UserController::class, 'adminForm'])->name('form');
        Route::post('store', [UserController::class, 'store'])->name('store');
        Route::post('update-status/{id}', [UserController::class, 'updateActiveStatus'])->name('update-status');
    });

    Route::name('user.')->prefix('user')->group(function () {
        Route::get('/get', [UserController::class, 'get'])->name('get');
        Route::get('/', [UserController::class, 'userIndex'])->name('index');
        Route::get('public-account', [UserController::class, 'publicAccountIndex'])->name('public');
        Route::post('delete/{id}', [UserController::class, 'destroy'])->name('delete');
        Route::get('profile/{id?}', [ProfileController::class, 'index'])->name('profile');
        Route::post('set-admin/{id}', [UserController::class, 'setAsAdmin'])->name('set-admin');
        Route::post('reset-password/{id}', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('verify/{id}', [UserController::class, 'verifyAccount'])->name('verify');
    });


    Route::get('app-setting', [SettingController::class, 'index'])->name('app-setting');
});

require __DIR__.'/auth.php';
