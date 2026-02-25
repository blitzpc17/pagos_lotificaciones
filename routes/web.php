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



Route::get('/login', [AjaxLoginController::class, 'show'])->name('login');
Route::post('/login', [AjaxLoginController::class, 'login'])->name('login.ajax');
Route::post('/logout', [AjaxLoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== ROLES =====
    Route::get('/roles', [RolesController::class, 'index'])->name('roles.index'); // view o JSON
    Route::get('/roles/{role}', [RolesController::class, 'show'])->name('roles.show');
    Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RolesController::class, 'update'])->name('roles.update');
    Route::post('/roles/{role}/baja', [RolesController::class, 'baja'])->name('roles.baja');

    // Empleados (persona+empleado+contactos y vendedor complemento)
    Route::get('/empleados', [EmpleadosController::class, 'index'])->name('empleados.index');
    Route::get('/empleados/datatable', [EmpleadosController::class, 'datatable'])->name('empleados.datatable');
    Route::post('/empleados', [EmpleadosController::class, 'store'])->name('empleados.store');
    Route::get('/empleados/{id}', [EmpleadosController::class, 'show'])->name('empleados.show');
    Route::put('/empleados/{id}', [EmpleadosController::class, 'update'])->name('empleados.update');
    Route::post('/empleados/{id}/baja', [EmpleadosController::class, 'baja'])->name('empleados.baja');  

    // Usuarios (crear sobre empleado existente)
    Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/datatable', [UsuariosController::class, 'datatable'])->name('usuarios.datatable');
    Route::get('/usuarios/empleados-disponibles', [UsuariosController::class, 'empleadosDisponibles'])->name('usuarios.empleados_disponibles');
    Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}', [UsuariosController::class, 'show'])->name('usuarios.show');
    Route::put('/usuarios/{id}', [UsuariosController::class, 'update'])->name('usuarios.update');
    Route::post('/usuarios/{id}/baja', [UsuariosController::class, 'baja'])->name('usuarios.baja');

    // ===== SOCIOS =====
    Route::get('/socios', [\App\Http\Controllers\SociosController::class, 'index'])->name('socios.index');
    Route::get('/socios/datatable', [\App\Http\Controllers\SociosController::class, 'datatable'])->name('socios.datatable');
    Route::post('/socios', [\App\Http\Controllers\SociosController::class, 'store'])->name('socios.store');
    Route::get('/socios/{id}', [\App\Http\Controllers\SociosController::class, 'show'])->name('socios.show');
    Route::put('/socios/{id}', [\App\Http\Controllers\SociosController::class, 'update'])->name('socios.update');
    Route::post('/socios/{id}/baja', [\App\Http\Controllers\SociosController::class, 'baja'])->name('socios.baja');


    // ===== CLIENTES =====
    Route::get('/clientes', [\App\Http\Controllers\ClientesController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/datatable', [\App\Http\Controllers\ClientesController::class, 'datatable'])->name('clientes.datatable');
    Route::post('/clientes', [\App\Http\Controllers\ClientesController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{id}', [\App\Http\Controllers\ClientesController::class, 'show'])->name('clientes.show');
    Route::put('/clientes/{id}', [\App\Http\Controllers\ClientesController::class, 'update'])->name('clientes.update');
    Route::post('/clientes/{id}/baja', [\App\Http\Controllers\ClientesController::class, 'baja'])->name('clientes.baja');

    // ===== PROVEEDORES (persona + proveedor + contactos) =====
    Route::get('/proveedores', [\App\Http\Controllers\ProveedoresController::class, 'index'])->name('proveedores.index');
    Route::get('/proveedores/datatable', [\App\Http\Controllers\ProveedoresController::class, 'datatable'])->name('proveedores.datatable');

    Route::post('/proveedores', [\App\Http\Controllers\ProveedoresController::class, 'store'])->name('proveedores.store');
    Route::get('/proveedores/{id}', [\App\Http\Controllers\ProveedoresController::class, 'show'])->name('proveedores.show');
    Route::put('/proveedores/{id}', [\App\Http\Controllers\ProveedoresController::class, 'update'])->name('proveedores.update');
    Route::post('/proveedores/{id}/baja', [\App\Http\Controllers\ProveedoresController::class, 'baja'])->name('proveedores.baja');

    
    // ===== AUDITORÍA (SOLO CONSULTA) =====
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index'); // view o JSON   

   
    // contactos socio (si lo manejas como persona + contactos múltiples)
    Route::get('/socios/{id}/contactos', [\App\Http\Controllers\SociosController::class, 'contactos'])->name('socios.contactos');
    Route::post('/socios/{id}/telefonos', [\App\Http\Controllers\SociosController::class, 'addTelefono'])->name('socios.tel.add');
    Route::post('/socios/{id}/correos', [\App\Http\Controllers\SociosController::class, 'addCorreo'])->name('socios.mail.add');
    Route::post('/socios/{id}/direcciones', [\App\Http\Controllers\SociosController::class, 'addDireccion'])->name('socios.dir.add');
    Route::post('/socios/contacto/{tipo}/{cid}/baja', [\App\Http\Controllers\SociosController::class, 'bajaContacto'])->name('socios.contacto.baja');

    //contacto cliente
    Route::get('/clientes/{id}/contactos', [ClientesController::class, 'contactos'])->name('clientes.contactos');
    Route::post('/clientes/{id}/telefonos', [ClientesController::class, 'addTelefono'])->name('clientes.tel.add');
    Route::post('/clientes/{id}/correos', [ClientesController::class, 'addCorreo'])->name('clientes.mail.add');
    Route::post('/clientes/{id}/direcciones', [ClientesController::class, 'addDireccion'])->name('clientes.dir.add');
    Route::post('/clientes/contacto/{tipo}/{cid}/baja', [ClientesController::class, 'bajaContacto'])->name('clientes.contacto.baja');

    // contactos empleado (por persona_id interno)
    Route::get('/empleados/{id}/contactos', [EmpleadosController::class, 'contactos'])->name('empleados.contactos');
    Route::post('/empleados/{id}/telefonos', [EmpleadosController::class, 'addTelefono'])->name('empleados.tel.add');
    Route::post('/empleados/{id}/correos', [EmpleadosController::class, 'addCorreo'])->name('empleados.mail.add');
    Route::post('/empleados/{id}/direcciones', [EmpleadosController::class, 'addDireccion'])->name('empleados.dir.add');
    Route::post('/empleados/contacto/{tipo}/{cid}/baja', [EmpleadosController::class, 'bajaContacto'])->name('empleados.contacto.baja');

    
    // contacto proveedores 
    Route::get('/proveedores/{id}/contactos', [\App\Http\Controllers\ProveedoresController::class, 'contactos'])->name('proveedores.contactos');
    Route::post('/proveedores/{id}/telefonos', [\App\Http\Controllers\ProveedoresController::class, 'addTelefono'])->name('proveedores.tel.add');
    Route::post('/proveedores/{id}/correos', [\App\Http\Controllers\ProveedoresController::class, 'addCorreo'])->name('proveedores.mail.add');
    Route::post('/proveedores/{id}/direcciones', [\App\Http\Controllers\ProveedoresController::class, 'addDireccion'])->name('proveedores.dir.add');
    Route::post('/proveedores/contacto/{tipo}/{cid}/baja', [\App\Http\Controllers\ProveedoresController::class, 'bajaContacto'])->name('proveedores.contacto.baja');

    


    // ===== MODULOS =====
    Route::get('/modulos', [\App\Http\Controllers\ModulosController::class,'index'])->name('modulos.index');
    Route::get('/modulos/{modulo}', [\App\Http\Controllers\ModulosController::class,'show'])->name('modulos.show');
    Route::post('/modulos', [\App\Http\Controllers\ModulosController::class,'store'])->name('modulos.store');
    Route::put('/modulos/{modulo}', [\App\Http\Controllers\ModulosController::class,'update'])->name('modulos.update');
    Route::post('/modulos/{modulo}/baja', [\App\Http\Controllers\ModulosController::class,'baja'])->name('modulos.baja');

    // ===== PERMISOS (rol->modulo + acciones usuario->modulo) =====
    Route::get('/permisos', [\App\Http\Controllers\PermisosController::class,'index'])->name('permisos.index');
    Route::get('/permisos/roles/{rol}', [\App\Http\Controllers\PermisosController::class,'getRoleModules'])->name('permisos.role_modules');
    Route::post('/permisos/roles/{rol}', [\App\Http\Controllers\PermisosController::class,'setRoleModules'])->name('permisos.role_modules_set');

    Route::get('/permisos/usuarios/{usuario}/acciones', [\App\Http\Controllers\PermisosController::class,'getUserActions'])->name('permisos.user_actions');
    Route::post('/permisos/usuarios/{usuario}/acciones', [\App\Http\Controllers\PermisosController::class,'setUserActions'])->name('permisos.user_actions_set');

    // ===== AUTORIZACIONES =====
    Route::middleware(['auth','module:/autorizaciones','action:ver'])->group(function(){

        Route::get('/autorizaciones', [\App\Http\Controllers\AutorizacionesController::class,'index'])->name('autorizaciones.index');
        Route::get('/autorizaciones/{solicitud}', [\App\Http\Controllers\AutorizacionesController::class,'show'])->name('autorizaciones.show');
        Route::post('/autorizaciones/{solicitud}/aprobar', [\App\Http\Controllers\AutorizacionesController::class,'aprobar'])->name('autorizaciones.aprobar');
        Route::post('/autorizaciones/{solicitud}/rechazar', [\App\Http\Controllers\AutorizacionesController::class,'rechazar'])->name('autorizaciones.rechazar');
   
    
    });








});
