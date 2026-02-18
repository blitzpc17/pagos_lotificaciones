<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AjaxLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\AuditoriaController;


Route::get('/login', [AjaxLoginController::class, 'show'])->name('login');
Route::post('/login', [AjaxLoginController::class, 'login'])->name('login.ajax');
Route::post('/logout', [AjaxLoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== Usuarios =====   
    Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/{usuario}', [UsuariosController::class, 'show'])->name('usuarios.show');
    Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{usuario}', [UsuariosController::class, 'update'])->name('usuarios.update');
    Route::post('/usuarios/{usuario}/baja', [UsuariosController::class, 'baja'])->name('usuarios.baja');


    // ===== ROLES =====
    Route::get('/roles', [RolesController::class, 'index'])->name('roles.index'); // view o JSON
    Route::get('/roles/{role}', [RolesController::class, 'show'])->name('roles.show');
    Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RolesController::class, 'update'])->name('roles.update');
    Route::post('/roles/{role}/baja', [RolesController::class, 'baja'])->name('roles.baja');

    // ===== AUDITORÍA (SOLO CONSULTA) =====
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index'); // view o JSON


});
