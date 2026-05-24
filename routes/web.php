<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login');

Route::middleware('admin.auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('employees.index');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks/{issue}/recommendations', [TaskController::class, 'recommendations'])->name('tasks.recommendations');
    Route::post('/tasks/{issue}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
});
