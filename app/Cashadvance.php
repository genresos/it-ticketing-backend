<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cashadvance extends Model
{
    protected $table = '0_cashadvance';
    protected $primaryKey = 'trans_no';
    public $fillable = [
                        'trans_no',
                        'ca_type_id', 
                        'tran_date',
                        'reference',
                        'project_budget_id',
                        'bank_account_no',
                        'emp_no',
                        'emp_id',
                        'payment_type_id',
                        'area_id',
                        'approval',
                        'amount',
                        'approval_amount',
                        'release_amount',
                        'release_date',
                        'project_no',
    ];
}
