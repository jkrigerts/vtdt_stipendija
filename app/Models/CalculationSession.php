<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculationSession extends Model
{
    protected $fillable = ['period_start', 'period_end', 'monthly_budget', 'grade_table'];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'grade_table' => 'array',
        'monthly_budget' => 'decimal:2',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'session_id');
    }
}
