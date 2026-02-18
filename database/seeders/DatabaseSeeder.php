<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Limpieza opcional (NO borra tablas, solo limpia data para re-seed)
            // Si prefieres NO limpiar, comenta estas líneas.
            $this->truncateAll();

            // =========================
            // 1) ROLES
            // =========================
            $roleAdmin = $this->insertRole('ADMIN', 'Acceso total al sistema');
            $roleVentas = $this->insertRole('VENTAS', 'Operación de ventas/boletas');
            $roleCobranza = $this->insertRole('COBRANZA', 'Operación de cobros/partidas');
            $roleSistemas = $this->insertRole('SISTEMAS', 'Soporte/administración técnica');

            // =========================
            // 2) PERSONAS + USUARIOS
            // =========================
            $pAdmin = $this->insertPersona('Hugo', 'Admin', 'Demo', '1996-01-15', 'Usuario administrador demo');
            $pVentas = $this->insertPersona('Paola', 'Ventas', 'Demo', '1998-08-12', 'Usuario ventas demo');
            $pCobranza = $this->insertPersona('Carlos', 'Cobranza', 'Demo', '1997-03-03', 'Usuario cobranza demo');
            $pSistemas = $this->insertPersona('Sergio', 'Sistemas', 'Demo', '1995-11-20', 'Usuario sistemas demo');

            // Usuarios (password: 123456)
            $uAdmin = $this->insertUsuario($pAdmin, $roleAdmin, 'admin@demo.com', 'admin', '123456');
            $uVentas = $this->insertUsuario($pVentas, $roleVentas, 'ventas@demo.com', 'ventas', '123456');
            $uCobranza = $this->insertUsuario($pCobranza, $roleCobranza, 'cobranza@demo.com', 'cobranza', '123456');
            $uSistemas = $this->insertUsuario($pSistemas, $roleSistemas, 'sistemas@demo.com', 'sistemas', '123456');

            // Backfill baja_by en roles/personas si quieres dejar trazabilidad:
            DB::table('roles')->update(['baja_by' => $uAdmin]);
            DB::table('personas')->update(['baja_by' => $uAdmin]);

            // =========================
            // 3) CONTACTOS PERSONA
            // =========================
            $this->insertTelefono($pAdmin, 'principal', '555-111-1111', true, $uAdmin);
            $this->insertCorreo($pAdmin, 'principal', 'admin@demo.com', true, $uAdmin);
            $this->insertDireccion($pAdmin, 'principal', 'Av. Principal', '100', null, 'Centro', 'Tuxtla', 'Chiapas', '29000', 'Oficina', true, $uAdmin);

            $this->insertTelefono($pVentas, 'principal', '555-222-2222', true, $uAdmin);
            $this->insertCorreo($pVentas, 'principal', 'ventas@demo.com', true, $uAdmin);

            $this->insertTelefono($pCobranza, 'principal', '555-333-3333', true, $uAdmin);
            $this->insertCorreo($pCobranza, 'principal', 'cobranza@demo.com', true, $uAdmin);

            // =========================
            // 4) MODULOS (Jerarquía) + ROLES_MODULOS
            // =========================
            // Parents
            $mDashboard = $this->insertModulo('Dashboard', '/dashboard', 'fa-solid fa-gauge-high', null, true, 1, $uAdmin);
            $mCatalogos = $this->insertModulo('Catálogos', null, 'fa-solid fa-layer-group', null, true, 2, $uAdmin);
            $mOperaciones = $this->insertModulo('Operación', null, 'fa-solid fa-list-check', null, true, 3, $uAdmin);
            $mSeguridad = $this->insertModulo('Seguridad', null, 'fa-solid fa-shield-halved', null, true, 4, $uAdmin);

            // Children Catálogos
            $mLotificaciones = $this->insertModulo('Lotificaciones', '/lotificaciones', 'fa-solid fa-map', $mCatalogos, true, 1, $uAdmin);
            $mSocios = $this->insertModulo('Socios', '/socios', 'fa-solid fa-handshake', $mCatalogos, true, 2, $uAdmin);
            $mLotes = $this->insertModulo('Lotes', '/lotes', 'fa-solid fa-border-all', $mCatalogos, true, 3, $uAdmin);
            $mClientes = $this->insertModulo('Clientes', '/clientes', 'fa-solid fa-user-tag', $mCatalogos, true, 4, $uAdmin);
            $mVendedores = $this->insertModulo('Vendedores', '/vendedores', 'fa-solid fa-user-tie', $mCatalogos, true, 5, $uAdmin);
            $mEmpleados = $this->insertModulo('Empleados', '/empleados', 'fa-solid fa-id-card', $mCatalogos, true, 6, $uAdmin);

            // Children Operación
            $mBoletas = $this->insertModulo('Boletas de Pago', '/boletas', 'fa-solid fa-receipt', $mOperaciones, true, 1, $uAdmin);
            $mPartidas = $this->insertModulo('Partidas', '/partidas', 'fa-solid fa-money-bill-wave', $mOperaciones, true, 2, $uAdmin);
            $mSolicitudes = $this->insertModulo('Solicitudes', '/solicitudes', 'fa-solid fa-file-signature', $mOperaciones, true, 3, $uAdmin);

            // Children Seguridad
            $mUsuarios = $this->insertModulo('Usuarios', '/usuarios', 'fa-solid fa-users', $mSeguridad, true, 1, $uAdmin);
            $mRoles = $this->insertModulo('Roles', '/roles', 'fa-solid fa-id-badge', $mSeguridad, true, 2, $uAdmin);
            $mAuditoria = $this->insertModulo('Auditoría', '/auditoria', 'fa-solid fa-clock-rotate-left', $mSeguridad, true, 3, $uAdmin);

            // roles_modulos: ADMIN a todo
            $allModulos = [
                $mDashboard,$mCatalogos,$mOperaciones,$mSeguridad,
                $mLotificaciones,$mSocios,$mLotes,$mClientes,$mVendedores,$mEmpleados,
                $mBoletas,$mPartidas,$mSolicitudes,
                $mUsuarios,$mRoles,$mAuditoria
            ];
            foreach ($allModulos as $mid) {
                $this->attachRoleModulo($roleAdmin, $mid);
                $this->attachRoleModulo($roleSistemas, $mid);
            }

            // VENTAS: dashboard + catalogos relevantes + boletas
            foreach ([$mDashboard,$mCatalogos,$mLotificaciones,$mSocios,$mLotes,$mClientes,$mVendedores,$mBoletas] as $mid) {
                $this->attachRoleModulo($roleVentas, $mid);
            }

            // COBRANZA: dashboard + operación (partidas/solicitudes) + boletas
            foreach ([$mDashboard,$mOperaciones,$mBoletas,$mPartidas,$mSolicitudes] as $mid) {
                $this->attachRoleModulo($roleCobranza, $mid);
            }

            // =========================
            // 5) USUARIOS_ACCIONES_MODULO (Permisos por usuario)
            // =========================
            // Admin: todo
            foreach ($allModulos as $mid) {
                $this->upsertUsuarioAcciones($uAdmin, $mid, true, true, true, true);
            }

            // Ventas: ver + crear/modificar en boletas y catálogos, sin baja fuerte
            foreach ([$mDashboard,$mCatalogos,$mLotificaciones,$mSocios,$mLotes,$mClientes,$mVendedores] as $mid) {
                $this->upsertUsuarioAcciones($uVentas, $mid, true, true, true, false);
            }
            $this->upsertUsuarioAcciones($uVentas, $mBoletas, true, true, true, false);

            // Cobranza: partidas sí; boletas solo ver; solicitudes sí
            $this->upsertUsuarioAcciones($uCobranza, $mDashboard, true, false, false, false);
            $this->upsertUsuarioAcciones($uCobranza, $mBoletas, true, false, false, false);
            $this->upsertUsuarioAcciones($uCobranza, $mPartidas, true, true, true, false);
            $this->upsertUsuarioAcciones($uCobranza, $mSolicitudes, true, true, true, false);

            // =========================
            // 6) CLIENTES / VENDEDORES / EMPLEADOS (Persona + entidad)
            // =========================
            // Clientes
            $pCliente1 = $this->insertPersona('Ana', 'López', 'Hernández', '1999-05-10', 'Cliente demo 1');
            $pCliente2 = $this->insertPersona('Luis', 'Martínez', 'Gómez', '1988-02-22', 'Cliente demo 2');

            $c1 = $this->insertCliente($pCliente1, 'LOHA990510XXX', 'general', 'Cliente frecuente', $uAdmin);
            $c2 = $this->insertCliente($pCliente2, 'MAGL880222XXX', 'general', null, $uAdmin);

            // Vendedores
            $pVend1 = $this->insertPersona('Mario', 'Vendedor', 'Uno', '1992-07-02', 'Vendedor demo 1');
            $pVend2 = $this->insertPersona('Karla', 'Vendedor', 'Dos', '1994-09-18', 'Vendedor demo 2');

            $v1 = $this->insertVendedor($pVend1, 5.00, 'VEND-001', $uAdmin);
            $v2 = $this->insertVendedor($pVend2, 7.50, 'VEND-002', $uAdmin);

            // Empleados
            $pEmp1 = $this->insertPersona('Brenda', 'Auxiliar', 'Admin', '1993-12-01', 'Empleado demo 1');
            $pEmp2 = $this->insertPersona('Oscar', 'Supervisor', 'Zona', '1990-06-14', 'Empleado demo 2');

            $e1 = $this->insertEmpleado($pEmp1, 'AUXILIAR_ADMIN', null, 'EMP-001', 'Operación oficina', $uAdmin);
            $e2 = $this->insertEmpleado($pEmp2, 'SUPERVISOR', 'Supervisor de campo', 'EMP-002', 'Revisión lotes', $uAdmin);

            // =========================
            // 7) VARIABLES GLOBALES (JSONB)
            // =========================
            $this->insertVariableGlobal('app_config', [
                'empresa' => 'Lotificaciones Demo',
                'moneda' => 'MXN',
                'timezone' => 'America/Mexico_City',
                'imprimir_logo' => true,
            ], 'Configuración base de la app', $uAdmin);

            $this->insertVariableGlobal('mobile_flags', [
                'require_update' => false,
                'min_version' => '1.0.0',
                'features' => [
                    'boletas' => true,
                    'partidas' => true,
                    'croquis' => true,
                ],
            ], 'Flags para app móvil', $uAdmin);

            // =========================
            // 8) LOTIFICACIONES / SOCIOS / LOTES
            // =========================
            $lot1 = $this->insertLotificacion('Fracc. Las Palmas', [
                'version' => 1,
                'nota' => 'Croquis demo',
                'poligonos' => [
                    ['clave' => 'A-01', 'points' => [[10,10],[80,10],[80,60],[10,60]]],
                    ['clave' => 'A-02', 'points' => [[90,10],[160,10],[160,60],[90,60]]],
                ]
            ], 8, 'Oficina Centro', 'Chiapas', $uAdmin);

            $lot2 = $this->insertLotificacion('Residencial El Sol', [
                'version' => 1,
                'nota' => 'Croquis demo 2',
                'poligonos' => []
            ], 6, 'Oficina Norte', 'Chiapas', $uAdmin);

            $socio1 = $this->insertSocio('Socio Principal', '#2D6CDF', '555-777-0001', 'socio1@demo.com', $uAdmin);
            $socio2 = $this->insertSocio('Socio Secundario', '#F59E0B', '555-777-0002', 'socio2@demo.com', $uAdmin);

            $this->attachLotificacionSocio($lot1, $socio1);
            $this->attachLotificacionSocio($lot1, $socio2);
            $this->attachLotificacionSocio($lot2, $socio1);

            // Lotes lot1
            $loteA1 = $this->insertLote($lot1, 'A-01', 'A', '01', 'LIBRE', 120000, 150000, 'Lote demo', $uAdmin);
            $loteA2 = $this->insertLote($lot1, 'A-02', 'A', '02', 'LIBRE', 125000, 155000, null, $uAdmin);
            $loteA3 = $this->insertLote($lot1, 'A-03', 'A', '03', 'LIBRE', 110000, 140000, null, $uAdmin);

            // Lotes lot2
            $loteB1 = $this->insertLote($lot2, 'B-01', 'B', '01', 'LIBRE', 98000, 120000, null, $uAdmin);
            $loteB2 = $this->insertLote($lot2, 'B-02', 'B', '02', 'LIBRE', 105000, 130000, null, $uAdmin);

            // =========================
            // 9) BOLETAS + PARTIDAS
            // =========================
            $folio1 = 'BOL-' . now()->format('Ymd') . '-001';
            $boleta1 = $this->insertBoletaPago([
                'folio' => $folio1,
                'cliente_id' => $c1,
                'vendedor_id' => $v1,
                'lotificacion_id' => $lot1,
                'socio_id' => $socio1,
                'lote_id' => $loteA1,
                'oficina' => 'Oficina Centro',
                'fecha_contrato' => now()->toDateString(),
                'tipo_venta' => 'CREDITO',
                'costo_contado' => 120000,
                'costo_credito' => 150000,
                'enganche' => 15000,
                'comision_vendedor' => 5000,
                'meses' => 12,
                'observaciones' => 'Boleta demo crédito',
                'created_by' => $uVentas,
                'updated_by' => $uVentas,
                'baja_by' => null,
            ]);

            // Marca lote como OCUPADO por venta (opcional demo)
            DB::table('lotes')->where('id', $loteA1)->update(['estado' => 'OCUPADO', 'updated_at' => now()]);

            // Partidas boleta1
            $this->insertPartida($boleta1, 'P-001', now()->subDays(10)->toDateString(), 15000, false, 0, 'ENGANCHE', 'Enganche', $uCobranza);
            $this->insertPartida($boleta1, 'P-002', now()->subDays(3)->toDateString(), 5000, false, 0, 'ABONO', 'Abono 1', $uCobranza);
            $this->insertPartida($boleta1, 'P-003', now()->subDays(1)->toDateString(), 5000, true, 250, 'ABONO', 'Abono 2 con recargo', $uCobranza);

            // Boleta contado
            $folio2 = 'BOL-' . now()->format('Ymd') . '-002';
            $boleta2 = $this->insertBoletaPago([
                'folio' => $folio2,
                'cliente_id' => $c2,
                'vendedor_id' => $v2,
                'lotificacion_id' => $lot2,
                'socio_id' => $socio1,
                'lote_id' => $loteB1,
                'oficina' => 'Oficina Norte',
                'fecha_contrato' => now()->subDays(15)->toDateString(),
                'tipo_venta' => 'CONTADO',
                'costo_contado' => 98000,
                'costo_credito' => 0,
                'enganche' => 98000,
                'comision_vendedor' => 6000,
                'meses' => 0,
                'observaciones' => 'Boleta demo contado',
                'created_by' => $uVentas,
                'updated_by' => $uVentas,
                'baja_by' => null,
            ]);

            DB::table('lotes')->where('id', $loteB1)->update(['estado' => 'OCUPADO', 'updated_at' => now()]);
            $this->insertPartida($boleta2, 'P-001', now()->subDays(15)->toDateString(), 98000, false, 0, 'ENGANCHE', 'Pago contado', $uCobranza);

            // =========================
            // 10) SOLICITUDES (demo)
            // =========================
            $this->insertSolicitud([
                'tipo' => 'MODIFICACION',
                'estatus' => 'PENDIENTE',
                'modulo_id' => $mBoletas,
                'tabla_objetivo' => 'boletas_pago',
                'registro_id' => $boleta1,
                'motivo' => 'Cambiar observación del contrato',
                'payload' => [
                    'observaciones' => 'Nueva observación solicitada'
                ],
                'solicitado_por' => $uVentas,
                'revisado_por' => null,
                'decision_motivo' => null,
            ]);

            // =========================
            // 11) AUDITORÍA (acciones_usuario_historial) demo
            // =========================
            if ($this->tableExists('acciones_usuario_historial')) {
                $this->insertAudit($uAdmin, $mUsuarios, 'CREAR', 'usuarios', $uAdmin, null, ['seed' => true, 'nota' => 'Creación usuario admin']);
                $this->insertAudit($uVentas, $mBoletas, 'CREAR', 'boletas_pago', $boleta1, null, ['folio' => $folio1]);
                $this->insertAudit($uCobranza, $mPartidas, 'CREAR', 'boletas_partidas', null, null, ['boleta_id' => $boleta1, 'folio_partida' => 'P-001']);
            }

        });
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function truncateAll(): void
    {
        // Orden con FKs (hijos -> padres)
        $tables = [
            'acciones_usuario_historial',
            'solicitudes',
            'boletas_partidas',
            'boletas_pago',
            'lotes',
            'lotificacion_socios',
            'socios',
            'lotificaciones',
            'variables_globales',
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
            'personas',
            'roles',
        ];

        DB::statement('SET session_replication_role = replica;'); // desactiva FKs temporalmente (Postgres)
        foreach ($tables as $t) {
            if ($this->tableExists($t)) {
                DB::table($t)->truncate();
            }
        }
        DB::statement('SET session_replication_role = DEFAULT;');
    }

    private function tableExists(string $name): bool
    {
        return DB::selectOne("SELECT to_regclass(?) as t", [$name])?->t !== null;
    }

    private function insertRole(string $nombre, ?string $descripcion): int
    {
        return DB::table('roles')->insertGetId([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
        ]);
    }

    private function insertPersona(string $nombres, string $apPat, ?string $apMat, ?string $fn, ?string $notas): int
    {
        return DB::table('personas')->insertGetId([
            'nombres' => $nombres,
            'apellido_paterno' => $apPat,
            'apellido_materno' => $apMat,
            'fecha_nacimiento' => $fn,
            'notas' => $notas,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
        ]);
    }

    private function insertUsuario(int $personaId, int $roleId, ?string $email, ?string $username, string $passwordPlain): int
    {
        return DB::table('usuarios')->insertGetId([
            'persona_id' => $personaId,
            'role_id' => $roleId,
            'email' => $email,
            'username' => $username,
            'password_hash' => Hash::make($passwordPlain),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
        ]);
    }

    private function insertTelefono(int $personaId, string $etiqueta, string $tel, bool $principal, int $by): int
    {
        return DB::table('persona_telefonos')->insertGetId([
            'persona_id' => $personaId,
            'etiqueta' => $etiqueta,
            'telefono' => $tel,
            'extension' => null,
            'es_principal' => $principal,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertCorreo(int $personaId, string $etiqueta, string $correo, bool $principal, int $by): int
    {
        return DB::table('persona_correos')->insertGetId([
            'persona_id' => $personaId,
            'etiqueta' => $etiqueta,
            'correo' => $correo,
            'es_principal' => $principal,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertDireccion(
        int $personaId,
        string $etiqueta,
        ?string $calle,
        ?string $ext,
        ?string $int,
        ?string $colonia,
        ?string $municipio,
        ?string $estado,
        ?string $cp,
        ?string $ref,
        bool $principal,
        int $by
    ): int {
        return DB::table('persona_direcciones')->insertGetId([
            'persona_id' => $personaId,
            'etiqueta' => $etiqueta,
            'calle' => $calle,
            'numero_ext' => $ext,
            'numero_int' => $int,
            'colonia' => $colonia,
            'municipio' => $municipio,
            'estado' => $estado,
            'cp' => $cp,
            'referencias' => $ref,
            'es_principal' => $principal,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertModulo(string $nombre, ?string $ruta, ?string $icono, ?int $parentId, bool $esMenu, int $orden, int $by): int
    {
        return DB::table('modulos')->insertGetId([
            'nombre' => $nombre,
            'ruta' => $ruta,
            'icono' => $icono,
            'parent_id' => $parentId,
            'es_menu' => $esMenu,
            'orden' => $orden,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function attachRoleModulo(int $roleId, int $moduloId): void
    {
        DB::table('roles_modulos')->insert([
            'role_id' => $roleId,
            'modulo_id' => $moduloId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertUsuarioAcciones(int $usuarioId, int $moduloId, bool $ver, bool $crear, bool $mod, bool $baja): void
    {
        DB::table('usuarios_acciones_modulo')->updateOrInsert(
            ['usuario_id' => $usuarioId, 'modulo_id' => $moduloId],
            [
                'puede_ver' => $ver,
                'puede_crear' => $crear,
                'puede_modificar' => $mod,
                'puede_baja' => $baja,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function insertCliente(int $personaId, ?string $rfc, ?string $tipo, ?string $notas, int $by): int
    {
        return DB::table('clientes')->insertGetId([
            'persona_id' => $personaId,
            'rfc' => $rfc,
            'tipo_cliente' => $tipo ?? 'general',
            'notas' => $notas,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertVendedor(int $personaId, float $comisionDefault, ?string $clave, int $by): int
    {
        return DB::table('vendedores')->insertGetId([
            'persona_id' => $personaId,
            'comision_default' => $comisionDefault,
            'clave' => $clave,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertEmpleado(int $personaId, string $puestoEnum, ?string $puestoDetalle, ?string $numeroEmpleado, ?string $obs, int $by): int
    {
        return DB::table('empleados')->insertGetId([
            'persona_id' => $personaId,
            'puesto' => $puestoEnum, // ENUM puesto_empleado
            'puesto_detalle' => $puestoDetalle,
            'numero_empleado' => $numeroEmpleado,
            'observaciones' => $obs,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertVariableGlobal(string $nombre, array $valor, ?string $descripcion, int $by): int
    {
        return DB::table('variables_globales')->insertGetId([
            'nombre' => $nombre,
            'valor' => json_encode($valor),
            'descripcion' => $descripcion,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertLotificacion(string $nombre, ?array $jsonCroquis, int $numeroLotes, ?string $oficina, ?string $estado, int $by): int
    {
        return DB::table('lotificaciones')->insertGetId([
            'nombre' => $nombre,
            'json_croquis' => $jsonCroquis ? json_encode($jsonCroquis) : null,
            'numero_lotes' => $numeroLotes,
            'oficina' => $oficina,
            'estado' => $estado,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertSocio(string $nombre, string $color, ?string $tel, ?string $email, int $by): int
    {
        return DB::table('socios')->insertGetId([
            'nombre' => $nombre,
            'color' => $color,
            'telefono' => $tel,
            'email' => $email,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function attachLotificacionSocio(int $lotificacionId, int $socioId): void
    {
        DB::table('lotificacion_socios')->insert([
            'lotificacion_id' => $lotificacionId,
            'socio_id' => $socioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertLote(
        int $lotificacionId,
        string $claveLote,
        ?string $manzana,
        ?string $numero,
        string $estadoEnum,
        float $contado,
        float $credito,
        ?string $notas,
        int $by
    ): int {
        return DB::table('lotes')->insertGetId([
            'lotificacion_id' => $lotificacionId,
            'clave_lote' => $claveLote,
            'manzana' => $manzana,
            'numero' => $numero,
            'estado' => $estadoEnum, // ENUM lote_estado
            'costo_contado' => $contado,
            'costo_credito' => $credito,
            'notas' => $notas,
            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
            'baja_by' => $by,
        ]);
    }

    private function insertBoletaPago(array $x): int
    {
        return DB::table('boletas_pago')->insertGetId([
            'folio' => $x['folio'],

            'cliente_id' => $x['cliente_id'],
            'vendedor_id' => $x['vendedor_id'] ?? null,

            'lotificacion_id' => $x['lotificacion_id'],
            'socio_id' => $x['socio_id'] ?? null,
            'lote_id' => $x['lote_id'],

            'oficina' => $x['oficina'] ?? null,
            'fecha_contrato' => $x['fecha_contrato'],
            'tipo_venta' => $x['tipo_venta'] ?? 'CONTADO', // ENUM tipo_venta

            'costo_contado' => $x['costo_contado'] ?? 0,
            'costo_credito' => $x['costo_credito'] ?? 0,

            'enganche' => $x['enganche'] ?? 0,
            'comision_vendedor' => $x['comision_vendedor'] ?? 0,
            'meses' => $x['meses'] ?? 0,

            'observaciones' => $x['observaciones'] ?? null,

            'created_by' => $x['created_by'] ?? null,
            'updated_by' => $x['updated_by'] ?? null,

            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
        ]);
    }

    private function insertPartida(
        int $boletaId,
        string $folioPartida,
        string $fechaPago,
        float $monto,
        bool $recargo,
        float $montoRecargo,
        string $tipoPagoEnum,
        ?string $obs,
        int $usuarioRegistro
    ): int {
        return DB::table('boletas_partidas')->insertGetId([
            'boleta_id' => $boletaId,
            'folio_partida' => $folioPartida,
            'fecha_pago' => $fechaPago,
            'monto' => $monto,

            'recargo' => $recargo,
            'monto_recargo' => $montoRecargo,

            'tipo_pago' => $tipoPagoEnum, // ENUM tipo_partida_pago
            'observacion' => $obs,

            'usuario_registro_id' => $usuarioRegistro,
            'usuario_modifico_id' => null,
            'usuario_baja_id' => null,

            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
        ]);
    }

    private function insertSolicitud(array $x): int
    {
        return DB::table('solicitudes')->insertGetId([
            'tipo' => $x['tipo'], // ENUM solicitud_tipo
            'estatus' => $x['estatus'] ?? 'PENDIENTE', // ENUM solicitud_estatus

            'modulo_id' => $x['modulo_id'] ?? null,

            'tabla_objetivo' => $x['tabla_objetivo'],
            'registro_id' => $x['registro_id'],

            'motivo' => $x['motivo'] ?? null,
            'payload' => isset($x['payload']) ? json_encode($x['payload']) : null,

            'solicitado_por' => $x['solicitado_por'],
            'solicitado_at' => now(),

            'revisado_por' => $x['revisado_por'] ?? null,
            'revisado_at' => null,
            'decision_motivo' => $x['decision_motivo'] ?? null,

            'created_at' => now(),
            'updated_at' => now(),
            'baja' => false,
        ]);
    }

    private function insertAudit(int $usuarioId, ?int $moduloId, string $accion, string $tabla, ?int $registroId, $before, $after): void
    {
        DB::table('acciones_usuario_historial')->insert([
            'usuario_id' => $usuarioId,
            'modulo_id' => $moduloId,
            'accion' => strtoupper($accion),
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'ip' => '127.0.0.1',
            'user_agent' => 'Seeder/DatabaseSeeder',
            'before_data' => $before ? json_encode($before) : null,
            'after_data' => $after ? json_encode($after) : null,
            'created_at' => now(),
        ]);
    }
}
