<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AjaxLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\SociosController;
use App\Http\Controllers\EmpleadosController;
use App\Http\Controllers\VendedoresController;
use App\Http\Controllers\ModulosController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\AutorizacionesController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\PersonaContactosController;

/**
 * AUTH (login / logout)
 * Nota: La auditoría de LOGIN/LOGOUT conviene hacerla dentro del AjaxLoginController
 * con AuditService::log(...) para que no dependa de module:/ruta.
 */
Route::get('/login', [AjaxLoginController::class, 'show'])->name('login');
Route::post('/login', [AjaxLoginController::class, 'login'])->name('login.ajax');
Route::post('/logout', [AjaxLoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));

    // =========================
    // DASHBOARD
    // =========================
    Route::middleware(['module:/dashboard','action:ver','audit:VER'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // =========================
    // ROLES
    // =========================
    Route::middleware(['module:/roles','action:ver','audit:VER'])->group(function () {
        Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}', [RolesController::class, 'show'])->name('roles.show');
    });

    Route::middleware(['module:/roles','action:crear','audit:CREAR'])->group(function () {
        Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    });

    Route::middleware(['module:/roles','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::put('/roles/{role}', [RolesController::class, 'update'])->name('roles.update');
    });

    Route::middleware(['module:/roles','action:baja','audit:BAJA'])->group(function () {
        Route::post('/roles/{role}/baja', [RolesController::class, 'baja'])->name('roles.baja');
    });

    // =========================
    // EMPLEADOS
    // =========================
    Route::middleware(['module:/empleados','action:ver','audit:VER'])->group(function () {
        Route::get('/empleados', [EmpleadosController::class, 'index'])->name('empleados.index');
        Route::get('/empleados/datatable', [EmpleadosController::class, 'datatable'])->name('empleados.datatable');
        Route::get('/empleados/{id}', [EmpleadosController::class, 'show'])->name('empleados.show');

        // contactos (ver)
        Route::get('/empleados/{id}/contactos', [PersonaContactosController::class, 'contactosByOwner'])
            ->defaults('owner','empleados')
            ->name('empleados.contactos');
    });

    Route::middleware(['module:/empleados','action:crear','audit:CREAR'])->group(function () {
        Route::post('/empleados', [EmpleadosController::class, 'store'])->name('empleados.store');

        // contactos (crear)
        Route::post('/empleados/{id}/telefonos', [PersonaContactosController::class, 'addTelefono'])
            ->defaults('owner','empleados')
            ->name('empleados.tel.add');
        Route::post('/empleados/{id}/correos', [PersonaContactosController::class, 'addCorreo'])
            ->defaults('owner','empleados')
            ->name('empleados.mail.add');
        Route::post('/empleados/{id}/direcciones', [PersonaContactosController::class, 'addDireccion'])
            ->defaults('owner','empleados')
            ->name('empleados.dir.add');
    });

    Route::middleware(['module:/empleados','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::put('/empleados/{id}', [EmpleadosController::class, 'update'])->name('empleados.update');

        // reactivar contacto (modificar)
        Route::post('/empleados/contacto/{tipo}/{cid}/reactivar', [PersonaContactosController::class, 'reactivarContacto'])
            ->defaults('owner','empleados')
            ->name('empleados.contacto.reactivar');
    });

    Route::middleware(['module:/empleados','action:baja','audit:BAJA'])->group(function () {
        Route::post('/empleados/{id}/baja', [EmpleadosController::class, 'baja'])->name('empleados.baja');

        // baja contacto (baja) - motivo requerido en controller
        Route::post('/empleados/contacto/{tipo}/{cid}/baja', [PersonaContactosController::class, 'bajaContacto'])
            ->defaults('owner','empleados')
            ->name('empleados.contacto.baja');
    });

    // =========================
    // USUARIOS
    // =========================
    Route::middleware(['module:/usuarios','action:ver','audit:VER'])->group(function () {
        Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/datatable', [UsuariosController::class, 'datatable'])->name('usuarios.datatable');
        Route::get('/usuarios/empleados-disponibles', [UsuariosController::class, 'empleadosDisponibles'])->name('usuarios.empleados_disponibles');
        Route::get('/usuarios/{id}', [UsuariosController::class, 'show'])->name('usuarios.show');
    });

    Route::middleware(['module:/usuarios','action:crear','audit:CREAR'])->group(function () {
        Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
    });

    Route::middleware(['module:/usuarios','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::put('/usuarios/{id}', [UsuariosController::class, 'update'])->name('usuarios.update');
    });

    Route::middleware(['module:/usuarios','action:baja','audit:BAJA'])->group(function () {
        Route::post('/usuarios/{id}/baja', [UsuariosController::class, 'baja'])->name('usuarios.baja');
    });

    // =========================
    // SOCIOS
    // =========================
    Route::middleware(['module:/socios','action:ver','audit:VER'])->group(function () {
        Route::get('/socios', [SociosController::class, 'index'])->name('socios.index');
        Route::get('/socios/datatable', [SociosController::class, 'datatable'])->name('socios.datatable');
        Route::get('/socios/{id}', [SociosController::class, 'show'])->name('socios.show');

        // contactos (ver)
        Route::get('/socios/{id}/contactos', [PersonaContactosController::class, 'contactosByOwner'])
            ->defaults('owner','socios')
            ->name('socios.contactos');
    });

    Route::middleware(['module:/socios','action:crear','audit:CREAR'])->group(function () {
        Route::post('/socios', [SociosController::class, 'store'])->name('socios.store');

        // contactos (crear)
        Route::post('/socios/{id}/telefonos', [PersonaContactosController::class, 'addTelefono'])
            ->defaults('owner','socios')
            ->name('socios.tel.add');
        Route::post('/socios/{id}/correos', [PersonaContactosController::class, 'addCorreo'])
            ->defaults('owner','socios')
            ->name('socios.mail.add');
        Route::post('/socios/{id}/direcciones', [PersonaContactosController::class, 'addDireccion'])
            ->defaults('owner','socios')
            ->name('socios.dir.add');
    });

    Route::middleware(['module:/socios','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::put('/socios/{id}', [SociosController::class, 'update'])->name('socios.update');

        Route::post('/socios/contacto/{tipo}/{cid}/reactivar', [PersonaContactosController::class, 'reactivarContacto'])
            ->defaults('owner','socios')
            ->name('socios.contacto.reactivar');
    });

    Route::middleware(['module:/socios','action:baja','audit:BAJA'])->group(function () {
        Route::post('/socios/{id}/baja', [SociosController::class, 'baja'])->name('socios.baja');

        Route::post('/socios/contacto/{tipo}/{cid}/baja', [PersonaContactosController::class, 'bajaContacto'])
            ->defaults('owner','socios')
            ->name('socios.contacto.baja');
    });

    // =========================
    // CLIENTES
    // =========================
    Route::middleware(['module:/clientes','action:ver','audit:VER'])->group(function () {
        Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/datatable', [ClientesController::class, 'datatable'])->name('clientes.datatable');
        Route::get('/clientes/{id}', [ClientesController::class, 'show'])->name('clientes.show');

        // contactos (ver)
        Route::get('/clientes/{id}/contactos', [PersonaContactosController::class, 'contactosByOwner'])
            ->defaults('owner','clientes')
            ->name('clientes.contactos');
    });

    Route::middleware(['module:/clientes','action:crear','audit:CREAR'])->group(function () {
        Route::post('/clientes', [ClientesController::class, 'store'])->name('clientes.store');

        // contactos (crear)
        Route::post('/clientes/{id}/telefonos', [PersonaContactosController::class, 'addTelefono'])
            ->defaults('owner','clientes')
            ->name('clientes.tel.add');
        Route::post('/clientes/{id}/correos', [PersonaContactosController::class, 'addCorreo'])
            ->defaults('owner','clientes')
            ->name('clientes.mail.add');
        Route::post('/clientes/{id}/direcciones', [PersonaContactosController::class, 'addDireccion'])
            ->defaults('owner','clientes')
            ->name('clientes.dir.add');
    });

    Route::middleware(['module:/clientes','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::put('/clientes/{id}', [ClientesController::class, 'update'])->name('clientes.update');

        Route::post('/clientes/contacto/{tipo}/{cid}/reactivar', [PersonaContactosController::class, 'reactivarContacto'])
            ->defaults('owner','clientes')
            ->name('clientes.contacto.reactivar');
    });

    Route::middleware(['module:/clientes','action:baja','audit:BAJA'])->group(function () {
        Route::post('/clientes/{id}/baja', [ClientesController::class, 'baja'])->name('clientes.baja');

        Route::post('/clientes/contacto/{tipo}/{cid}/baja', [PersonaContactosController::class, 'bajaContacto'])
            ->defaults('owner','clientes')
            ->name('clientes.contacto.baja');
    });

    // =========================
    // PROVEEDORES
    // =========================
    Route::middleware(['module:/proveedores','action:ver','audit:VER'])->group(function () {
        Route::get('/proveedores', [ProveedoresController::class, 'index'])->name('proveedores.index');
        Route::get('/proveedores/datatable', [ProveedoresController::class, 'datatable'])->name('proveedores.datatable');
        Route::get('/proveedores/{id}', [ProveedoresController::class, 'show'])->name('proveedores.show');

        // contactos (ver)
        Route::get('/proveedores/{id}/contactos', [PersonaContactosController::class, 'contactosByOwner'])
            ->defaults('owner','proveedores')
            ->name('proveedores.contactos');
    });

    Route::middleware(['module:/proveedores','action:crear','audit:CREAR'])->group(function () {
        Route::post('/proveedores', [ProveedoresController::class, 'store'])->name('proveedores.store');

        // contactos (crear)
        Route::post('/proveedores/{id}/telefonos', [PersonaContactosController::class, 'addTelefono'])
            ->defaults('owner','proveedores')
            ->name('proveedores.tel.add');
        Route::post('/proveedores/{id}/correos', [PersonaContactosController::class, 'addCorreo'])
            ->defaults('owner','proveedores')
            ->name('proveedores.mail.add');
        Route::post('/proveedores/{id}/direcciones', [PersonaContactosController::class, 'addDireccion'])
            ->defaults('owner','proveedores')
            ->name('proveedores.dir.add');
    });

    Route::middleware(['module:/proveedores','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::put('/proveedores/{id}', [ProveedoresController::class, 'update'])->name('proveedores.update');

        Route::post('/proveedores/contacto/{tipo}/{cid}/reactivar', [PersonaContactosController::class, 'reactivarContacto'])
            ->defaults('owner','proveedores')
            ->name('proveedores.contacto.reactivar');
    });

    Route::middleware(['module:/proveedores','action:baja','audit:BAJA'])->group(function () {
        Route::post('/proveedores/{id}/baja', [ProveedoresController::class, 'baja'])->name('proveedores.baja');

        Route::post('/proveedores/contacto/{tipo}/{cid}/baja', [PersonaContactosController::class, 'bajaContacto'])
            ->defaults('owner','proveedores')
            ->name('proveedores.contacto.baja');
    });

    // =========================
    // AUDITORÍA (consulta)
    // =========================
    Route::middleware(['module:/auditoria','action:ver','audit:VER'])->group(function () {
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    });

    // =========================
    // MODULOS
    // =========================
    Route::middleware(['module:/modulos','action:ver','audit:VER'])->group(function () {
        Route::get('/modulos', [ModulosController::class,'index'])->name('modulos.index');

        // ✅ SIEMPRE antes del {modulo}
        Route::get('/modulos/datatable', [ModulosController::class,'datatable'])->name('modulos.datatable');
        Route::get('/modulos/parents', [ModulosController::class,'parents'])->name('modulos.parents');

        Route::get('/modulos/{modulo}', [ModulosController::class,'show'])->name('modulos.show');
    });

    Route::middleware(['module:/modulos','action:crear','audit:CREAR'])->group(function () {
        Route::post('/modulos', [ModulosController::class,'store'])->name('modulos.store');
    });

    Route::middleware(['module:/modulos','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::put('/modulos/{modulo}', [ModulosController::class,'update'])->name('modulos.update');
    });

    Route::middleware(['module:/modulos','action:baja','audit:BAJA'])->group(function () {
        Route::post('/modulos/{modulo}/baja', [ModulosController::class,'baja'])->name('modulos.baja');
    });

    // =========================
    // PERMISOS
    // =========================
    Route::middleware(['module:/permisos','action:ver','audit:VER'])->group(function () {
        Route::get('/permisos', [PermisosController::class,'index'])->name('permisos.index');
        Route::get('/permisos/roles/{rol}', [PermisosController::class,'getRoleModules'])->name('permisos.role_modules');
        Route::get('/permisos/usuarios/{usuario}/acciones', [PermisosController::class,'getUserActions'])->name('permisos.user_actions');
    });

    Route::middleware(['module:/permisos','action:modificar','audit:MODIFICAR'])->group(function () {
        Route::post('/permisos/roles/{rol}', [PermisosController::class,'setRoleModules'])->name('permisos.role_modules_set');
        Route::post('/permisos/usuarios/{usuario}/acciones', [PermisosController::class,'setUserActions'])->name('permisos.user_actions_set');
    });

    // =========================
    // AUTORIZACIONES
    // =========================
    Route::middleware(['module:/autorizaciones','action:ver','audit:VER'])->group(function(){
        Route::get('/autorizaciones', [AutorizacionesController::class,'index'])->name('autorizaciones.index');
        Route::get('/autorizaciones/{solicitud}', [AutorizacionesController::class,'show'])->name('autorizaciones.show');
    });

    Route::middleware(['module:/autorizaciones','action:modificar','audit:MODIFICAR'])->group(function(){
        Route::post('/autorizaciones/{solicitud}/aprobar', [AutorizacionesController::class,'aprobar'])->name('autorizaciones.aprobar');
        Route::post('/autorizaciones/{solicitud}/rechazar', [AutorizacionesController::class,'rechazar'])->name('autorizaciones.rechazar');
    });

});