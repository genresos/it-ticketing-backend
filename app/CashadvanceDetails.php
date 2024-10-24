<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashadvanceDetails extends Model
{
    protected $table = '0_cashadvance_details';

    public $fillable = [
                        'trans_no',
                        'project_no',
                        'project_budget_id',
                        'approval',
                        'approval_date',
                        'amount',
                        'act_amount',
                        'approval_amount',
                        'release_amount',
                        'release_date',
                        'plan_release_date',
                        'remark',
                        'release_cashier',
                        'tolcard_no',
    ];
}
