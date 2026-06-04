<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea el primer usuario administrador del sistema.
 *
 * Ejecutar con:
 *   php artisan db:seed --class=AdminUserSeeder
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@inventario.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('Admin1234!'),
                'rol'      => Rol::Admin,
            ]
        );

        $this->command->info('✅ Usuario admin creado: admin@inventario.com / Admin1234!');
        $this->command->warn('⚠️  Cambia la contraseña después del primer inicio de sesión.');
    }
}
