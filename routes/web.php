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


    Route::group(['prefix' => 'user', 'name' => 'user'], function () {
        Route::get('admin-list', [UserController::class, 'indexAdmin'])->name('admin-list');
        Route::get('admin-form', [UserController::class, 'createAdmin'])->name('admin-form');
        Route::get('user-list', [UserController::class, 'indexUser'])->name('user-list');
        Route::get('public-account-list', [UserController::class, 'indexPublicUser'])->name('public-account-list');
        Route::post('update-status/{id}', [UserController::class, 'updateActiveStatus'])->name('update-active-status');
        Route::post('create-admin', [UserController::class, 'store'])->name('create-admin');
        Route::post('delete/{id}', [UserController::class, 'destroy'])->name('delete-user');
    });

    Route::get('app-setting', [SettingController::class, 'index'])->name('app-setting');
});

require __DIR__.'/auth.php';
