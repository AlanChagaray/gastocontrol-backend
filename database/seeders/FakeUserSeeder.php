<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expenses;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FakeUserSeeder extends Seeder
{
    /**
     * Crea un usuario de prueba con credenciales fijas, sus categorías
     * y gastos (algunos en el mes actual) para probar el flujo completo.
     *
     * Credenciales: test@gastocontrol.com / Password123!
     *
     * Idempotente: se puede correr varias veces sin duplicar.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@gastocontrol.com'],
            [
                'name' => 'Usuario de Prueba',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]
        );

        $definiciones = [
            ['name' => 'Comida',        'icon' => 'food',          'color' => '#F59E0B', 'sort_order' => 1],
            ['name' => 'Transporte',    'icon' => 'transport',     'color' => '#3B82F6', 'sort_order' => 2],
            ['name' => 'Entretenimiento','icon' => 'entertainment', 'color' => '#8B5CF6', 'sort_order' => 3],
            ['name' => 'Compras',       'icon' => 'shopping',      'color' => '#EC4899', 'sort_order' => 4],
            ['name' => 'Salud',         'icon' => 'health',        'color' => '#10B981', 'sort_order' => 5],
        ];

        $categorias = collect($definiciones)->map(function ($def) use ($user) {
            return Category::updateOrCreate(
                ['user_id' => $user->id, 'name' => $def['name']],
                $def + ['user_id' => $user->id]
            );
        });

        // Limpiamos gastos previos del usuario para mantener el seed determinista
        Expenses::where('user_id', $user->id)->delete();

        // Construimos gastos directamente (evitamos la factory, que crea
        // usuarios/categorías nuevos al evaluar su definición).
        $comercios = ['Supermercado Día', 'Estación YPF', 'Cine Hoyts', 'Farmacity', 'MercadoLibre', 'Café Martínez'];

        $crearGasto = function ($fecha) use ($user, $categorias, $comercios) {
            Expenses::create([
                'user_id' => $user->id,
                'category_id' => $categorias->random()->id,
                'amount' => rand(500, 50000) / 10, // 50.0 .. 5000.0
                'merchant' => $comercios[array_rand($comercios)],
                'expense_date' => $fecha,
                'notes' => 'Gasto de prueba',
            ]);
        };

        // Gastos del mes actual (para probar /expenses/byMonth)
        $esteMes = now()->startOfMonth();
        for ($i = 0; $i < 8; $i++) {
            $crearGasto($esteMes->copy()->addDays(rand(0, 27))->format('Y-m-d'));
        }

        // Gastos del mes anterior (para verificar el filtro por mes)
        $mesAnterior = now()->subMonth()->startOfMonth();
        for ($i = 0; $i < 5; $i++) {
            $crearGasto($mesAnterior->copy()->addDays(rand(0, 27))->format('Y-m-d'));
        }

        $this->command->info("Usuario de prueba: test@gastocontrol.com / Password123!");
        $this->command->info("Categorías: {$categorias->count()} · Gastos: " . Expenses::where('user_id', $user->id)->count());
    }
}
