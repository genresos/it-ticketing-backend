<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    protected $table = '0_hrm_employees';

    public $fillable = [
                        'emp_id',
                        'name', 
                        'division_id',
                        'L=evel',
                        'inactive',
    ];

}
