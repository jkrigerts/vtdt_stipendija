<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['session_id', 'surname', 'first_name', 'personal_id', 'group_name'];

    public function session()
    {
        return $this->belongsTo(CalculationSession::class, 'session_id');
    }

    public function grades()
    {
        return $this->hasMany(GradeRecord::class);
    }
}
