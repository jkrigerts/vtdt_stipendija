<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeRecord extends Model
{
    protected $fillable = ['student_id', 'subject_name', 'grade_type', 'grade_value', 'grade_date', 'excluded'];

    protected $casts = [
        'grade_date' => 'date',
        'excluded' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
