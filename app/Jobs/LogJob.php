<?php

namespace App\Jobs;

use App\Models\AccessLog;
use App\Models\LogEvent;
use App\Libraries\Helpers;
use App\Models\User;

class LogJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $code;
    protected $data;
    protected $user;
    protected $ip;
    public function __construct($code, User $user, $ip, array $data)
    {
        $this->code = $code;
        $this->data = $data;
        $this->user = $user;
        $this->ip = $ip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $logEvt = LogEvent::getLogEventByCode($this->code);
            $data = [];
            if ($logEvt) {
                $data['log_event_id'] = $logEvt->log_event_id;
                $time = time();
                $mapKey = explode('#|' . $time . '|#', '<' . implode('>#|' . $time . '|#<', array_keys($this->data)) . '>');
                $data['evt_obj'] = str_replace($mapKey, array_values($this->data), $logEvt->obj_info);
                if (!empty($this->data['items'])) {
                    $data['evt_obj'] = $this->data['items'] . ', ' . $data['evt_obj'];
                }
                $user = $this->user;
                $data['user_id'] = $user->user_id;
                $data['access_ip'] = $this->ip;
                $mailRole = $this->user->getMainRole($user->user_id);
                $data['role_id'] = $mailRole ? $mailRole->role_id : null;
                //$data['access_time'] = date_time
                AccessLog::create($data);
            }
        }
        catch (\Exception $e)
        {
            return;
        }
    }
}
