<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('login.throttle');

Route::middleware('admin.auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('employees.index');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::put('/employees', [EmployeeController::class, 'bulkUpdate'])->name('employees.bulk-update');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/ajax/categories', [TaskController::class, 'categories'])->name('tasks.ajax.categories');
    Route::get('/tasks/ajax/issues', [TaskController::class, 'searchIssues'])->name('tasks.ajax.issues');
    Route::post('/tasks/{issue}/recommendations', [TaskController::class, 'recommendations'])->name('tasks.recommendations');
    Route::post('/tasks/{issue}/assign', [TaskController::class, 'assign'])->name('tasks.assign');

    Route::middleware('admin.role')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
