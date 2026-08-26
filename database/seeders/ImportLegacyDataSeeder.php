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
 * Importa los datos reales capturados en producción (dump myclethc_gh.sql,
 * exportado el 26/08/2026) al esquema actual del proyecto.
 *
 * Notas de la migración de datos:
 * - En el dump de origen `sucursales` todavía tenía razon_social_id (antes
 *   de la corrección que las volvió globales), por lo que "Constituyentes
 *   1080" aparecía repetida 3 veces (una por cada razón social). Aquí se
 *   fusiona por nombre en un solo registro global, igual que hace la
 *   migración 2026_08_26_120000_make_sucursales_globales_otra_vez.
 * - Los PDFs de responsiva no vienen en el dump (solo el nombre/ruta del
 *   archivo), así que se conserva el dato como referencia pero el archivo
 *   no estará disponible para descargar hasta que se suba manualmente.
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
        $sucursales = $this->importSucursales();
        $marcas = $this->importMarcas();

        $this->importEquiposComputo($razonesSociales, $sucursales, $areas, $marcas);
        $this->importEquiposCelulares($razonesSociales, $sucursales, $areas, $marcas);
        $this->importUsuarios();

        $this->command->info('✅ Datos reales importados desde producción (myclethc_gh.sql).');
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
            7 => 'Almacén',
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
            3 => ['nombre' => 'TESSA STUDIO', 'rfc' => 'JSR150113FR4'],
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
     * Las sucursales son globales (no pertenecen a una razón social), así
     * que se deduplican por nombre. En el dump original "Constituyentes
     * 1080" existía 3 veces (ids 1, 4 y 5) por estar ligada a cada razón
     * social; aquí las 3 apuntan al mismo registro.
     *
     * @return array<int, Sucursal>
     */
    private function importSucursales(): array
    {
        $datos = [
            1 => ['nombre' => 'Constituyentes 1080', 'ciudad' => 'CDMX'],
            2 => ['nombre' => 'Costa Guadiana', 'ciudad' => 'CDMX'],
            3 => ['nombre' => 'Hacienda Cocina y Cantina', 'ciudad' => 'Los cabos'],
            4 => ['nombre' => 'Constituyentes 1080', 'ciudad' => 'CDMX'], // duplicado -> mismo global
            5 => ['nombre' => 'Constituyentes 1080', 'ciudad' => 'CDMX'], // duplicado -> mismo global
        ];

        $sucursales = [];
        foreach ($datos as $oldId => $data) {
            $sucursales[$oldId] = Sucursal::firstOrCreate(
                ['nombre' => $data['nombre']],
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
            // [tipoMovimiento, fechaEntrega, fechaBaja, rsOld, nombreUsuario, correo, sucOld, areaOld, ext, tipoEquipo, marcaOld, modelo, numeroSerie, procesador, ram, almacenamiento, sistemaOperativo, observaciones, usuarioEquipo, pinPassword, responsivaPdf, windowsVersion, officeVersion, antivirusNombre, antivirusFechaInstalacion, usuarioReferencia, createdAt, updatedAt]
            ['baja', '2025-02-07 10:47:00', '2026-06-23', 1, 'Laura Ramirez', 'lramirez@grupohunan.com', 1, 1, '1281', 'laptop', 1, 'IdeaPad 1 15ALC7', 'PF547YRF', 'Ryzen 7 5700u', '16 GB', 'SSD 512 GB', 'windows', 'Se entrega equipo nuevo con un adaptador multifuncional con 3 puertos USB y un puerto Ethernet.', 'Laura', null, '01KVTPNKFJRJY322FD77VF4H11.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'lramirez@grupohunan.com', '2026-06-23 22:57:58', '2026-06-25 02:46:46'],
            ['alta', '2025-02-05 11:01:00', null, 1, 'Armando Ulloa', 'acontreras@grupohunan.com', 1, 2, null, 'laptop', 2, 'Inspiron 3480', 'D55QTW2', 'Core i5 8365U', '8 GB', 'SSD 256 GB', 'windows', 'Se entrega equipo en stock.', 'Armando', null, '01KVTT86Q1N0RATPDMD4SJQZSY.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'acontreras@grupohunan.com', '2026-06-24 00:00:33', '2026-06-25 02:47:43'],
            ['alta', '2025-03-11 12:01:00', null, 1, 'Francisco Daniel Cruz Uriel', 'na@na.com', 1, 3, null, 'laptop', 1, 'A14', 'R58W81H6SNL', null, '4 GB', 'SSD 128 GB', 'windows', 'Se entrega equipo nuevo', 'Francisco', null, '01KVTYPHQ05TFJF9AG80PV8WAQ.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'na@na.com', '2026-06-24 01:18:17', '2026-06-24 01:18:17'],
            ['baja', '2025-03-18 10:40:00', '2026-06-24', 1, 'Laura Ramirez', 'lramirez@grupohunan.com', 1, 1, '1281', 'laptop', 1, 'IdeaPad S145', 'PF1S1MDW', 'Core i3-8145U', '8 GB', 'SSD 256 GB', 'windows', 'Se entrega equipo en stock sin tecla ESC', 'Laura', null, '01KVX99NJY7C39KRG0YRQAQKCY.pdf', 'Windows 10 Pro', 'Office 2021', 'Windows Defender', null, 'lramirez@grupohunan.com', '2026-06-24 23:01:58', '2026-06-24 23:02:17'],
            ['alta', '2025-03-25 13:48:00', null, 1, 'Federico Gerdes', 'fgerdes@grupohunan.com', 1, 4, null, 'laptop', 2, 'Inspiron 15 3520', '22N1854', 'Core i7 1255U', '16 GB', 'SSD 512 GB', 'windows', 'Se entrega equipo nuevo', 'Federico', null, '01KVXKQ0K0DQXQBGSWEM3N3BNJ.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'fgerdes@grupohunan.com', '2026-06-25 02:02:55', '2026-06-25 02:04:01'],
            ['alta', '2025-04-22 15:01:00', null, 1, 'Yury Edson Arce Peralta', 'yarce@grupohunan.com', 3, 6, null, 'laptop', 2, 'Inspiron 15 3535', 'H7JPV64', 'Ryzen', '16 GB', 'SSD 1 TB', 'windows', 'Se entrega equipo nuevo', 'Edson', null, '01KVXQKY6V6EVNF8G7SQACPNQK.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'yarce@grupohunan.com', '2026-06-25 03:12:15', '2026-06-25 03:12:15'],
            ['alta', '2025-04-22 10:59:00', null, 1, 'Edwin Mendoza', 'emendoza@grupohunan.com', 3, 6, null, 'laptop', 2, 'Inspiron 3535', '9D7SV64', 'Ryzen', '16 GB', 'SSD 1 TB', 'windows', 'Se entrega equipo nuevo', 'Edwin', null, '01KWA6J0NNDRZZQ4MXTRBXHB4Y.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'emendoza@grupohunan.com', '2026-06-29 23:24:14', '2026-06-29 23:24:14'],
            ['alta', '2026-08-25 11:31:00', null, 2, 'Regina Guerrero Olavarrieta', 'rguerrero@grupohunan.com', 4, 5, '1155', 'all_in_one', 4, 'iMac', 'CVDHWHQWTV', '10 Nucleos', '16 GB', 'SSD 512 GB', 'apple', 'Se entrega equipo nuevo', null, '1234', 'responsivas/computo/01M0ZJFCWMZ9KK84J2A3XJ3VAS.pdf', null, 'Office 2021', null, null, 'rguerrero@grupohunan.com', '2026-08-26 00:09:11', '2026-08-26 17:39:21'],
            ['alta', '2025-04-02 14:04:00', null, 1, 'Carlos Morelos', 'cmorelos@grupohunan.com', 1, 4, null, 'laptop', 2, 'Inspiron 15 3520', 'PENDIENTE-CARLOS-MORELOS', 'Core i7 1255U', '16 GB', 'SSD 500 GB', 'windows', 'Se entrega equipo nuevo. ATENCION: numero de serie pendiente de verificar, en el dump original venia duplicado (22N1854) con el equipo de Federico Gerdes.', 'Federico', null, '01KVXMHJ1DZWEPCCHT76HWEC0A.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'cmorelos@grupohunan.com', '2026-06-25 02:17:34', '2026-06-25 02:18:31'],
            ['alta', '2025-04-21 14:22:00', null, 1, 'Daniela Chavez Valle', 'dchavez@grupohunan.com', 1, 5, null, 'laptop', 2, 'Inspiron 15 3520', 'PENDIENTE-DANIELA-CHAVEZ', 'Core i7 1255U', '16 GB', 'SSD 512 GB', 'windows', 'Se entrega equipo nuevo. ATENCION: numero de serie pendiente de verificar, en el dump original venia duplicado (22N1854) con el equipo de Federico Gerdes.', 'Daniela', null, '01KVXN03E9WWRH11QXB5FQZXCY.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', null, 'dchavez@grupohunan.com', '2026-06-25 02:26:28', '2026-06-25 02:26:28'],
            ['alta', '2025-04-22 12:15:00', null, 1, 'Rosario', 'na@na.com', 3, 7, null, 'desktop', 1, 'Neo 50qGen4', 'YJ0Q7HX', 'Core i5', '16 GB', 'SSD 512 GB', 'windows', 'Se entrega equipo nuevo', null, null, 'responsivas/computo/01M0ZN0TD12FCXMVN20G36BFAE.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', '2026-08-26', 'na@na.com', '2026-08-26 18:20:38', '2026-08-26 18:23:49'],
            ['alta', '2025-05-23 12:23:00', null, 1, 'Laura Ramirez', 'lramirez@grupohunan.com', 1, 1, '1281', 'laptop', 1, 'IdeaPad 1 15ALC', 'PF5BWHJP', 'Rayzen 7 5700u', '16 GB', 'SSD 512 GB', 'windows', 'Se entrega equipo nuevo', null, null, 'responsivas/computo/01M0ZN8YXNFK2M27B19BKGVV33.pdf', 'Windows 11 Pro', 'Office 2021', 'Windows Defender', '2026-08-26', 'lramirez@grupohunan.com', '2026-08-26 18:28:16', '2026-08-26 18:28:16'],
        ];

        foreach ($filas as $fila) {
            [
                $tipoMovimiento, $fechaEntrega, $fechaBaja, $razonSocialOld, $nombreUsuario, $correo,
                $sucursalOld, $areaOld, $ext, $tipoEquipo, $marcaOld, $modelo, $numeroSerie, $procesador,
                $ram, $almacenamiento, $sistemaOperativo, $observaciones, $usuarioEquipo, $pinPassword,
                $responsivaPdf, $windowsVersion, $officeVersion, $antivirusNombre, $antivirusFechaInstalacion,
                $usuarioReferencia, $createdAt, $updatedAt,
            ] = $fila;

            if (EquipoComputo::where('numero_serie', $numeroSerie)->exists()) {
                continue;
            }

            $equipo = EquipoComputo::create([
                'tipo_movimiento' => $tipoMovimiento,
                'fecha_entrega' => $fechaEntrega,
                'fecha_baja' => $fechaBaja,
                'razon_social_id' => $razonesSociales[$razonSocialOld]->id,
                'nombre_usuario' => $nombreUsuario,
                'correo_electronico' => $correo,
                'sucursal_id' => $sucursales[$sucursalOld]->id,
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
                'sistema_operativo' => $sistemaOperativo,
                'windows_version' => $windowsVersion,
                'office_version' => $officeVersion,
                'antivirus_nombre' => $antivirusNombre,
                'antivirus_fecha_instalacion' => $antivirusFechaInstalacion,
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
            'sucursal_id' => $sucursales[2]->id,
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

        $equipo->created_at = '2026-06-25 02:39:36';
        $equipo->updated_at = '2026-06-25 02:39:36';
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
            ['name' => 'Axel', 'email' => 'hramirez@grupohunan.com', 'hash' => '$2y$12$H9HqyH5ZNc3LT2mI1fCx..H2Kd2W0B0X5Eq686urPgxZtCt1zDQlC', 'verified' => '2026-06-06 03:10:21', 'created' => '2026-06-06 02:54:31', 'role' => 'super_admin'],
            ['name' => 'Carlos Velazquez', 'email' => 'cvelazquez@grupohunan.com', 'hash' => '$2y$12$Po1Mw6xpnNADxXMFDRdBOez1kA4IN98LbOP54mkMpgNDc1QGbhgbO', 'verified' => '2026-06-23 02:23:13', 'created' => '2026-06-23 02:23:13', 'role' => 'Colaborador'],
            ['name' => 'Eder Galarza', 'email' => 'egalarza@grupohunan.com', 'hash' => '$2y$12$ifQT21ZeDmWkXtcZWsS7SuN2Tf1PZLOUJJi2/efPQkc2LG1msjqAK', 'verified' => '2026-06-23 02:24:15', 'created' => '2026-06-23 02:24:15', 'role' => 'Colaborador'],
            ['name' => 'Jhonatan Trujano', 'email' => 'jtrujano@grupohuanan.com', 'hash' => '$2y$12$F7fz1/XSaTDYNdEnmGOgx.G0OBg2eAtrPCSbcyOYsOdDlVUlJf31.', 'verified' => '2026-06-23 02:26:56', 'created' => '2026-06-23 02:26:56', 'role' => 'super_admin'],
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
