<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetVehicles extends Model
{
    protected $table = '0_am_vehicles';

    public $fillable = [
                        'order_no',
                        'type',
                        'reference', 
                        'ord_date',
                        'to_date',
                        'vehicle_type_id',
                        'emp_no',
                        'created_by',
                        'created_date',
                        'updated_by',
                        'bank_account_no', 
                        'payment_type_id', 
                        'cashadvance_ref',
                        'updated_date',
    ];
}
