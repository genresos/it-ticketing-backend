<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectTaskCICO extends Model
{

    protected $table = '0_project_task_cico';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $softDelete = false;
}
