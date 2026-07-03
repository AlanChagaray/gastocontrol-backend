<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    /** @use HasFactory<\Database\Factories\IncomeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'income_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
