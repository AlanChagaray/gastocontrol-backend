<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'color',
        'sort_order',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expenses::class, 'category_id');
    }

    /**
     * Categorías por defecto que recibe todo usuario nuevo.
     * Los slugs de `icon` deben coincidir con LUCIDE_CATEGORY_ICONS del frontend.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultCategories(): array
    {
        return [
            ['name' => 'Supermercado', 'icon' => 'shoppingCart', 'color' => '#22c55e', 'sort_order' => 1],
            ['name' => 'Alquiler',     'icon' => 'home',         'color' => '#8b5cf6', 'sort_order' => 2],
            ['name' => 'Luz',          'icon' => 'lightbulb',    'color' => '#f59e0b', 'sort_order' => 3],
            ['name' => 'Gas',          'icon' => 'flame',        'color' => '#f97316', 'sort_order' => 4],
            ['name' => 'Agua',         'icon' => 'droplets',     'color' => '#3b82f6', 'sort_order' => 5],
            ['name' => 'Seguros',      'icon' => 'shieldCheck',  'color' => '#3b82f6', 'sort_order' => 6],
            ['name' => 'Deportes',     'icon' => 'dumbbell',     'color' => '#ec4899', 'sort_order' => 7],
            ['name' => 'Mascotas',     'icon' => 'pawPrint',     'color' => '#f59e0b', 'sort_order' => 8],
        ];
    }

    /**
     * Crea las categorías por defecto para un usuario recién creado.
     */
    public static function createDefaultsForUser(int $userId): void
    {
        foreach (self::defaultCategories() as $category) {
            self::create($category + ['user_id' => $userId]);
        }
    }
}
