<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\InventoryInternalUseController;

class ValidateGrnTmp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:grn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate GRN';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $command     =
            InventoryInternalUseController::validate_when_not_raw_material();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
