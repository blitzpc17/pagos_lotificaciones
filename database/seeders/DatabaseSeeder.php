<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            DB::table('personas')->updateOrInsert(
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
            $upsertModulo('Dashboard', '/dashboard', 'fa fa-gauge', null, 1, true);

            $mCatalogos = $upsertModulo('Catálogos', null, 'fa fa-list', null, 10, true);
            $mSeguridad = $upsertModulo('Seguridad', null, 'fa fa-shield-halved', null, 20, true);
            $mLotificaciones =    $upsertModulo('Lotificaciones', null, 'fa fa-map', null, 30, true);

            $mMovimientos = $upsertModulo('Cobros', null, 'fa fa-money-bill-wave', null, 40, true); 
            $mProveedores = $upsertModulo(' Proveedores', null, 'fa fa-money-bill-wave', null, 50, true); 
           
           
            $mReportes  = $upsertModulo('Reportes', null, 'fa fa-chart-column', null, 60, true);
       

            // Catálogos hijos
            $upsertModulo('Socios', '/socios', 'fa fa-users', $mCatalogos, 1, true);
            $upsertModulo('Clientes', '/clientes', 'fa fa-user', $mCatalogos, 2, true);          
            $upsertModulo('Empleados', '/empleados', 'fa fa-id-badge', $mCatalogos, 4, true);

             // Seguridad hijos
            $upsertModulo('Roles', '/roles', 'fa fa fa-user-tag', $mSeguridad, 1, true);
            $upsertModulo('Usuarios', '/usuarios', 'fa fa-user-gear', $mSeguridad, 2, true);
            $upsertModulo('Módulos', '/modulos', 'fa fa-sitemap', $mSeguridad, 3, true);
            $upsertModulo('Permisos', '/permisos', 'fa fa-key', $mSeguridad, 4, true);


            //lotificaciones / lotes
         
            $upsertModulo('Lotificaciones', '/lotificaciones', 'fa fa-map', $mLotificaciones, 1, true);
            $upsertModulo('Lotes', '/lotes', 'fa fa-border-all', $mLotificaciones, 2, true);

            // boletas
            $upsertModulo('Boletas de Pago', '/boletas', 'fa fa-receipt', $mMovimientos, 1, true);          

            //proveedores
            $upsertModulo('Proveedores', '/proveedores', 'fa fa-truck-field', $mProveedores, 1, true);
            $upsertModulo('Pagos a Proveedor', '/pagos-proveedor', 'fa fa-truck-field', $mProveedores, 2, true);

           
            // Reportes / auditoría / autorizaciones
            $upsertModulo('Auditoría', '/auditoria', 'fa fa-clipboard-list', $mReportes, 1, true);
            $upsertModulo('Autorizaciones', '/autorizaciones', 'fa fa-circle-check', $mReportes, 2, true);

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

            // =========================
            // 6.1) ✅ Empleados demo con numero_empleado 0001, 0002...
            // =========================
            $this->seedEmpleadosConNumero($adminUserId);

            // =========================
            // 7) Datos demo (clientes/vendedores/proveedores/lotificaciones/lotes)
            // =========================
            $this->seedDatosBaseDemo($adminUserId);

            // =========================
            // 8) MUCHOS REGISTROS: boletas + partidas
            // =========================
            $this->seedBoletasYPagosClientes(
                boletasCount: 250,
                maxPartidasPorBoleta: 18,
                usuarioFallbackId: $adminUserId
            );

            // =========================
            // 9) MUCHOS REGISTROS: pago proveedor + partidas
            // =========================
            $this->seedPagosProveedores(
                pagosProveedorCount: 160,
                maxPartidasPorPago: 10,
                usuarioFallbackId: $adminUserId
            );

        });
    }

    // =====================================================================
    // ✅ Empleados demo: numero_empleado 0001, 0002, ... (solo si faltan)
    // =====================================================================
    private function seedEmpleadosConNumero(int $adminUserId): void
    {
        $faker = \Faker\Factory::create('es_MX');

        // Si ya tienes empleados, no duplicamos (puedes cambiar la regla si quieres).
        $count = (int) DB::table('empleados')->where('baja', false)->count();
        if ($count > 0) return;

        // Crea 30 empleados demo
        for ($i=1; $i<=30; $i++){
            $nEmp = str_pad((string)$i, 4, '0', STR_PAD_LEFT);

            $pid = DB::table('personas')->insertGetId([
                'nombres' => $faker->firstName,
                'apellido_paterno' => $faker->lastName,
                'apellido_materno' => $faker->lastName,
                'fecha_nacimiento' => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                'notas' => 'Empleado demo',
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
                'baja_by' => null,
                'baja_at' => null,
                'baja_motivo' => null,
            ]);

            DB::table('empleados')->insert([
                'persona_id' => $pid,
                'puesto' => 'OTRO',
                'puesto_detalle' => null,
                'numero_empleado' => $nEmp, // ✅ 0001...
                'observaciones' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'baja' => false,
                'baja_by' => null,
                'baja_at' => null,
                'baja_motivo' => null,
            ]);
        }
    }

    // =====================================================================
    //  DATOS BASE DEMO (solo si hacen falta)
    // =====================================================================
    private function seedDatosBaseDemo(int $adminUserId): void
    {
        $faker = \Faker\Factory::create('es_MX');

        // ----- Lotificaciones + Socios + Lotes
        $lotificacionesCount = (int) DB::table('lotificaciones')->where('baja', false)->count();
        if ($lotificacionesCount < 3) {
            for ($i=1; $i<=3; $i++) {
                DB::table('lotificaciones')->insert([
                    'nombre' => 'LOTIFICACION DEMO ' . $i,
                    'json_croquis' => json_encode(['demo' => true, 'version' => 1]),
                    'numero_lotes' => 0,
                    'oficina' => $faker->randomElement(['PUEBLA','CDMX','MATRIZ']),
                    'estado' => $faker->randomElement(['PUEBLA','CDMX','VERACRUZ']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                    'baja_by' => null,
                    'baja_at' => null,
                    'baja_motivo' => null,
                ]);
            }
        }

        $sociosCount = (int) DB::table('socios')->where('baja', false)->count();
        if ($sociosCount < 5) {
            for ($i=1; $i<=5; $i++) {
                $pid = DB::table('personas')->insertGetId([
                    'nombres' => 'Socio ' . $i,
                    'apellido_paterno' => $faker->lastName,
                    'apellido_materno' => $faker->lastName,
                    'fecha_nacimiento' => null,
                    'notas' => 'Socio demo',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);

                DB::table('socios')->insert([
                    'nombre' => 'SOCIO DEMO ' . $i,
                    'color' => $faker->hexColor,
                    'telefono' => $faker->phoneNumber,
                    'email' => $faker->safeEmail,
                    'persona_id' => $pid,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);
            }
        }

        $lotificacionIds = DB::table('lotificaciones')->where('baja', false)->pluck('id')->all();
        $socioIds = DB::table('socios')->where('baja', false)->pluck('id')->all();

        foreach ($lotificacionIds as $lid) {
            if (!empty($socioIds)) {
                $picked = array_rand($socioIds, min(2, count($socioIds)));
                $picked = is_array($picked) ? $picked : [$picked];
                foreach ($picked as $idx) {
                    DB::table('lotificacion_socios')->updateOrInsert(
                        ['lotificacion_id' => $lid, 'socio_id' => $socioIds[$idx]],
                        ['updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        }

        $lotesCount = (int) DB::table('lotes')->where('baja', false)->count();
        if ($lotesCount < 100) {
            foreach ($lotificacionIds as $lid) {
                for ($n=1; $n<=60; $n++) {
                    $clave = 'L-' . $lid . '-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
                    DB::table('lotes')->updateOrInsert(
                        ['lotificacion_id' => $lid, 'clave_lote' => $clave],
                        [
                            'manzana' => (string) $faker->numberBetween(1, 20),
                            'numero' => (string) $n,
                            'estado' => 'LIBRE',
                            'costo_contado' => $this->money($faker->numberBetween(20000, 180000)),
                            'costo_credito' => $this->money($faker->numberBetween(30000, 240000)),
                            'notas' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'baja' => false
                        ]
                    );
                }

                DB::table('lotificaciones')->where('id', $lid)->update([
                    'numero_lotes' => (int) DB::table('lotes')->where('lotificacion_id', $lid)->where('baja', false)->count(),
                    'updated_at' => now()
                ]);
            }
        }

        // ----- Clientes (con persona + contactos)
        $clientesCount = (int) DB::table('clientes')->where('baja', false)->count();
        if ($clientesCount < 40) {
            for ($i=1; $i<=50; $i++) {
                $pid = DB::table('personas')->insertGetId([
                    'nombres' => $faker->firstName,
                    'apellido_paterno' => $faker->lastName,
                    'apellido_materno' => $faker->lastName,
                    'fecha_nacimiento' => $faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
                    'notas' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);

                DB::table('clientes')->insert([
                    'persona_id' => $pid,
                    'rfc' => strtoupper($faker->bothify('????######???')),
                    'tipo_cliente' => $faker->randomElement(['general','preferente','inversionista']),
                    'notas' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false,
                    'baja_by' => null,
                    'baja_at' => null,
                    'baja_motivo' => null
                ]);

                DB::table('persona_correos')->insert([
                    'persona_id' => $pid,
                    'etiqueta' => 'principal',
                    'correo' => $faker->unique()->safeEmail,
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);

                DB::table('persona_telefonos')->insert([
                    'persona_id' => $pid,
                    'etiqueta' => 'celular',
                    'telefono' => $faker->phoneNumber,
                    'extension' => null,
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);

                DB::table('persona_direcciones')->insert([
                    'persona_id' => $pid,
                    'etiqueta' => 'casa',
                    'calle' => $faker->streetName,
                    'numero_ext' => (string)$faker->numberBetween(1, 999),
                    'numero_int' => null,
                    'colonia' => $faker->citySuffix,
                    'municipio' => $faker->city,
                    'estado' => $faker->state,
                    'cp' => $faker->postcode,
                    'referencias' => null,
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);
            }
        }

        // ----- Vendedores
        $vendedoresCount = (int) DB::table('vendedores')->where('baja', false)->count();
        if ($vendedoresCount < 8) {
            for ($i=1; $i<=10; $i++) {
                $pid = DB::table('personas')->insertGetId([
                    'nombres' => 'Vendedor ' . $faker->firstName,
                    'apellido_paterno' => $faker->lastName,
                    'apellido_materno' => $faker->lastName,
                    'fecha_nacimiento' => $faker->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
                    'notas' => 'Vendedor demo',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);

                DB::table('vendedores')->insert([
                    'persona_id' => $pid,
                    'comision_default' => $this->money($faker->randomFloat(2, 0, 10)),
                    'clave' => 'VEND-' . strtoupper(Str::random(5)),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);
            }
        }

        // ----- Proveedores
        $proveedoresCount = (int) DB::table('proveedores')->where('baja', false)->count();
        if ($proveedoresCount < 15) {
            for ($i=1; $i<=20; $i++) {
                $pid = DB::table('personas')->insertGetId([
                    'nombres' => 'Proveedor ' . $faker->firstName,
                    'apellido_paterno' => $faker->lastName,
                    'apellido_materno' => $faker->lastName,
                    'fecha_nacimiento' => null,
                    'notas' => 'Proveedor demo',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);

                DB::table('proveedores')->insert([
                    'persona_id' => $pid,
                    'rfc' => strtoupper($faker->bothify('????######???')),
                    'razon_social' => 'PROV ' . strtoupper($faker->company),
                    'notas' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);

                DB::table('persona_correos')->updateOrInsert(
                    ['persona_id' => $pid, 'correo' => $faker->unique()->safeEmail],
                    [
                        'etiqueta' => 'principal',
                        'es_principal' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                        'baja' => false
                    ]
                );

                DB::table('persona_telefonos')->insert([
                    'persona_id' => $pid,
                    'etiqueta' => 'oficina',
                    'telefono' => $faker->phoneNumber,
                    'extension' => null,
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'baja' => false
                ]);
            }
        }
    }

    // =====================================================================
    //  MUCHOS: BOLETAS + PARTIDAS
    // =====================================================================
    private function seedBoletasYPagosClientes(int $boletasCount, int $maxPartidasPorBoleta, int $usuarioFallbackId): void
    {
        $faker = \Faker\Factory::create('es_MX');

        $usuarioIds = DB::table('usuarios')->where('baja', false)->pluck('id')->all();
        if (empty($usuarioIds)) $usuarioIds = [$usuarioFallbackId];

        $clienteIds  = DB::table('clientes')->where('baja', false)->pluck('id')->all();
        $vendedorIds = DB::table('vendedores')->where('baja', false)->pluck('id')->all();
        $socioIds    = DB::table('socios')->where('baja', false)->pluck('id')->all();

        // ✅ Mejora: preferir lotes LIBRES primero (si no hay, toma cualquiera)
        $loteRowsLibre = DB::table('lotes')
            ->where('baja', false)
            ->where('estado', 'LIBRE')
            ->select('id', 'lotificacion_id', 'estado', 'costo_contado', 'costo_credito')
            ->get();

        $loteRows = $loteRowsLibre->isEmpty()
            ? DB::table('lotes')->where('baja', false)->select('id', 'lotificacion_id', 'estado', 'costo_contado', 'costo_credito')->get()
            : $loteRowsLibre;

        if (empty($clienteIds) || $loteRows->isEmpty()) {
            $this->command?->warn('Seeder Boletas: faltan datos base (clientes/lotes). No se insertó nada.');
            return;
        }

        $tiposVenta   = ['CONTADO', 'CREDITO', 'APARTADO', 'OTRO'];
        $tiposPartida = ['ENGANCHE', 'ABONO', 'RECARGO', 'OTRO'];

        for ($i = 1; $i <= $boletasCount; $i++) {
            $lote = $loteRows[random_int(0, $loteRows->count() - 1)];

            $lotificacionId = (int)$lote->lotificacion_id;
            $loteId         = (int)$lote->id;

            $clienteId  = (int)$clienteIds[array_rand($clienteIds)];
            $vendedorId = (!empty($vendedorIds) && random_int(1, 100) <= 70) ? (int)$vendedorIds[array_rand($vendedorIds)] : null;
            $socioId    = (!empty($socioIds) && random_int(1, 100) <= 60) ? (int)$socioIds[array_rand($socioIds)] : null;

            $createdBy  = (int)$usuarioIds[array_rand($usuarioIds)];
            $updatedBy  = (random_int(1, 100) <= 60) ? (int)$usuarioIds[array_rand($usuarioIds)] : null;

            $tipoVenta     = $tiposVenta[array_rand($tiposVenta)];
            $fechaContrato = $faker->dateTimeBetween('-18 months', 'now')->format('Y-m-d');

            $costoContado = $this->money(max(15000, (float)($lote->costo_contado ?? 0) ?: $faker->numberBetween(20000, 180000)));
            $costoCredito = $this->money(max($costoContado, (float)($lote->costo_credito ?? 0) ?: ($costoContado + $faker->numberBetween(5000, 60000))));

            $meses = 0;
            if ($tipoVenta === 'CREDITO') {
                $meses = $faker->randomElement([6, 12, 18, 24, 36, 48]);
            } elseif ($tipoVenta === 'APARTADO') {
                $meses = $faker->randomElement([1, 2, 3, 4, 6]);
            }

            $enganche = $tipoVenta === 'CONTADO'
                ? $this->money($faker->numberBetween(0, (int)($costoContado * 0.30)))
                : $this->money($faker->numberBetween((int)($costoCredito * 0.05), (int)($costoCredito * 0.30)));

            $comisionVendedor = $vendedorId ? $this->money((float)$costoContado * $faker->randomFloat(3, 0.01, 0.06)) : 0;

            $boletaId = DB::table('boletas_pago')->insertGetId([
                'folio'             => $this->folio('BOL', 10),
                'cliente_id'        => $clienteId,
                'vendedor_id'       => $vendedorId,
                'lotificacion_id'   => $lotificacionId,
                'socio_id'          => $socioId,
                'lote_id'           => $loteId,
                'oficina'           => $faker->randomElement(['PUEBLA', 'CDMX', 'TLAXCALA', 'VERACRUZ', 'MATRIZ']),
                'fecha_contrato'    => $fechaContrato,
                'tipo_venta'        => $tipoVenta,
                'costo_contado'     => $costoContado,
                'costo_credito'     => $costoCredito,
                'enganche'          => $enganche,
                'comision_vendedor' => $comisionVendedor,
                'meses'             => $meses,
                'observaciones'     => (random_int(1, 100) <= 25) ? $faker->sentence(10) : null,
                'created_by'        => $createdBy,
                'updated_by'        => $updatedBy,
                'created_at'        => now(),
                'updated_at'        => now(),
                'baja'              => false
            ]);

            // marca como ocupado
            if (($lote->estado ?? 'LIBRE') === 'LIBRE') {
                DB::table('lotes')->where('id', $loteId)->update([
                    'estado' => 'OCUPADO',
                    'updated_at' => now()
                ]);
            }

            $saldoBase = ($tipoVenta === 'CONTADO') ? $costoContado : $costoCredito;
            $saldo = max(0, $saldoBase - $enganche);

            $partidasCount = ($tipoVenta === 'CREDITO')
                ? min($maxPartidasPorBoleta, max(3, (int)ceil($meses * $faker->randomFloat(2, 0.25, 0.75))))
                : $faker->numberBetween(1, min($maxPartidasPorBoleta, 10));

            $partidas = [];
            $folioCounter = 1;

            if ($enganche > 0) {
                $partidas[] = $this->boletaPartidaRow(
                    boletaId: $boletaId,
                    folioPartida: $this->folioPartida('BP', $boletaId, $folioCounter++),
                    fechaPago: $fechaContrato,
                    monto: $enganche,
                    recargo: false,
                    montoRecargo: 0,
                    tipoPago: 'ENGANCHE',
                    obs: 'Enganche',
                    usuarioId: $createdBy
                );
            }

            $fechaBase = Carbon::parse($fechaContrato);

            for ($p = 0; $p < $partidasCount; $p++) {
                if ($saldo <= 0) break;

                $isRecargo = random_int(1, 100) <= 10;
                $montoRecargo = $isRecargo ? $this->money($faker->numberBetween(50, 1500)) : 0;

                $monto = $this->money(min(
                    $saldo,
                    $faker->numberBetween(300, (int)max(500, $saldo * 0.40))
                ));

                $tipoPago = $isRecargo ? 'RECARGO' : $tiposPartida[array_rand($tiposPartida)];
                if ($tipoPago === 'ENGANCHE') $tipoPago = 'ABONO';

                $fechaPago = ($tipoVenta === 'CREDITO' || $tipoVenta === 'APARTADO')
                    ? $fechaBase->copy()->addMonths($p + 1)->format('Y-m-d')
                    : $faker->dateTimeBetween($fechaBase->copy()->addDays(1), 'now')->format('Y-m-d');

                $partidas[] = $this->boletaPartidaRow(
                    boletaId: $boletaId,
                    folioPartida: $this->folioPartida('BP', $boletaId, $folioCounter++),
                    fechaPago: $fechaPago,
                    monto: $monto,
                    recargo: $isRecargo,
                    montoRecargo: $montoRecargo,
                    tipoPago: $tipoPago,
                    obs: $isRecargo ? 'Recargo por atraso' : null,
                    usuarioId: (int)$usuarioIds[array_rand($usuarioIds)]
                );

                $saldo = max(0, $saldo - $monto);
            }

            if (!empty($partidas)) {
                DB::table('boletas_partidas')->insert($partidas);
            }
        }

        $this->command?->info('OK: Seed boletas_pago + boletas_partidas generado.');
    }

    private function boletaPartidaRow(
        int $boletaId,
        string $folioPartida,
        string $fechaPago,
        float $monto,
        bool $recargo,
        float $montoRecargo,
        string $tipoPago,
        ?string $obs,
        int $usuarioId
    ): array {
        return [
            'boleta_id'           => $boletaId,
            'folio_partida'       => $folioPartida,
            'fecha_pago'          => $fechaPago,
            'monto'               => $monto,
            'recargo'             => $recargo,
            'monto_recargo'       => $montoRecargo,
            'tipo_pago'           => $tipoPago,
            'observacion'         => $obs,
            'usuario_registro_id' => $usuarioId,
            'usuario_modifico_id' => null,
            'usuario_baja_id'     => null,
            'created_at'          => now(),
            'updated_at'          => now(),
            'baja'                => false
        ];
    }

    // =====================================================================
    //  MUCHOS: PAGO PROVEEDOR + PARTIDAS
    // =====================================================================
    private function seedPagosProveedores(int $pagosProveedorCount, int $maxPartidasPorPago, int $usuarioFallbackId): void
    {
        $faker = \Faker\Factory::create('es_MX');

        $usuarioIds = DB::table('usuarios')->where('baja', false)->pluck('id')->all();
        if (empty($usuarioIds)) $usuarioIds = [$usuarioFallbackId];

        $proveedorIds = DB::table('proveedores')->where('baja', false)->pluck('id')->all();
        if (empty($proveedorIds)) {
            $this->command?->warn('Seeder Proveedores: faltan proveedores. No se insertó nada.');
            return;
        }

        $formasPago = ['EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA','CHEQUE','OTRO'];
        $tiposPartidaProv = ['ANTICIPO','ABONO','PAGO_TOTAL','RETENCION','OTRO'];

        for ($i = 1; $i <= $pagosProveedorCount; $i++) {
            $proveedorId = (int)$proveedorIds[array_rand($proveedorIds)];
            $createdBy   = (int)$usuarioIds[array_rand($usuarioIds)];
            $updatedBy   = (random_int(1, 100) <= 50) ? (int)$usuarioIds[array_rand($usuarioIds)] : null;

            $fechaDoc = $faker->dateTimeBetween('-18 months', 'now')->format('Y-m-d');
            $montoTotalObjetivo = $this->money($faker->numberBetween(1500, 250000));

            $pagoProvId = DB::table('pago_proveedor')->insertGetId([
                'folio'           => $this->folio('PROV', 10),
                'proveedor_id'    => $proveedorId,
                'fecha_documento' => $fechaDoc,
                'fecha_registro'  => now(),
                'concepto'        => $faker->randomElement(['Material', 'Mano de obra', 'Servicios', 'Renta', 'Flete', 'Mantenimiento']),
                'referencia'      => 'FAC-' . strtoupper(Str::random(6)),
                'monto_total'     => $montoTotalObjetivo,
                'observaciones'   => (random_int(1, 100) <= 25) ? $faker->sentence(12) : null,
                'created_by'      => $createdBy,
                'updated_by'      => $updatedBy,
                'created_at'      => now(),
                'updated_at'      => now(),
                'baja'            => false
            ]);

            $partidasCount = $faker->numberBetween(1, $maxPartidasPorPago);
            $restante = $montoTotalObjetivo;
            $partidas = [];
            $folioCounter = 1;
            $fechaBase = Carbon::parse($fechaDoc);

            for ($p = 1; $p <= $partidasCount; $p++) {
                if ($restante <= 0) break;

                $tipo  = $tiposPartidaProv[array_rand($tiposPartidaProv)];
                $forma = $formasPago[array_rand($formasPago)];

                $monto = ($p === $partidasCount)
                    ? $this->money($restante)
                    : $this->money(min($restante, $faker->numberBetween(300, (int)max(600, $restante * 0.60))));

                $fechaPago = $fechaBase->copy()->addDays($faker->numberBetween(0, 120))->format('Y-m-d');

                $partidas[] = [
                    'pago_proveedor_id'   => $pagoProvId,
                    'folio_partida'       => $this->folioPartida('PP', $pagoProvId, $folioCounter++),
                    'fecha_pago'          => $fechaPago,
                    'forma_pago'          => $forma,
                    'tipo_partida'        => $tipo,
                    'monto'               => $monto,
                    'referencia_pago'     => $forma === 'TRANSFERENCIA'
                        ? 'TRX-' . strtoupper(Str::random(10))
                        : ($forma === 'CHEQUE' ? 'CH-' . $faker->numberBetween(1000, 99999) : null),
                    'observacion'         => (random_int(1, 100) <= 15) ? $faker->sentence(8) : null,
                    'usuario_registro_id' => (int)$usuarioIds[array_rand($usuarioIds)],
                    'usuario_modifico_id' => null,
                    'usuario_baja_id'     => null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                    'baja'                => false
                ];

                $restante = max(0, $restante - $monto);
            }

            if (!empty($partidas)) {
                DB::table('pago_proveedor_partidas')->insert($partidas);
            }
        }

        $this->command?->info('OK: Seed pago_proveedor + pago_proveedor_partidas generado.');
    }

    // =====================================================================
    //  HELPERS
    // =====================================================================
    private function money(float|int $value): float
    {
        return (float) number_format((float)$value, 2, '.', '');
    }

    private function folio(string $prefix, int $digits = 10): string
    {
        $rand = strtoupper(Str::random($digits));
        $date = now()->format('Ymd');
        return "{$prefix}-{$date}-{$rand}";
    }

    private function folioPartida(string $prefix, int $parentId, int $counter): string
    {
        $r = strtoupper(Str::random(4));
        return "{$prefix}-{$parentId}-" . str_pad((string)$counter, 4, '0', STR_PAD_LEFT) . "-{$r}";
    }
}