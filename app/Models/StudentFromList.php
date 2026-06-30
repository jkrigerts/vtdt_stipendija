<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFromList extends Model
{
    protected $fillable = ["surname", "first_name","personal_id","group_name"];
}
