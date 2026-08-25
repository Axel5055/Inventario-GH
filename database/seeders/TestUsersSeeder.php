<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea usuarios de prueba para desarrollo local.
 *
 * Ejecutar con:
 *   php artisan db:seed --class=TestUsersSeeder
 */
class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Axel Blanco', 'email' => 'axel@inventario.test'],
            ['name' => 'Usuario Prueba 1', 'email' => 'usuario1@inventario.test'],
            ['name' => 'Usuario Prueba 2', 'email' => 'usuario2@inventario.test'],
            ['name' => 'Usuario Prueba 3', 'email' => 'usuario3@inventario.test'],
        ];

        foreach ($usuarios as $usuario) {
            $user = User::firstOrCreate(
                ['email' => $usuario['email']],
                [
                    'name'              => $usuario['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole('super_admin');
        }

        $this->command->info('✅ Usuarios de prueba creados (contraseña para todos: password).');
    }
}
