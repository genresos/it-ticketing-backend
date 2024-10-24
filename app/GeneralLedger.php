<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GeneralLedger extends Model
{
    protected $table = '0_gl_trans';

    public $fillable = [
                        'type',
                        'type_no', 
                        'tran_date',
                        'account',
                        'memo_',
    ];
}
