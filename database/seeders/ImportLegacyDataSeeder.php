<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\EquipoCelular;
use App\Models\EquipoComputo;
use App\Models\Marca;
use App\Models\RazonSocial;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Importa los datos reales de la versión anterior del proyecto
 * (dump myclethc_gh.sql) al esquema actual.
 *
 * Notas de la migración de datos:
 * - La tabla `sucursales` original NO tenía razon_social_id (era el bug que
 *   arreglamos). El equipo #11 (Regina Guerrero) usaba la sucursal
 *   "Constituyentes 1080" pero con la razón social "GHDININGHOSPITALITY",
 *   mientras que el resto de registros usan esa misma sucursal bajo
 *   "GH OPERACION DE RESTAURANTES". Como ahora una sucursal pertenece a una
 *   sola razón social, se crea una sucursal "Constituyentes 1080" por cada
 *   razón social que realmente la usó en los datos originales.
 * - Los PDFs de responsiva no venían en el dump (solo el nombre de archivo),
 *   así que se conserva el nombre como referencia pero el archivo no estará
 *   disponible para descargar hasta que se suba manualmente.
 * - `sistema_operativo` es un campo nuevo que no existía en el dump; se
 *   marca como 'windows' para los 11 equipos importados porque todos traían
 *   una versión de Windows capturada.
 *
 * Ejecutar con:
 *   php artisan db:seed --class=ImportLegacyDataSeeder
 */
class ImportLegacyDataSeeder extends Seeder
{
    public function run(): void
    {
        $areas = $this->importAreas();
        $razonesSociales = $this->importRazonesSociales();
        $sucursales = $this->importSucursales($razonesSociales);
        $marcas = $this->importMarcas();

        $this->importEquiposComputo($razonesSociales, $sucursales, $areas, $marcas);
        $this->importEquiposCelulares($razonesSociales, $sucursales, $areas, $marcas);
        $this->importUsuarios();

        $this->command->info('✅ Datos reales importados desde la versión anterior del proyecto.');
    }

    /** @return array<int, Area> */
    private function importAreas(): array
    {
        $nombres = [
            1 => 'Recursos Humanos',
            2 => 'Compras',
            3 => 'Mensajero',
            4 => 'Operaciones',
            5 => 'Marketing',
            6 => 'Gerencia',
        ];

        $areas = [];
        foreach ($nombres as $oldId => $nombre) {
            $areas[$oldId] = Area::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }

        return $areas;
    }

    /** @return array<int, RazonSocial> */
    private function importRazonesSociales(): array
    {
        $datos = [
            1 => ['nombre' => 'GH OPERACION DE RESTAURANTES', 'rfc' => 'GOR160425KC7'],
            2 => ['nombre' => 'GHDININGHOSPITALITY', 'rfc' => 'CCN1010268M6'],
        ];

        $razonesSociales = [];
        foreach ($datos as $oldId => $data) {
            $razonesSociales[$oldId] = RazonSocial::firstOrCreate(
                ['nombre' => $data['nombre']],
                ['rfc' => $data['rfc'], 'activo' => true]
            );
        }

        return $razonesSociales;
    }

    /**
     * Clave del mapa: "{old_sucursal_id}:{old_razon_social_id}", porque en
     * los datos originales una misma sucursal se usó bajo dos razones
     * sociales distintas (ver nota de la clase).
     *
     * @param array<int, RazonSocial> $razonesSociales
     * @return array<string, Sucursal>
     */
    private function importSucursales(array $razonesSociales): array
    {
        $datos = [
            ['old_id' => 1, 'old_razon_social_id' => 1, 'nombre' => 'Constituyentes 1080', 'ciudad' => 'CDMX'],
            ['old_id' => 2, 'old_razon_social_id' => 1, 'nombre' => 'Costa Guadiana', 'ciudad' => 'CDMX'],
            ['old_id' => 3, 'old_razon_social_id' => 1, 'nombre' => 'Hacienda Cocina y Cantina', 'ciudad' => 'Los cabos'],
            ['old_id' => 1, 'old_razon_social_id' => 2, 'nombre' => 'Constituyentes 1080', 'ciudad' => 'CDMX'],
        ];

        $sucursales = [];
        foreach ($datos as $data) {
            $key = $data['old_id'] . ':' . $data['old_razon_social_id'];
            $sucursales[$key] = Sucursal::firstOrCreate(
                [
                    'razon_social_id' => $razonesSociales[$data['old_razon_social_id']]->id,
                    'nombre' => $data['nombre'],
                ],
                ['ciudad' => $data['ciudad'], 'activo' => true]
            );
        }

        return $sucursales;
    }

    /** @return array<int, Marca> */
    private function importMarcas(): array
    {
        $datos = [
            1 => ['nombre' => 'Lenovo', 'categoria' => 'computo'],
            2 => ['nombre' => 'Dell', 'categoria' => 'computo'],
            3 => ['nombre' => 'Samsung', 'categoria' => 'computo'],
            4 => ['nombre' => 'Apple', 'categoria' => 'ambas'],
        ];

        $marcas = [];
        foreach ($datos as $oldId => $data) {
            $marcas[$oldId] = Marca::firstOrCreate(
                ['nombre' => $data['nombre']],
                ['categoria' => $data['categoria'], 'activo' => true]
            );
        }

        return $marcas;
    }

    private function importEquiposComputo(array $razonesSociales, array $sucursales, array $areas, array $marcas): void
    {
        $filas = [
            [1, 'baja', '2025-02-07 10:47:00', '2026-06-23', 1, 'Laura Ramirez', 'lramirez@grupohunan.com', 1, 1, '1281', 'laptop', 1, 'IdeaPad 1 15ALC7', 'PF547YRF', 'Ryzen 7 5700u', '16 GB', 'SSD 512 GB', 'Se entrega equipo nuevo con un adaptador multifuncional con 3 puertos USB y un puerto Ethernet.', 'Laura', null, '01KVTPNKFJRJY322FD77VF4H11.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'lramirez@grupohunan.com', '2026-06-23 16:57:58', '2026-06-24 20:46:46'],
            [2, 'alta', '2025-02-05 11:01:00', null, 1, 'Armando Ulloa', 'acontreras@grupohunan.com', 1, 2, null, 'laptop', 2, 'Inspiron 3480', 'D55QTW2', 'Core i5 8365U', '8 GB', 'SSD 256 GB', 'Se entrega equipo en stock.', 'Armando', null, '01KVTT86Q1N0RATPDMD4SJQZSY.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'acontreras@grupohunan.com', '2026-06-23 18:00:33', '2026-06-24 20:47:43'],
            [3, 'alta', '2025-03-11 12:01:00', null, 1, 'Francisco Daniel Cruz Uriel', 'na@na.com', 1, 3, null, 'laptop', 1, 'A14', 'R58W81H6SNL', null, '4 GB', 'SSD 128 GB', 'Se entrega equipo nuevo', 'Francisco', null, '01KVTYPHQ05TFJF9AG80PV8WAQ.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'na@na.com', '2026-06-23 19:18:17', '2026-06-23 19:18:17'],
            [4, 'baja', '2025-03-18 10:40:00', '2026-06-24', 1, 'Laura Ramirez', 'lramirez@grupohunan.com', 1, 1, '1281', 'laptop', 1, 'IdeaPad S145', 'PF1S1MDW', 'Core i3-8145U', '8 GB', 'SSD 256 GB', 'Se entrega equipo en stock sin tecla ESC', 'Laura', null, '01KVX99NJY7C39KRG0YRQAQKCY.pdf', 'Windows 10 Pro', 'Office 2021', 'Windows Defender', 'lramirez@grupohunan.com', '2026-06-24 17:01:58', '2026-06-24 17:02:17'],
            [5, 'alta', '2025-03-25 13:48:00', null, 1, 'Federico Gerdes', 'fgerdes@grupohunan.com', 1, 4, null, 'laptop', 2, 'Inspiron 15 3520', '22N1854', 'Core i7 1255U', '16 GB', 'SSD 512 GB', 'Se entrega equipo nuevo', 'Federico', null, '01KVXKQ0K0DQXQBGSWEM3N3BNJ.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'fgerdes@grupohunan.com', '2026-06-24 20:02:55', '2026-06-24 20:04:01'],
            [7, 'alta', '2025-04-02 14:04:00', null, 1, 'Carlos Morelos', 'cmorelos@grupohunan.com', 1, 4, null, 'laptop', 2, 'Inspiron 15 3520', 'PENDIENTE-CARLOS-MORELOS', 'Core i7 1255U', '16 GB', 'SSD 500 GB', 'Se entrega equipo nuevo. ATENCION: numero de serie pendiente de verificar, en el dump original venia duplicado (22N1854) con el equipo de Federico Gerdes.', 'Federico', null, '01KVXMHJ1DZWEPCCHT76HWEC0A.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'cmorelos@grupohunan.com', '2026-06-24 20:17:34', '2026-06-24 20:18:31'],
            [8, 'alta', '2025-04-21 14:22:00', null, 1, 'Daniela Chavez Valle', 'dchavez@grupohunan.com', 1, 5, null, 'laptop', 2, 'Inspiron 15 3520', 'PENDIENTE-DANIELA-CHAVEZ', 'Core i7 1255U', '16 GB', 'SSD 512 GB', 'Se entrega equipo nuevo. ATENCION: numero de serie pendiente de verificar, en el dump original venia duplicado (22N1854) con el equipo de Federico Gerdes.', 'Daniela', null, '01KVXN03E9WWRH11QXB5FQZXCY.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'dchavez@grupohunan.com', '2026-06-24 20:26:28', '2026-06-24 20:26:28'],
            [9, 'alta', '2025-04-22 15:01:00', null, 1, 'Yury Edson Arce Peralta', 'yarce@grupohunan.com', 3, 6, null, 'laptop', 2, 'Inspiron 15 3535', 'H7JPV64', 'Ryzen', '16 GB', 'SSD 1 TB', 'Se entrega equipo nuevo', 'Edson', null, '01KVXQKY6V6EVNF8G7SQACPNQK.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'yarce@grupohunan.com', '2026-06-24 21:12:15', '2026-06-24 21:12:15'],
            [10, 'alta', '2025-04-22 10:59:00', null, 1, 'Edwin Mendoza', 'emendoza@grupohunan.com', 3, 6, null, 'laptop', 2, 'Inspiron 3535', '9D7SV64', 'Ryzen', '16 GB', 'SSD 1 TB', 'Se entrega equipo nuevo', 'Edwin', null, '01KWA6J0NNDRZZQ4MXTRBXHB4Y.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', 'emendoza@grupohunan.com', '2026-06-29 17:24:14', '2026-06-29 17:24:14'],
            [11, 'alta', '2026-08-25 11:31:00', null, 2, 'Regina Guerrero Olavarrieta', 'rguerrero@grupohunan.com', 1, 5, '1155', 'all_in_one', 4, 'iMac', 'CVDHWHQWTV', '10 Nucleos', '16', 'SSD 512 GB', 'Se entrega equipo nuevo', null, '1234', '01M0X1S9FVPN4R5GSCDXPFQQM6.pdf', 'Windows 11 Pro', 'Office 2021', null, 'rguerrero@grupohunan.com', '2026-08-25 18:09:11', '2026-08-25 18:09:11'],
        ];

        foreach ($filas as $fila) {
            [
                , $tipoMovimiento, $fechaEntrega, $fechaBaja, $razonSocialOld, $nombreUsuario, $correo,
                $sucursalOld, $areaOld, $ext, $tipoEquipo, $marcaOld, $modelo, $numeroSerie, $procesador,
                $ram, $almacenamiento, $observaciones, $usuarioEquipo, $pinPassword, $responsivaPdf,
                $windowsVersion, $officeVersion, $antivirusNombre, $usuarioReferencia, $createdAt, $updatedAt,
            ] = $fila;

            if (EquipoComputo::where('numero_serie', $numeroSerie)->exists()) {
                continue;
            }

            $sucursal = $sucursales[$sucursalOld . ':' . $razonSocialOld];

            $equipo = EquipoComputo::create([
                'tipo_movimiento' => $tipoMovimiento,
                'fecha_entrega' => $fechaEntrega,
                'fecha_baja' => $fechaBaja,
                'razon_social_id' => $razonesSociales[$razonSocialOld]->id,
                'nombre_usuario' => $nombreUsuario,
                'correo_electronico' => $correo,
                'sucursal_id' => $sucursal->id,
                'area_id' => $areas[$areaOld]->id,
                'ext' => $ext,
                'tipo_equipo' => $tipoEquipo,
                'marca_id' => $marcas[$marcaOld]->id,
                'modelo' => $modelo,
                'numero_serie' => $numeroSerie,
                'procesador' => $procesador,
                'ram' => $ram,
                'almacenamiento' => $almacenamiento,
                'observaciones' => $observaciones,
                'usuario_equipo' => $usuarioEquipo,
                'pin_password' => $pinPassword,
                'responsiva_pdf' => $responsivaPdf,
                'sistema_operativo' => 'windows',
                'windows_version' => $windowsVersion,
                'office_version' => $officeVersion,
                'antivirus_nombre' => $antivirusNombre,
                'usuario_referencia' => $usuarioReferencia,
            ]);

            $equipo->created_at = $createdAt;
            $equipo->updated_at = $updatedAt;
            $equipo->saveQuietly();
        }
    }

    private function importEquiposCelulares(array $razonesSociales, array $sucursales, array $areas, array $marcas): void
    {
        if (EquipoCelular::where('imei', '354499632153470')->exists()) {
            return;
        }

        $equipo = EquipoCelular::create([
            'tipo_movimiento' => 'alta',
            'fecha_entrega' => '2026-06-24 14:29:00',
            'razon_social_id' => $razonesSociales[1]->id,
            'nombre_usuario' => 'Christinne Torres',
            'sucursal_id' => $sucursales['2:1']->id,
            'area_id' => $areas[6]->id,
            'tipo_equipo' => 'celular',
            'marca_id' => $marcas[3]->id,
            'modelo' => 'A17',
            'numero_telefonico' => '55 5331 8945',
            'imei' => '354499632153470',
            'iccid' => '8952020525691663955',
            'curp' => 'TOBC128508MMCRXH07',
            'observaciones' => 'Se entrega equipo nuevo con sim y una sim extra',
            'responsiva_pdf' => '01KVXNR4ZZCFHZ7AZ2XFRQPBHK.pdf',
            'usuario_referencia' => 'christinne.torres',
        ]);

        $equipo->created_at = '2026-06-24 20:39:36';
        $equipo->updated_at = '2026-06-24 20:39:36';
        $equipo->saveQuietly();
    }

    private function importUsuarios(): void
    {
        $colaborador = Role::firstOrCreate(['name' => 'Colaborador', 'guard_name' => 'web']);
        $colaborador->syncPermissions(
            Permission::where(function ($query) {
                $query->where('name', 'like', '%:EntregaDispositivo')
                    ->orWhere('name', 'like', '%:EquipoCelular')
                    ->orWhere('name', 'like', '%:EquipoComputo');
            })->get()
        );

        $usuarios = [
            ['name' => 'Axel', 'email' => 'hramirez@grupohunan.com', 'hash' => '$2y$12$H9HqyH5ZNc3LT2mI1fCx..H2Kd2W0B0X5Eq686urPgxZtCt1zDQlC', 'verified' => '2026-06-05 21:10:21', 'created' => '2026-06-05 20:54:31', 'role' => 'super_admin'],
            ['name' => 'Carlos Velazquez', 'email' => 'cvelazquez@grupohunan.com', 'hash' => '$2y$12$Po1Mw6xpnNADxXMFDRdBOez1kA4IN98LbOP54mkMpgNDc1QGbhgbO', 'verified' => '2026-06-22 20:23:13', 'created' => '2026-06-22 20:23:13', 'role' => 'Colaborador'],
            ['name' => 'Eder Galarza', 'email' => 'egalarza@grupohunan.com', 'hash' => '$2y$12$ifQT21ZeDmWkXtcZWsS7SuN2Tf1PZLOUJJi2/efPQkc2LG1msjqAK', 'verified' => '2026-06-22 20:24:15', 'created' => '2026-06-22 20:24:15', 'role' => 'Colaborador'],
            ['name' => 'Jhonatan Trujano', 'email' => 'jtrujano@grupohuanan.com', 'hash' => '$2y$12$F7fz1/XSaTDYNdEnmGOgx.G0OBg2eAtrPCSbcyOYsOdDlVUlJf31.', 'verified' => '2026-06-22 20:26:56', 'created' => '2026-06-22 20:26:56', 'role' => 'super_admin'],
        ];

        foreach ($usuarios as $datos) {
            $user = User::firstOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['name'],
                    'password' => $datos['hash'],
                    'email_verified_at' => $datos['verified'],
                ]
            );

            $user->created_at = $datos['created'];
            $user->saveQuietly();

            $user->syncRoles([$datos['role']]);
        }
    }
}
