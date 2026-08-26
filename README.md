<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Filament-5.6-FDAE4B?style=for-the-badge&logo=php&logoColor=white" alt="Filament 5.6">
  <img src="https://img.shields.io/badge/PHP-%5E8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8">
</p>

<h1 align="center">Inventario GH</h1>

<p align="center">
  Sistema interno de control de inventario de equipo de cómputo, celulares y dispositivos<br>
  entregados al personal, construido sobre <a href="https://laravel.com">Laravel</a> y <a href="https://filamentphp.com">Filament</a>.
</p>

---

## Descripción

Inventario GH centraliza el ciclo de vida de los activos tecnológicos de la empresa: quién tiene qué equipo, en qué sucursal, desde cuándo, y con qué historial de movimientos (altas, bajas, reasignaciones, mantenimientos, préstamos). Cubre tres tipos de registro:

- 💻 **Equipos de Cómputo** — laptops, desktops, all-in-one, workstations y mini PCs, con su ficha técnica completa (sistema operativo, Office, antivirus, BitLocker, accesos).
- 📱 **Equipos Celulares** — celulares, tablets e iPads, con IMEI, ICCID y CURP del resguardante.
- 📦 **Dispositivos Externos** — entregas de accesorios sueltos (teclados, mouses, monitores, cables, discos duros, etc.).

Cada movimiento queda respaldado con su PDF de responsiva y agrupado en un historial por usuario, para saber en todo momento qué equipo ha tenido cada persona a lo largo del tiempo.

## Características principales

- **Catálogos administrables**: Razones Sociales, Sucursales (ligadas a su razón social), Áreas y Marcas — todo editable desde el panel, sin tocar la base de datos.
- **Control de duplicados inteligente**: un número de serie o IMEI puede reutilizarse cuando el equipo se recicla a otra persona, pero el sistema impide tener dos registros *activos* con el mismo número al mismo tiempo, y ofrece dar de baja el anterior con un clic.
- **Roles y permisos granulares** con [Filament Shield](https://github.com/bezhansalleh/filament-shield) sobre [spatie/laravel-permission](https://github.com/spatie/laravel-permission): cada recurso, acción y widget tiene su propio permiso.
- **Bitácora de actividad**: quién inició sesión, quién dio de alta/editó/eliminó qué registro y cuándo, visible en el Dashboard — nunca se registran contraseñas ni claves.
- **Dashboard con indicadores**: equipos activos, altas/bajas del mes, distribución por tipo de equipo y tendencia de los últimos 6 meses.
- **Responsivas en PDF** adjuntas a cada movimiento, con descarga directa desde el detalle del registro.

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Panel de administración | Filament 5.6 |
| Roles y permisos | Filament Shield + Spatie Laravel Permission |
| Bitácora de actividad | Spatie Laravel Activitylog |
| Base de datos | MySQL 8 |
| Frontend build | Vite + Tailwind CSS 4 |

## Requisitos

- PHP >= 8.2 con las extensiones habituales de Laravel
- Composer 2
- Node.js 18+ y npm
- MySQL 8 (o compatible)

## Instalación

```bash
git clone <url-del-repositorio> inventario-gh
cd inventario-gh

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configura la conexión a base de datos en `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) y luego:

```bash
php artisan migrate --seed
npm run build

php artisan serve
```

El `--seed` deja listos: los permisos y roles del sistema, y un puñado de usuarios de prueba.

### Usuarios de prueba

Todos con la contraseña `password`:

| Usuario | Correo | Rol |
|---|---|---|
| Axel Blanco | `axel@inventario.test` | super_admin |
| Usuario Prueba 1–3 | `usuario1@inventario.test` … `usuario3@inventario.test` | super_admin |

> ⚠️ Cambia estas contraseñas antes de usar el sistema en producción.

## Estructura del panel

```
Inventario        → Equipos de Cómputo · Equipos Móviles · Dispositivos Externos
Catálogos         → Marcas · Razones Sociales · Sucursales · Áreas
Administración    → Usuarios · Roles (Filament Shield)
```

## Licencia

Proyecto de uso interno. Construido sobre el framework [Laravel](https://laravel.com), software libre licenciado bajo la [licencia MIT](https://opensource.org/licenses/MIT).
