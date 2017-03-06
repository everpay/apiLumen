<?php

namespace App\Jobs;

use App\Models\Position;
use App\Models\Device;
use App\Models\RobotProcessing;
use App\Libraries\MyHelper;
use App\Libraries\Clients;
use Illuminate\Support\Facades\Log;

class ExampleJob extends Job
{


    /**
     * Create a new job instance.
     *
     * @param $mac
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @param
     */
    public function handle()
    {
       throw  new \Exception('Demo Fail Job');
    }

    public function failed()
    {
       Log::error('Example Error');
    }

}
