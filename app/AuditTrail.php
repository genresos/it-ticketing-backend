<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table = '0_audit_trail';
    public $fillable = [
                            'trans_no',
                            'type', 
                            'user',
                            'stamp',
                            'fiscal_year',
                            'gl_seq',
                            'gl_date',
                        ];
}

