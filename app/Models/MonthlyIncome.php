<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyIncome extends Model
{
    /** @use HasFactory<\Database\Factories\MonthlyIncomeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
