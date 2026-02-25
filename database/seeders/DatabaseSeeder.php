<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Limpieza ligera (opcional). Si ya tienes data real, comenta esto.
            // OJO: respeta FKs: primero tablas hijas, luego padres.
            $tables = [
                'pago_proveedor_partidas',
                'pago_proveedor',
                'boletas_partidas',
                'boletas_pago',
                'lotes',
                'lotificacion_socios',
                'socios',
                'lotificaciones',
                'proveedores',
                'empleados',
                'vendedores',
                'clientes',
                'persona_direcciones',
                'persona_correos',
                'persona_telefonos',
                'usuarios_acciones_modulo',
                'roles_modulos',
                'modulos',
                'usuarios',
                'roles',
                'personas',
                'variables_globales',
                'solicitudes',
            ];

            foreach ($tables as $t) {
                // Si hay error por dependencias, comenta la limpieza.
                DB::statement("TRUNCATE TABLE {$t} RESTART IDENTITY CASCADE");
            }

            // ---------------------------------------------------------
            // 1) ROLES
            // ---------------------------------------------------------
            $roleAdminId = DB::table('roles')->insertGetId([
                'nombre' => 'ADMIN',
                'descripcion' => 'Acceso total',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $roleVentasId = DB::table('roles')->insertGetId([
                'nombre' => 'VENTAS',
                'descripcion' => 'Ventas / boletas / clientes',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $roleCobranzaId = DB::table('roles')->insertGetId([
                'nombre' => 'COBRANZA',
                'descripcion' => 'Cobranza / pagos / partidas',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // ---------------------------------------------------------
            // 2) PERSONAS + USUARIOS
            //    REGLA: usuario requiere persona (persona_id NOT NULL)
            // ---------------------------------------------------------
            $personaAdminId = DB::table('personas')->insertGetId([
                'nombres' => 'Hugo',
                'apellido_paterno' => 'Admin',
                'apellido_materno' => 'Sistema',
                'fecha_nacimiento' => null,
                'notas' => 'Usuario administrador inicial',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $adminUserId = DB::table('usuarios')->insertGetId([
                'persona_id' => $personaAdminId,
                'role_id' => $roleAdminId,
                'email' => 'admin@demo.local',
                'username' => 'admin',
                'password_hash' => Hash::make('Admin123*'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // 2 usuarios extra
            $personaVentasId = DB::table('personas')->insertGetId([
                'nombres' => 'Ana',
                'apellido_paterno' => 'Ventas',
                'apellido_materno' => 'Demo',
                'fecha_nacimiento' => '1994-07-13',
                'notas' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $ventasUserId = DB::table('usuarios')->insertGetId([
                'persona_id' => $personaVentasId,
                'role_id' => $roleVentasId,
                'email' => 'ventas@demo.local',
                'username' => 'ventas',
                'password_hash' => Hash::make('Ventas123*'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $personaCobranzaId = DB::table('personas')->insertGetId([
                'nombres' => 'Carlos',
                'apellido_paterno' => 'Cobranza',
                'apellido_materno' => 'Demo',
                'fecha_nacimiento' => null,
                'notas' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $cobranzaUserId = DB::table('usuarios')->insertGetId([
                'persona_id' => $personaCobranzaId,
                'role_id' => $roleCobranzaId,
                'email' => 'cobranza@demo.local',
                'username' => 'cobranza',
                'password_hash' => Hash::make('Cobranza123*'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // ---------------------------------------------------------
            // 3) CONTACTOS para personas (tel/correo/dirección)
            // ---------------------------------------------------------
            DB::table('persona_telefonos')->insert([
                [
                    'persona_id' => $personaAdminId,
                    'etiqueta' => 'principal',
                    'telefono' => '2221234567',
                    'extension' => null,
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
                [
                    'persona_id' => $personaVentasId,
                    'etiqueta' => 'whatsapp',
                    'telefono' => '2229876543',
                    'extension' => null,
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
            ]);

            DB::table('persona_correos')->insert([
                [
                    'persona_id' => $personaAdminId,
                    'etiqueta' => 'principal',
                    'correo' => 'admin@demo.local',
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
                [
                    'persona_id' => $personaVentasId,
                    'etiqueta' => 'principal',
                    'correo' => 'ventas@demo.local',
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
            ]);

            DB::table('persona_direcciones')->insert([
                [
                    'persona_id' => $personaAdminId,
                    'etiqueta' => 'casa',
                    'calle' => 'Av. Demo',
                    'numero_ext' => '123',
                    'numero_int' => null,
                    'colonia' => 'Centro',
                    'municipio' => 'Puebla',
                    'estado' => 'Puebla',
                    'cp' => '72000',
                    'referencias' => 'Frente al parque',
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
            ]);

            // ---------------------------------------------------------
            // 4) MODULOS (drawer) + roles_modulos
            // ---------------------------------------------------------
            $modCatalogos = DB::table('modulos')->insertGetId([
                'nombre' => 'Catálogos',
                'ruta' => null,
                'icono' => 'fa-list',
                'parent_id' => null,
                'es_menu' => true,
                'orden' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $modClientes = DB::table('modulos')->insertGetId([
                'nombre' => 'Clientes',
                'ruta' => '/clientes',
                'icono' => 'fa-users',
                'parent_id' => $modCatalogos,
                'es_menu' => true,
                'orden' => 11,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $modVendedores = DB::table('modulos')->insertGetId([
                'nombre' => 'Vendedores',
                'ruta' => '/vendedores',
                'icono' => 'fa-user-tie',
                'parent_id' => $modCatalogos,
                'es_menu' => true,
                'orden' => 12,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $modLotificaciones = DB::table('modulos')->insertGetId([
                'nombre' => 'Lotificaciones',
                'ruta' => '/lotificaciones',
                'icono' => 'fa-map',
                'parent_id' => null,
                'es_menu' => true,
                'orden' => 20,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $modBoletas = DB::table('modulos')->insertGetId([
                'nombre' => 'Boletas de Pago',
                'ruta' => '/boletas',
                'icono' => 'fa-file-invoice-dollar',
                'parent_id' => null,
                'es_menu' => true,
                'orden' => 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $modProveedores = DB::table('modulos')->insertGetId([
                'nombre' => 'Proveedores',
                'ruta' => '/proveedores',
                'icono' => 'fa-truck-field',
                'parent_id' => null,
                'es_menu' => true,
                'orden' => 40,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $modPagosProv = DB::table('modulos')->insertGetId([
                'nombre' => 'Pagos a Proveedor',
                'ruta' => '/pagos-proveedor',
                'icono' => 'fa-money-check-dollar',
                'parent_id' => $modProveedores,
                'es_menu' => true,
                'orden' => 41,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $allModules = [$modCatalogos, $modClientes, $modVendedores, $modLotificaciones, $modBoletas, $modProveedores, $modPagosProv];

            // ADMIN -> todos
            foreach ($allModules as $mid) {
                DB::table('roles_modulos')->insert([
                    'role_id' => $roleAdminId,
                    'modulo_id' => $mid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // VENTAS -> clientes, vendedores, lotificaciones, boletas
            foreach ([$modClientes, $modVendedores, $modLotificaciones, $modBoletas] as $mid) {
                DB::table('roles_modulos')->insert([
                    'role_id' => $roleVentasId,
                    'modulo_id' => $mid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // COBRANZA -> boletas
            DB::table('roles_modulos')->insert([
                'role_id' => $roleCobranzaId,
                'modulo_id' => $modBoletas,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ---------------------------------------------------------
            // 5) Permisos por USUARIO por módulo (ejemplo)
            // ---------------------------------------------------------
            foreach ($allModules as $mid) {
                DB::table('usuarios_acciones_modulo')->insert([
                    'usuario_id' => $adminUserId,
                    'modulo_id' => $mid,
                    'puede_ver' => true,
                    'puede_crear' => true,
                    'puede_modificar' => true,
                    'puede_baja' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // VENTAS: puede crear/modificar, baja solo boletas si quieres (aquí lo dejo false)
            foreach ([$modClientes, $modVendedores, $modLotificaciones, $modBoletas] as $mid) {
                DB::table('usuarios_acciones_modulo')->insert([
                    'usuario_id' => $ventasUserId,
                    'modulo_id' => $mid,
                    'puede_ver' => true,
                    'puede_crear' => true,
                    'puede_modificar' => true,
                    'puede_baja' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // COBRANZA: puede crear partidas, modificar, no baja
            DB::table('usuarios_acciones_modulo')->insert([
                'usuario_id' => $cobranzaUserId,
                'modulo_id' => $modBoletas,
                'puede_ver' => true,
                'puede_crear' => true,
                'puede_modificar' => true,
                'puede_baja' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ---------------------------------------------------------
            // 6) Variables globales (catálogos en JSON)
            // ---------------------------------------------------------
            DB::table('variables_globales')->insert([
                'nombre' => 'catalogos_estados_mx',
                'valor' => json_encode([
                    'estados' => [
                        ['clave' => 'PUE', 'nombre' => 'Puebla'],
                        ['clave' => 'CDMX', 'nombre' => 'Ciudad de México'],
                        ['clave' => 'JAL', 'nombre' => 'Jalisco'],
                    ],
                ]),
                'descripcion' => 'Catálogo de estados (ejemplo)',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
                'baja_by' => null,
                'baja_at' => null,
                'baja_motivo' => null,
            ]);

            DB::table('variables_globales')->insert([
                'nombre' => 'catalogo_puestos_empleado',
                'valor' => json_encode([
                    'puestos' => [
                        'GERENTE',
                        'ADMINISTRACION',
                        'VENTAS',
                        'COBRANZA',
                        'AUXILIAR_ADMIN',
                        'CONTABILIDAD',
                        'SISTEMAS',
                        'SUPERVISOR',
                        'OTRO',
                    ],
                ]),
                'descripcion' => 'Puestos para empleados (refuerzo UI)',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // ---------------------------------------------------------
            // 7) Socios + Lotificación + N a N + Lotes
            // ---------------------------------------------------------
            $socio1 = DB::table('socios')->insertGetId([
                'nombre' => 'Socio Norte',
                'color' => '#52BF04',
                'telefono' => '2221112233',
                'email' => 'socio.norte@demo.local',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $socio2 = DB::table('socios')->insertGetId([
                'nombre' => 'Socio Sur',
                'color' => '#F24405',
                'telefono' => '2224445566',
                'email' => 'socio.sur@demo.local',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $lotificacionId = DB::table('lotificaciones')->insertGetId([
                'nombre' => 'Residencial Demo',
                'json_croquis' => json_encode([
                    'version' => 1,
                    'shapes' => [
                        ['type' => 'lot', 'clave' => 'MZ-1 LT-1', 'x' => 10, 'y' => 10],
                        ['type' => 'lot', 'clave' => 'MZ-1 LT-2', 'x' => 60, 'y' => 10],
                    ],
                ]),
                'numero_lotes' => 10,
                'oficina' => 'Oficina Central',
                'estado' => 'Puebla',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            DB::table('lotificacion_socios')->insert([
                [
                    'lotificacion_id' => $lotificacionId,
                    'socio_id' => $socio1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'lotificacion_id' => $lotificacionId,
                    'socio_id' => $socio2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $lote1 = DB::table('lotes')->insertGetId([
                'lotificacion_id' => $lotificacionId,
                'clave_lote' => 'MZ-1 LT-1',
                'manzana' => '1',
                'numero' => '1',
                'estado' => 'LIBRE',
                'costo_contado' => 150000,
                'costo_credito' => 180000,
                'notas' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $lote2 = DB::table('lotes')->insertGetId([
                'lotificacion_id' => $lotificacionId,
                'clave_lote' => 'MZ-1 LT-2',
                'manzana' => '1',
                'numero' => '2',
                'estado' => 'LIBRE',
                'costo_contado' => 160000,
                'costo_credito' => 190000,
                'notas' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // ---------------------------------------------------------
            // 8) Cliente (persona + cliente)
            // ---------------------------------------------------------
            $personaClienteId = DB::table('personas')->insertGetId([
                'nombres' => 'Luis',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'Gómez',
                'fecha_nacimiento' => '1989-02-10',
                'notas' => 'Cliente demo',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            DB::table('persona_telefonos')->insert([
                'persona_id' => $personaClienteId,
                'etiqueta' => 'principal',
                'telefono' => '2225556677',
                'extension' => null,
                'es_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $clienteId = DB::table('clientes')->insertGetId([
                'persona_id' => $personaClienteId,
                'rfc' => 'PEGF890210AA1',
                'tipo_cliente' => 'general',
                'notas' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // ---------------------------------------------------------
            // 9) Vendedor (persona + vendedor)
            // ---------------------------------------------------------
            $personaVendedorId = DB::table('personas')->insertGetId([
                'nombres' => 'María',
                'apellido_paterno' => 'Vendedora',
                'apellido_materno' => 'Demo',
                'fecha_nacimiento' => null,
                'notas' => 'Vendedor demo',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $vendedorId = DB::table('vendedores')->insertGetId([
                'persona_id' => $personaVendedorId,
                'comision_default' => 5000,
                'clave' => 'VEND-001',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // ---------------------------------------------------------
            // 10) Empleado (persona + empleado) - ejemplo
            // ---------------------------------------------------------
            $personaEmpId = DB::table('personas')->insertGetId([
                'nombres' => 'Sofía',
                'apellido_paterno' => 'Auxiliar',
                'apellido_materno' => 'Demo',
                'fecha_nacimiento' => null,
                'notas' => 'Empleado/operador demo',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            DB::table('empleados')->insert([
                'persona_id' => $personaEmpId,
                'puesto' => 'AUXILIAR_ADMIN',
                'puesto_detalle' => 'Auxiliar de contratos',
                'numero_empleado' => 'EMP-0001',
                'observaciones' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // ---------------------------------------------------------
            // 11) Boleta + Partidas (venta de lote)
            // ---------------------------------------------------------
            $folioBoleta = 'BOL-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

            $boletaId = DB::table('boletas_pago')->insertGetId([
                'folio' => $folioBoleta,
                'cliente_id' => $clienteId,
                'vendedor_id' => $vendedorId,
                'lotificacion_id' => $lotificacionId,
                'socio_id' => $socio1,
                'lote_id' => $lote1,
                'oficina' => 'Oficina Central',
                'fecha_contrato' => Carbon::now()->subDays(10)->toDateString(),
                'tipo_venta' => 'CREDITO',
                'costo_contado' => 150000,
                'costo_credito' => 180000,
                'enganche' => 20000,
                'comision_vendedor' => 5000,
                'meses' => 20,
                'observaciones' => 'Boleta demo crédito 20 meses',
                'created_by' => $ventasUserId,
                'updated_by' => $ventasUserId,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            // Partidas
            DB::table('boletas_partidas')->insert([
                [
                    'boleta_id' => $boletaId,
                    'folio_partida' => $folioBoleta . '-P1',
                    'fecha_pago' => Carbon::now()->subDays(9)->toDateString(),
                    'monto' => 20000,
                    'recargo' => false,
                    'monto_recargo' => 0,
                    'tipo_pago' => 'ENGANCHE',
                    'observacion' => 'Enganche',
                    'usuario_registro_id' => $cobranzaUserId,
                    'usuario_modifico_id' => null,
                    'usuario_baja_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
                [
                    'boleta_id' => $boletaId,
                    'folio_partida' => $folioBoleta . '-P2',
                    'fecha_pago' => Carbon::now()->subDays(1)->toDateString(),
                    'monto' => 8000,
                    'recargo' => true,
                    'monto_recargo' => 200,
                    'tipo_pago' => 'ABONO',
                    'observacion' => 'Abono mensual + recargo',
                    'usuario_registro_id' => $cobranzaUserId,
                    'usuario_modifico_id' => null,
                    'usuario_baja_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
            ]);

            // ---------------------------------------------------------
            // 12) Proveedor + Pago proveedor + partidas
            // ---------------------------------------------------------
            $personaProvId = DB::table('personas')->insertGetId([
                'nombres' => 'Proveedora',
                'apellido_paterno' => 'Materiales',
                'apellido_materno' => 'SA',
                'fecha_nacimiento' => null,
                'notas' => 'Proveedor demo',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            DB::table('persona_correos')->insert([
                'persona_id' => $personaProvId,
                'etiqueta' => 'facturacion',
                'correo' => 'facturacion@proveedor.demo',
                'es_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $proveedorId = DB::table('proveedores')->insertGetId([
                'persona_id' => $personaProvId,
                'rfc' => 'XAXX010101000',
                'razon_social' => 'Materiales Demo SA de CV',
                'notas' => 'Proveedor de materiales',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            $folioPagoProv = 'PP-' . Str::upper(Str::random(8));

            $pagoProvId = DB::table('pago_proveedor')->insertGetId([
                'folio' => $folioPagoProv,
                'proveedor_id' => $proveedorId,
                'fecha_documento' => Carbon::now()->subDays(5)->toDateString(),
                'fecha_registro' => now(),
                'concepto' => 'Compra de material',
                'referencia' => 'FAC-000123',
                'monto_total' => 25000,
                'observaciones' => 'Pago en 2 exhibiciones',
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

            DB::table('pago_proveedor_partidas')->insert([
                [
                    'pago_proveedor_id' => $pagoProvId,
                    'folio_partida' => $folioPagoProv . '-1',
                    'fecha_pago' => Carbon::now()->subDays(4)->toDateString(),
                    'forma_pago' => 'TRANSFERENCIA',
                    'tipo_partida' => 'ANTICIPO',
                    'monto' => 10000,
                    'referencia_pago' => 'TRX-001122',
                    'observacion' => 'Anticipo',
                    'usuario_registro_id' => $adminUserId,
                    'usuario_modifico_id' => null,
                    'usuario_baja_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
                [
                    'pago_proveedor_id' => $pagoProvId,
                    'folio_partida' => $folioPagoProv . '-2',
                    'fecha_pago' => Carbon::now()->subDays(1)->toDateString(),
                    'forma_pago' => 'TRANSFERENCIA',
                    'tipo_partida' => 'ABONO',
                    'monto' => 15000,
                    'referencia_pago' => 'TRX-003344',
                    'observacion' => 'Liquidación',
                    'usuario_registro_id' => $adminUserId,
                    'usuario_modifico_id' => null,
                    'usuario_baja_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                ],
            ]);

            // ---------------------------------------------------------
            // 13) Solicitud demo (aprobación para baja/modificación)
            // ---------------------------------------------------------
            DB::table('solicitudes')->insert([
                'tipo' => 'MODIFICACION',
                'estatus' => 'PENDIENTE',
                'modulo_id' => $modBoletas,
                'tabla_objetivo' => 'boletas_pago',
                'registro_id' => $boletaId,
                'motivo' => 'Actualizar observaciones de boleta',
                'payload' => json_encode([
                    'before' => ['observaciones' => 'Boleta demo crédito 20 meses'],
                    'after' => ['observaciones' => 'Boleta demo (cambio solicitado)'],
                ]),
                'solicitado_por' => $ventasUserId,
                'solicitado_at' => now(),
                'revisado_por' => null,
                'revisado_at' => null,
                'decision_motivo' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
            ]);

        });
    }
}
/*
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $now = now();

            // =========================================================
            // Helpers
            // =========================================================
            $insertPersona = function(array $p) use ($now) {
                return DB::table('personas')->insertGetId([
                    'nombres' => $p['nombres'],
                    'apellido_paterno' => $p['apellido_paterno'],
                    'apellido_materno' => $p['apellido_materno'] ?? null,
                    'fecha_nacimiento' => $p['fecha_nacimiento'] ?? null,
                    'notas' => $p['notas'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ]);
            };

            $upsertVar = function(string $nombre, array $valor, string $descripcion = null) use ($now) {
                $existing = DB::table('variables_globales')->where('nombre', $nombre)->first();
                if ($existing) {
                    DB::table('variables_globales')->where('id', $existing->id)->update([
                        'valor' => json_encode($valor),
                        'descripcion' => $descripcion,
                        'updated_at' => $now,
                        'baja' => false,
                        'baja_at' => null,
                        'baja_by' => null,
                        'baja_motivo' => null,
                    ]);
                    return $existing->id;
                }

                return DB::table('variables_globales')->insertGetId([
                    'nombre' => $nombre,
                    'valor' => json_encode($valor),
                    'descripcion' => $descripcion,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ]);
            };

            $nextConsecutivo = function(string $varName) use ($now) {
                $row = DB::table('variables_globales')->where('nombre', $varName)->lockForUpdate()->first();
                if (!$row) throw new \RuntimeException("Falta variable_global: {$varName}");

                $v = is_string($row->valor) ? json_decode($row->valor, true) : (array)$row->valor;

                $prefix = (string)($v['prefix'] ?? '');
                $next   = (int)($v['next'] ?? 1);
                $pad    = (int)($v['pad'] ?? 4);

                $folio = $prefix . str_pad((string)$next, $pad, '0', STR_PAD_LEFT);

                DB::table('variables_globales')->where('id', $row->id)->update([
                    'valor' => json_encode(['prefix' => $prefix, 'next' => $next + 1, 'pad' => $pad]),
                    'updated_at' => $now,
                ]);

                return $folio;
            };

            $addTel = function(int $personaId, string $tel, string $label='principal', bool $principal=false) use ($now) {
                DB::table('persona_telefonos')->insert([
                    'persona_id' => $personaId,
                    'etiqueta' => $label,
                    'telefono' => $tel,
                    'extension' => null,
                    'es_principal' => $principal,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                    'baja_by' => null,
                ]);
            };

            $addMail = function(int $personaId, string $mail, string $label='principal', bool $principal=false) use ($now) {
                DB::table('persona_correos')->insert([
                    'persona_id' => $personaId,
                    'etiqueta' => $label,
                    'correo' => $mail,
                    'es_principal' => $principal,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                    'baja_by' => null,
                ]);
            };

            $addDir = function(int $personaId, array $d, string $label='principal', bool $principal=false) use ($now) {
                DB::table('persona_direcciones')->insert([
                    'persona_id' => $personaId,
                    'etiqueta' => $label,
                    'calle' => $d['calle'] ?? null,
                    'numero_ext' => $d['numero_ext'] ?? null,
                    'numero_int' => $d['numero_int'] ?? null,
                    'colonia' => $d['colonia'] ?? null,
                    'municipio' => $d['municipio'] ?? null,
                    'estado' => $d['estado'] ?? null,
                    'cp' => $d['cp'] ?? null,
                    'referencias' => $d['referencias'] ?? null,
                    'es_principal' => $principal,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                    'baja_by' => null,
                ]);
            };

            $insertModulo = function(array $m) use ($now) {
                return DB::table('modulos')->insertGetId([
                    'nombre' => $m['nombre'],
                    'ruta' => $m['ruta'] ?? null,
                    'icono' => $m['icono'] ?? null,
                    'parent_id' => $m['parent_id'] ?? null,
                    'es_menu' => $m['es_menu'] ?? true,
                    'orden' => $m['orden'] ?? 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ]);
            };

            // =========================================================
            // 1) ROLES
            // =========================================================
            $roleAdmin = DB::table('roles')->insertGetId([
                'nombre' => 'ADMIN',
                'descripcion' => 'Acceso total',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false
            ]);

            $roleGerente = DB::table('roles')->insertGetId([
                'nombre' => 'GERENTE',
                'descripcion' => 'Gerencia',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false
            ]);

            $roleCobranza = DB::table('roles')->insertGetId([
                'nombre' => 'COBRANZA',
                'descripcion' => 'Cobranza',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false
            ]);

            $roleAux = DB::table('roles')->insertGetId([
                'nombre' => 'AUXILIAR',
                'descripcion' => 'Operación',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false
            ]);

            // =========================================================
            // 2) VARIABLES GLOBALES (consecutivos)
            // =========================================================
            $upsertVar('empleados_consecutivo', [
                'prefix' => 'EMP-',
                'next' => 1,
                'pad' => 5
            ], 'Folio autogenerado para empleados.numero_empleado');

            $upsertVar('vendedores_clave_consecutivo', [
                'prefix' => '',
                'next' => 1,
                'pad' => 4
            ], 'Clave de vendedor 0001, 0002... (complemento de empleado VENTAS)');

            $upsertVar('boletas_pago_folio', [
                'prefix' => 'BP-',
                'next' => 1,
                'pad' => 6
            ], 'Folio de boletas_pago');

            $upsertVar('boletas_partidas_folio', [
                'prefix' => 'BPP-',
                'next' => 1,
                'pad' => 7
            ], 'Folio de boletas_partidas');

            // =========================================================
            // 3) EMPLEADOS (con contactos) + (si VENTAS => vendedores)
            // =========================================================
            $mkEmpleado = function(array $data) use ($now, $insertPersona, $nextConsecutivo) {
                $personaId = $insertPersona($data['persona']);

                $empId = DB::table('empleados')->insertGetId([
                    'persona_id' => $personaId,
                    'puesto' => $data['empleado']['puesto'] ?? 'OTRO',
                    'puesto_detalle' => $data['empleado']['puesto_detalle'] ?? null,
                    'numero_empleado' => $nextConsecutivo('empleados_consecutivo'),
                    'observaciones' => $data['empleado']['observaciones'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ]);

                return [$personaId, $empId];
            };

            // Admin empleado
            [$pAdmin, $empAdmin] = $mkEmpleado([
                'persona' => [
                    'nombres' => 'Hugo',
                    'apellido_paterno' => 'Admin',
                    'apellido_materno' => 'System',
                    'fecha_nacimiento' => '1999-01-01',
                    'notas' => 'Seed Admin',
                ],
                'empleado' => [
                    'puesto' => 'SISTEMAS',
                    'puesto_detalle' => 'Administrador',
                    'observaciones' => 'Empleado base admin',
                ]
            ]);

            // Contactos múltiples empleado admin
            $addTel($pAdmin, '999-111-2233', 'principal', true);
            $addTel($pAdmin, '999-222-3344', 'oficina', false);
            $addMail($pAdmin, 'admin@demo.com', 'principal', true);
            $addMail($pAdmin, 'admin.soporte@demo.com', 'soporte', false);
            $addDir($pAdmin, [
                'calle' => 'Av. Central',
                'numero_ext' => '123',
                'colonia' => 'Centro',
                'municipio' => 'Tuxtla',
                'estado' => 'Chiapas',
                'cp' => '29000',
                'referencias' => 'Frente al parque'
            ], 'principal', true);

            // Gerente
            [$pGer, $empGer] = $mkEmpleado([
                'persona' => [
                    'nombres' => 'Laura',
                    'apellido_paterno' => 'Gomez',
                    'apellido_materno' => 'Rios',
                    'fecha_nacimiento' => '1995-05-10',
                    'notas' => 'Gerente',
                ],
                'empleado' => [
                    'puesto' => 'GERENTE',
                    'puesto_detalle' => 'Gerencia',
                ]
            ]);
            $addTel($pGer, '961-555-1000', 'principal', true);
            $addMail($pGer, 'gerente@demo.com', 'principal', true);

            // Cobranza
            [$pCob, $empCob] = $mkEmpleado([
                'persona' => [
                    'nombres' => 'Carlos',
                    'apellido_paterno' => 'Lopez',
                    'apellido_materno' => 'Soto',
                    'fecha_nacimiento' => '1992-11-22',
                    'notas' => 'Cobranza',
                ],
                'empleado' => [
                    'puesto' => 'COBRANZA',
                    'puesto_detalle' => 'Cobrador',
                ]
            ]);
            $addTel($pCob, '961-555-2000', 'principal', true);
            $addMail($pCob, 'cobranza@demo.com', 'principal', true);

            // Auxiliar
            [$pAux, $empAux] = $mkEmpleado([
                'persona' => [
                    'nombres' => 'Martha',
                    'apellido_paterno' => 'Perez',
                    'apellido_materno' => 'Diaz',
                    'fecha_nacimiento' => '1998-02-14',
                    'notas' => 'Auxiliar',
                ],
                'empleado' => [
                    'puesto' => 'AUXILIAR_ADMIN',
                ]
            ]);
            $addTel($pAux, '961-555-3000', 'principal', true);
            $addMail($pAux, 'aux@demo.com', 'principal', true);

            // Empleado VENTAS (se crea complemento en vendedores)
            [$pVend, $empVend] = $mkEmpleado([
                'persona' => [
                    'nombres' => 'Raul',
                    'apellido_paterno' => 'Vega',
                    'apellido_materno' => 'Nava',
                    'fecha_nacimiento' => '1990-09-09',
                    'notas' => 'Empleado ventas',
                ],
                'empleado' => [
                    'puesto' => 'VENTAS',
                    'puesto_detalle' => 'Vendedor',
                    'observaciones' => 'Debe crear complemento en vendedores',
                ]
            ]);
            $addTel($pVend, '961-555-4000', 'principal', true);
            $addMail($pVend, 'ventas@demo.com', 'principal', true);

            // Complemento vendedor (puesto VENTAS)
            $vendId = DB::table('vendedores')->insertGetId([
                'empleado_id' => $empVend,
                'comision_default' => 5.00,
                'clave' => $nextConsecutivo('vendedores_clave_consecutivo'), // 0001...
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            // =========================================================
            // 4) USUARIOS (depende de EMPLEADO ya creado)
            // =========================================================
            $userAdmin = DB::table('usuarios')->insertGetId([
                'empleado_id' => $empAdmin,
                'role_id' => $roleAdmin,
                'email' => 'admin@demo.com',
                'username' => 'admin',
                'password_hash' => Hash::make('Admin123*'),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            $userGer = DB::table('usuarios')->insertGetId([
                'empleado_id' => $empGer,
                'role_id' => $roleGerente,
                'email' => 'gerente@demo.com',
                'username' => 'gerente',
                'password_hash' => Hash::make('Gerente123*'),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            $userCob = DB::table('usuarios')->insertGetId([
                'empleado_id' => $empCob,
                'role_id' => $roleCobranza,
                'email' => 'cobranza@demo.com',
                'username' => 'cobranza',
                'password_hash' => Hash::make('Cobranza123*'),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            $userAux = DB::table('usuarios')->insertGetId([
                'empleado_id' => $empAux,
                'role_id' => $roleAux,
                'email' => 'aux@demo.com',
                'username' => 'aux',
                'password_hash' => Hash::make('Aux123*'),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            $userVend = DB::table('usuarios')->insertGetId([
                'empleado_id' => $empVend,
                'role_id' => $roleAux,
                'email' => 'ventas@demo.com',
                'username' => 'ventas',
                'password_hash' => Hash::make('Ventas123*'),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            // =========================================================
            // 5) MODULOS (Drawer SIN “Vendedores”)
            // =========================================================
            $mDashboard = $insertModulo([
                'nombre' => 'Dashboard',
                'ruta' => '/dashboard',
                'icono' => 'fa-gauge-high',
                'orden' => 1,
                'es_menu' => true
            ]);

            $mCatalogos = $insertModulo([
                'nombre' => 'Catálogos',
                'ruta' => null,
                'icono' => 'fa-list',
                'orden' => 2,
                'es_menu' => true
            ]);

            $mEmpleados = $insertModulo([
                'nombre' => 'Empleados',
                'ruta' => '/empleados',
                'icono' => 'fa-id-card',
                'parent_id' => $mCatalogos,
                'orden' => 1,
                'es_menu' => true
            ]);

            $mClientes = $insertModulo([
                'nombre' => 'Clientes',
                'ruta' => '/clientes',
                'icono' => 'fa-users',
                'parent_id' => $mCatalogos,
                'orden' => 2,
                'es_menu' => true
            ]);

            $mSocios = $insertModulo([
                'nombre' => 'Socios',
                'ruta' => '/socios',
                'icono' => 'fa-handshake',
                'parent_id' => $mCatalogos,
                'orden' => 3,
                'es_menu' => true
            ]);

            $mLotificaciones = $insertModulo([
                'nombre' => 'Lotificaciones',
                'ruta' => '/lotificaciones',
                'icono' => 'fa-map',
                'parent_id' => $mCatalogos,
                'orden' => 4,
                'es_menu' => true
            ]);

            $mOperacion = $insertModulo([
                'nombre' => 'Operación',
                'ruta' => null,
                'icono' => 'fa-layer-group',
                'orden' => 3,
                'es_menu' => true
            ]);

            $mBoletas = $insertModulo([
                'nombre' => 'Boletas de Pago',
                'ruta' => '/boletas',
                'icono' => 'fa-file-invoice-dollar',
                'parent_id' => $mOperacion,
                'orden' => 1,
                'es_menu' => true
            ]);

            $mSolicitudes = $insertModulo([
                'nombre' => 'Autorizaciones',
                'ruta' => '/autorizaciones',
                'icono' => 'fa-clipboard-check',
                'parent_id' => $mOperacion,
                'orden' => 2,
                'es_menu' => true
            ]);

            $mSeguridad = $insertModulo([
                'nombre' => 'Seguridad',
                'ruta' => null,
                'icono' => 'fa-shield-halved',
                'orden' => 4,
                'es_menu' => true
            ]);

            $mUsuarios = $insertModulo([
                'nombre' => 'Usuarios',
                'ruta' => '/usuarios',
                'icono' => 'fa-user-shield',
                'parent_id' => $mSeguridad,
                'orden' => 1,
                'es_menu' => true
            ]);

            $mRoles = $insertModulo([
                'nombre' => 'Roles',
                'ruta' => '/roles',
                'icono' => 'fa-id-badge',
                'parent_id' => $mSeguridad,
                'orden' => 2,
                'es_menu' => true
            ]);

            $mModulos = $insertModulo([
                'nombre' => 'Módulos',
                'ruta' => '/modulos',
                'icono' => 'fa-sitemap',
                'parent_id' => $mSeguridad,
                'orden' => 3,
                'es_menu' => true
            ]);

            $mAuditoria = $insertModulo([
                'nombre' => 'Auditoría',
                'ruta' => '/auditoria',
                'icono' => 'fa-clock-rotate-left',
                'parent_id' => $mSeguridad,
                'orden' => 4,
                'es_menu' => true
            ]);

            // roles_modulos (drawer por rol)
            $grantRole = function($roleId, array $moduloIds) use ($now) {
                foreach ($moduloIds as $mid) {
                    DB::table('roles_modulos')->insert([
                        'role_id' => $roleId,
                        'modulo_id' => $mid,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            };

            // Admin ve todo
            $grantRole($roleAdmin, [
                $mDashboard,
                $mCatalogos, $mEmpleados, $mClientes, $mSocios, $mLotificaciones,
                $mOperacion, $mBoletas, $mSolicitudes,
                $mSeguridad, $mUsuarios, $mRoles, $mModulos, $mAuditoria
            ]);

            // Gerente
            $grantRole($roleGerente, [
                $mDashboard,
                $mCatalogos, $mEmpleados, $mClientes, $mSocios, $mLotificaciones,
                $mOperacion, $mBoletas, $mSolicitudes,
                $mSeguridad, $mUsuarios, $mRoles, $mAuditoria
            ]);

            // Cobranza
            $grantRole($roleCobranza, [
                $mDashboard,
                $mOperacion, $mBoletas, $mSolicitudes
            ]);

            // Aux
            $grantRole($roleAux, [
                $mDashboard,
                $mCatalogos, $mClientes, $mSocios,
                $mOperacion, $mBoletas
            ]);

            // usuarios_acciones_modulo (acciones por usuario)
            $grantUser = function($userId, array $map) use ($now) {
                foreach ($map as $modId => $perms) {
                    DB::table('usuarios_acciones_modulo')->insert([
                        'usuario_id' => $userId,
                        'modulo_id' => $modId,
                        'puede_ver' => (bool)($perms['ver'] ?? true),
                        'puede_crear' => (bool)($perms['crear'] ?? false),
                        'puede_modificar' => (bool)($perms['modificar'] ?? false),
                        'puede_baja' => (bool)($perms['baja'] ?? false),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            };

            // Admin full
            $grantUser($userAdmin, [
                $mDashboard => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mEmpleados => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mUsuarios  => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mClientes  => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mSocios    => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mLotificaciones => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mBoletas   => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mSolicitudes => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mRoles     => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mModulos   => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>true],
                $mAuditoria => ['ver'=>true,'crear'=>false,'modificar'=>false,'baja'=>false],
            ]);

            // Gerente
            $grantUser($userGer, [
                $mDashboard => ['ver'=>true],
                $mEmpleados => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mUsuarios  => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mClientes => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mSocios => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mLotificaciones => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mBoletas => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mSolicitudes => ['ver'=>true,'crear'=>true,'modificar'=>false,'baja'=>false],
                $mRoles => ['ver'=>true],
                $mAuditoria => ['ver'=>true],
            ]);

            // Cobranza
            $grantUser($userCob, [
                $mDashboard => ['ver'=>true],
                $mBoletas => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mSolicitudes => ['ver'=>true,'crear'=>true,'modificar'=>false,'baja'=>false],
            ]);

            // Aux
            $grantUser($userAux, [
                $mDashboard => ['ver'=>true],
                $mClientes => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mSocios => ['ver'=>true,'crear'=>true,'modificar'=>true,'baja'=>false],
                $mBoletas => ['ver'=>true,'crear'=>true,'modificar'=>false,'baja'=>false],
            ]);

            // Vendedor (usuario) - opera boletas y clientes
            $grantUser($userVend, [
                $mDashboard => ['ver'=>true],
                $mBoletas => ['ver'=>true,'crear'=>true,'modificar'=>false,'baja'=>false],
                $mClientes => ['ver'=>true,'crear'=>true,'modificar'=>false,'baja'=>false],
            ]);

            // =========================================================
            // 6) CLIENTES (con contactos múltiples)
            // =========================================================
            $pC1 = $insertPersona([
                'nombres' => 'Juan',
                'apellido_paterno' => 'Hernandez',
                'apellido_materno' => 'Mora',
                'fecha_nacimiento' => '1988-03-15',
                'notas' => 'Cliente 1'
            ]);
            $cli1 = DB::table('clientes')->insertGetId([
                'persona_id' => $pC1,
                'rfc' => 'HEMJ880315XXX',
                'tipo_cliente' => 'general',
                'notas' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            // múltiples contactos cliente 1
            $addTel($pC1, '961-700-1000', 'principal', true);
            $addTel($pC1, '961-700-1001', 'whatsapp', false);
            $addMail($pC1, 'juan.cliente@demo.com', 'principal', true);
            $addMail($pC1, 'juan.facturacion@demo.com', 'facturacion', false);
            $addDir($pC1, [
                'calle' => 'Calle 1',
                'numero_ext' => '10',
                'colonia' => 'Las Flores',
                'municipio' => 'Tuxtla',
                'estado' => 'Chiapas',
                'cp' => '29020',
                'referencias' => 'Casa azul'
            ], 'casa', true);

            $pC2 = $insertPersona([
                'nombres' => 'Ana',
                'apellido_paterno' => 'Sanchez',
                'apellido_materno' => 'Loera',
                'fecha_nacimiento' => '1991-07-20',
                'notas' => 'Cliente 2'
            ]);
            $cli2 = DB::table('clientes')->insertGetId([
                'persona_id' => $pC2,
                'rfc' => 'SALA910720XXX',
                'tipo_cliente' => 'general',
                'notas' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            // múltiples contactos cliente 2
            $addTel($pC2, '961-700-2000', 'principal', true);
            $addMail($pC2, 'ana.cliente@demo.com', 'principal', true);
            $addDir($pC2, [
                'calle' => 'Calle 2',
                'numero_ext' => '22',
                'colonia' => 'Lomas',
                'municipio' => 'Tuxtla',
                'estado' => 'Chiapas',
                'cp' => '29010',
                'referencias' => 'Departamento 3'
            ], 'casa', true);

            // =========================================================
            // 7) SOCIOS / LOTIFICACIONES / LOTES
            // =========================================================
            $socio1 = DB::table('socios')->insertGetId([
                'nombre' => 'Socio Azul',
                'color' => '#2D6CDF',
                'telefono' => '961-100-1000',
                'email' => 'socio.azul@demo.com',
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false
            ]);

            $socio2 = DB::table('socios')->insertGetId([
                'nombre' => 'Socio Rojo',
                'color' => '#D9042B',
                'telefono' => '961-200-2000',
                'email' => 'socio.rojo@demo.com',
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false
            ]);

            $lot1 = DB::table('lotificaciones')->insertGetId([
                'nombre' => 'Residencial Las Palmas',
                'json_croquis' => json_encode(['demo'=>true]),
                'numero_lotes' => 6,
                'oficina' => 'Oficina Centro',
                'estado' => 'Chiapas',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            $lot2 = DB::table('lotificaciones')->insertGetId([
                'nombre' => 'Colinas del Sol',
                'json_croquis' => json_encode(['demo'=>true]),
                'numero_lotes' => 4,
                'oficina' => 'Oficina Norte',
                'estado' => 'Chiapas',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'baja' => false,
            ]);

            DB::table('lotificacion_socios')->insert([
                ['lotificacion_id'=>$lot1,'socio_id'=>$socio1,'created_at'=>$now,'updated_at'=>$now],
                ['lotificacion_id'=>$lot1,'socio_id'=>$socio2,'created_at'=>$now,'updated_at'=>$now],
                ['lotificacion_id'=>$lot2,'socio_id'=>$socio2,'created_at'=>$now,'updated_at'=>$now],
            ]);

            $mkLote = function($lotificacionId, $clave, $estado='LIBRE', $cc=150000, $cr=185000) use ($now) {
                return DB::table('lotes')->insertGetId([
                    'lotificacion_id' => $lotificacionId,
                    'clave_lote' => $clave,
                    'manzana' => 'A',
                    'numero' => preg_replace('/\D+/', '', $clave) ?: '1',
                    'estado' => $estado,
                    'costo_contado' => $cc,
                    'costo_credito' => $cr,
                    'notas' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ]);
            };

            $lotes = [];
            $lotes['LP-02'] = $mkLote($lot1,'LP-02','LIBRE', 130000, 160000);
            $lotes['CS-03'] = $mkLote($lot2,'CS-03','LIBRE', 170000, 210000);

            // =========================================================
            // 8) BOLETAS + PARTIDAS (folios por variables_globales)
            // =========================================================
            $mkBoleta = function(array $b) use ($now, $nextConsecutivo, $userAdmin) {
                return DB::table('boletas_pago')->insertGetId([
                    'folio' => $nextConsecutivo('boletas_pago_folio'),
                    'cliente_id' => $b['cliente_id'],
                    'vendedor_id' => $b['vendedor_id'] ?? null,
                    'lotificacion_id' => $b['lotificacion_id'],
                    'socio_id' => $b['socio_id'] ?? null,
                    'lote_id' => $b['lote_id'],
                    'oficina' => $b['oficina'] ?? null,
                    'fecha_contrato' => $b['fecha_contrato'],
                    'tipo_venta' => $b['tipo_venta'],
                    'costo_contado' => $b['costo_contado'] ?? 0,
                    'costo_credito' => $b['costo_credito'] ?? 0,
                    'enganche' => $b['enganche'] ?? 0,
                    'comision_vendedor' => $b['comision_vendedor'] ?? 0,
                    'meses' => $b['meses'] ?? 0,
                    'observaciones' => $b['observaciones'] ?? null,
                    'created_by' => $b['created_by'] ?? $userAdmin,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ]);
            };

            $mkPartida = function(array $p) use ($now, $nextConsecutivo) {
                return DB::table('boletas_partidas')->insertGetId([
                    'boleta_id' => $p['boleta_id'],
                    'folio_partida' => $nextConsecutivo('boletas_partidas_folio'),
                    'fecha_pago' => $p['fecha_pago'],
                    'monto' => $p['monto'],
                    'recargo' => (bool)($p['recargo'] ?? false),
                    'monto_recargo' => $p['monto_recargo'] ?? 0,
                    'tipo_pago' => $p['tipo_pago'] ?? 'ABONO',
                    'observacion' => $p['observacion'] ?? null,
                    'usuario_registro_id' => $p['usuario_registro_id'] ?? null,
                    'usuario_modifico_id' => null,
                    'usuario_baja_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ]);
            };

            // Boleta 1 (contado)
            $b1 = $mkBoleta([
                'cliente_id' => $cli1,
                'vendedor_id' => $vendId, // complemento vendedor
                'lotificacion_id' => $lot1,
                'socio_id' => $socio1,
                'lote_id' => $lotes['LP-02'],
                'oficina' => 'Oficina Centro',
                'fecha_contrato' => date('Y-m-d'),
                'tipo_venta' => 'CONTADO',
                'costo_contado' => 130000,
                'enganche' => 130000,
                'comision_vendedor' => 6500,
                'meses' => 0,
                'created_by' => $userVend
            ]);

            $mkPartida([
                'boleta_id' => $b1,
                'fecha_pago' => date('Y-m-d'),
                'monto' => 130000,
                'tipo_pago' => 'ENGANCHE',
                'observacion' => 'Pago de contado',
                'usuario_registro_id' => $userVend
            ]);

            // Boleta 2 (crédito)
            $b2 = $mkBoleta([
                'cliente_id' => $cli2,
                'vendedor_id' => $vendId,
                'lotificacion_id' => $lot2,
                'socio_id' => $socio2,
                'lote_id' => $lotes['CS-03'],
                'oficina' => 'Oficina Norte',
                'fecha_contrato' => date('Y-m-d', strtotime('-5 days')),
                'tipo_venta' => 'CREDITO',
                'costo_contado' => 170000,
                'costo_credito' => 210000,
                'enganche' => 30000,
                'comision_vendedor' => 8500,
                'meses' => 24,
                'created_by' => $userAdmin
            ]);

            $mkPartida([
                'boleta_id' => $b2,
                'fecha_pago' => date('Y-m-d', strtotime('-5 days')),
                'monto' => 30000,
                'tipo_pago' => 'ENGANCHE',
                'observacion' => 'Enganche crédito',
                'usuario_registro_id' => $userAdmin
            ]);

            $mkPartida([
                'boleta_id' => $b2,
                'fecha_pago' => date('Y-m-d', strtotime('-2 days')),
                'monto' => 5000,
                'tipo_pago' => 'ABONO',
                'observacion' => 'Primer abono',
                'usuario_registro_id' => $userCob
            ]);

            // =========================================================
            // 9) SOLICITUDES (autorizaciones)
            // =========================================================
            DB::table('solicitudes')->insert([
                [
                    'tipo' => 'MODIFICACION',
                    'estatus' => 'PENDIENTE',
                    'modulo_id' => $mBoletas,
                    'tabla_objetivo' => 'boletas_pago',
                    'registro_id' => $b2,
                    'motivo' => 'Actualizar observaciones de contrato',
                    'payload' => json_encode(['observaciones' => 'Cliente solicita ajuste de datos']),
                    'solicitado_por' => $userCob,
                    'solicitado_at' => $now,
                    'revisado_por' => null,
                    'revisado_at' => null,
                    'decision_motivo' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ],
                [
                    'tipo' => 'BAJA',
                    'estatus' => 'PENDIENTE',
                    'modulo_id' => $mBoletas,
                    'tabla_objetivo' => 'boletas_partidas',
                    'registro_id' => 2, // demo
                    'motivo' => 'Pago duplicado',
                    'payload' => json_encode(['reason'=>'duplicado']),
                    'solicitado_por' => $userCob,
                    'solicitado_at' => $now,
                    'revisado_por' => null,
                    'revisado_at' => null,
                    'decision_motivo' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'baja' => false,
                ],
            ]);

            // =========================================================
            // 10) AUDITORIA (acciones_usuario_historial) - DEMO
            // =========================================================
            if (Schema::hasTable('acciones_usuario_historial')) {
                DB::table('acciones_usuario_historial')->insert([
                    [
                        'usuario_id' => $userAdmin,
                        'modulo_id' => $mDashboard,
                        'accion' => 'LOGIN',
                        'tabla' => 'usuarios',
                        'registro_id' => $userAdmin,
                        'ip' => '127.0.0.1',
                        'user_agent' => 'Seeder',
                        'before_data' => null,
                        'after_data' => json_encode(['username'=>'admin']),
                        'created_at' => $now,
                    ],
                    [
                        'usuario_id' => $userVend,
                        'modulo_id' => $mBoletas,
                        'accion' => 'CREAR',
                        'tabla' => 'boletas_pago',
                        'registro_id' => $b1,
                        'ip' => '127.0.0.1',
                        'user_agent' => 'Seeder',
                        'before_data' => null,
                        'after_data' => json_encode(['folio'=>'(autogen)']),
                        'created_at' => $now,
                    ],
                ]);
            }

        });
    }
}*/
