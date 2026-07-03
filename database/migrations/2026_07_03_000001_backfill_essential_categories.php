<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Categorías esenciales a sembrar en usuarios existentes.
     * name/icon/color deben coincidir con Category::defaultCategories().
     */
    private array $essentials = [
        ['name' => 'Luz',  'icon' => 'lightbulb', 'color' => '#f59e0b'],
        ['name' => 'Gas',  'icon' => 'flame',     'color' => '#f97316'],
        ['name' => 'Agua', 'icon' => 'droplets',  'color' => '#3b82f6'],
    ];

    /**
     * Inserta los esenciales faltantes después de la categoría "Alquiler"
     * de cada usuario, corriendo el sort_order de las siguientes. Idempotente.
     */
    public function up(): void
    {
        $userIds = DB::table('categories')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            DB::transaction(function () use ($userId) {
                $existing = DB::table('categories')
                    ->where('user_id', $userId)
                    ->pluck('name')
                    ->map(fn ($n) => mb_strtolower($n))
                    ->all();

                $missing = array_values(array_filter(
                    $this->essentials,
                    fn ($e) => ! in_array(mb_strtolower($e['name']), $existing, true)
                ));

                if (empty($missing)) {
                    return; // ya las tiene todas → nada que hacer
                }

                $n = count($missing);

                $alquiler = DB::table('categories')
                    ->where('user_id', $userId)
                    ->whereRaw('LOWER(name) = ?', ['alquiler'])
                    ->orderBy('sort_order')
                    ->first();

                if ($alquiler) {
                    $pivot = (int) $alquiler->sort_order;
                    // correr +N las categorías posteriores (bulk → sin colisiones)
                    DB::table('categories')
                        ->where('user_id', $userId)
                        ->where('sort_order', '>', $pivot)
                        ->update(['sort_order' => DB::raw("sort_order + {$n}")]);
                } else {
                    // sin "Alquiler" → append al final
                    $pivot = (int) DB::table('categories')->where('user_id', $userId)->max('sort_order');
                }

                $now = now();
                $order = $pivot;
                $rows = [];
                foreach ($missing as $e) {
                    $order++;
                    $rows[] = $e + [
                        'user_id'    => $userId,
                        'sort_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('categories')->insert($rows);
            });
        }
    }

    /**
     * Elimina los esenciales sembrados (match name+icon) que no tengan
     * gastos asociados. Best-effort: no restaura el sort_order desplazado.
     */
    public function down(): void
    {
        foreach ($this->essentials as $e) {
            DB::table('categories')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($e['name'])])
                ->where('icon', $e['icon'])
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('expenses')
                        ->whereColumn('expenses.category_id', 'categories.id');
                })
                ->delete();
        }
    }
};
