<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function(){

            // =========================
            // 1) ROLES
            // =========================
            DB::table('roles')->updateOrInsert(
                ['nombre' => 'ADMIN'],
                [
                    'descripcion' => 'Administrador del sistema',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                    'baja' => false
                ]
            );

            DB::table('roles')->updateOrInsert(
                ['nombre' => 'USUARIO'],
                [
                    'descripcion' => 'Usuario estándar',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                    'baja' => false
                ]
            );

            $adminRoleId = (int) DB::table('roles')->where('nombre','ADMIN')->value('id');

            // =========================
            // 2) PERSONA + USUARIO ADMIN
            // =========================
            $adminPersonaId = DB::table('personas')->updateOrInsert(
                ['nombres' => 'ADMIN', 'apellido_paterno' => 'SISTEMA'],
                [
                    'apellido_materno' => null,
                    'fecha_nacimiento' => null,
                    'notas' => 'Usuario administrador',
                    'updated_at' => now(),
                    'created_at' => now(),
                    'baja' => false
                ]
            );

            // updateOrInsert no regresa id; lo obtenemos
            $adminPersonaId = (int) DB::table('personas')
                ->where('nombres','ADMIN')
                ->where('apellido_paterno','SISTEMA')
                ->value('id');

            DB::table('usuarios')->updateOrInsert(
                ['username' => 'admin'],
                [
                    'persona_id' => $adminPersonaId,
                    'role_id' => $adminRoleId,
                    'email' => 'admin@local.test',
                    'password_hash' => Hash::make('Admin123*'),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                    'baja' => false
                ]
            );

            $adminUserId = (int) DB::table('usuarios')->where('username','admin')->value('id');

            // =========================
            // 3) MODULOS (jerarquía)
            // =========================
            // helper para upsert módulo
            $upsertModulo = function(string $nombre, ?string $ruta, ?string $icono, ?int $parentId, int $orden, bool $esMenu=true){
                DB::table('modulos')->updateOrInsert(
                    ['nombre' => $nombre, 'parent_id' => $parentId],
                    [
                        'ruta' => $ruta,
                        'icono' => $icono,
                        'es_menu' => $esMenu,
                        'orden' => $orden,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                        'baja' => false
                    ]
                );
                return (int) DB::table('modulos')->where('nombre',$nombre)->where('parent_id',$parentId)->value('id');
            };

            // raíz
            $mDashboard = $upsertModulo('Dashboard', '/dashboard', 'fa-gauge', null, 1, true);

            $mCatalogos = $upsertModulo('Catálogos', null, 'fa-list', null, 10, true);
            $mSeguridad = $upsertModulo('Seguridad', null, 'fa-shield-halved', null, 20, true);
            $mReportes  = $upsertModulo('Reportes', null, 'fa-chart-column', null, 30, true);

            // Catálogos hijos
            $upsertModulo('Socios', '/socios', 'fa-users', $mCatalogos, 1, true);
            $upsertModulo('Clientes', '/clientes', 'fa-user', $mCatalogos, 2, true);
            $upsertModulo('Proveedores', '/proveedores', 'fa-truck-field', $mCatalogos, 3, true);
            $upsertModulo('Empleados', '/empleados', 'fa-id-badge', $mCatalogos, 4, true);

            // Seguridad hijos
            $upsertModulo('Roles', '/roles', 'fa-user-tag', $mSeguridad, 1, true);
            $upsertModulo('Usuarios', '/usuarios', 'fa-user-gear', $mSeguridad, 2, true);
            $upsertModulo('Módulos', '/modulos', 'fa-sitemap', $mSeguridad, 3, true);
            $upsertModulo('Permisos', '/permisos', 'fa-key', $mSeguridad, 4, true);

            // Reportes / auditoría / autorizaciones
            $upsertModulo('Auditoría', '/auditoria', 'fa-clipboard-list', $mReportes, 1, true);
            $upsertModulo('Autorizaciones', '/autorizaciones', 'fa-circle-check', $mReportes, 2, true);

            // =========================
            // 4) ROLES_MODULOS: admin tiene TODO
            // =========================
            $modulos = DB::table('modulos')->where('baja', false)->pluck('id')->all();

            foreach($modulos as $mid){
                DB::table('roles_modulos')->updateOrInsert(
                    ['role_id' => $adminRoleId, 'modulo_id' => $mid],
                    ['updated_at'=>now(),'created_at'=>now()]
                );
            }

            // =========================
            // 5) USUARIOS_PERMISOS_MODULO: admin puede TODO
            // =========================
            foreach($modulos as $mid){
                DB::table('usuarios_permisos_modulo')->updateOrInsert(
                    ['usuario_id' => $adminUserId, 'modulo_id' => $mid],
                    [
                        'puede_ver' => true,
                        'puede_crear' => true,
                        'puede_modificar' => true,
                        'puede_baja' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            // =========================
            // 6) Variables globales (opcional)
            // =========================
            DB::table('variables_globales')->updateOrInsert(
                ['nombre' => 'app_config'],
                [
                    'valor' => json_encode(['empresa'=>'DEMO','moneda'=>'MXN']),
                    'descripcion' => 'Configuración general',
                    'updated_at' => now(),
                    'created_at' => now(),
                    'baja' => false
                ]
            );

        });
    }
}