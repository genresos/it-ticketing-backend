<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetVehicleDetails extends Model
{
    protected $table = '0_am_vehicle_details';

    public $fillable = [
                        'order_no',
                        'vehicle_no', 
                        'vehicle_name',
                        'project_no',
                        'bbm_doc_no',
                        'bbm_amount',
                        'remark',
                        'site_no',
                        'milestone_no',
                        'tolcard_no',
    ];
}
