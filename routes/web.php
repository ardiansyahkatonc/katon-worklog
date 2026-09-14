<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $tasks = auth()->user()->tasks();

        return view('dashboard', [
            'total' => (clone $tasks)->count(),
            'notStarted' => (clone $tasks)->where('status', 'belum dimulai')->count(),
            'inProgress' => (clone $tasks)->where('status', 'dikerjakan')->count(),
            'completed' => (clone $tasks)->where('status', 'selesai')->count(),
        ]);
    })->name('dashboard');

    Route::resource('tasks', TaskController::class);

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
