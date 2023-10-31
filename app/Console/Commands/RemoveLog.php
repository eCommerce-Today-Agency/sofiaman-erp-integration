<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RemoveLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove debug logs older than a month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {   
    
        $storage_folders = scandir( storage_path('/'));
        
        // check if logs folder exists.
        if( in_array('logs',$storage_folders) == false){
            return;
        }
        $files = scandir( storage_path('/logs'));

        // filter out all the logs files only
        $log_files = array_filter(  $files, function ($file) {     
            $date_string =substr($file, -12 , 8);
            return $this->is_log_file($date_string);
        });
        
        // Check and remove log files
        foreach($log_files as $i => $file ){

            $date_string =substr($file, -12 , 8);

            $to = \Carbon\Carbon::createFromFormat('Ymd', $date_string);
            $from = \Carbon\Carbon::now();
            $diff_in_days = $to->diffInDays($from);

            // see if log file is older then a week.
            if( $diff_in_days > 3 ){
                unlink( storage_path('logs/').$file );
            }
           
        }

       
    }   

    /**
     *  param $s string
     *  return string|false
     */
    function is_log_file($s) {
        if (preg_match('@^(\d\d\d\d)(\d\d)(\d\d)$@', $s, $m) == false) {
            return false;
        }
 
        return $s;
    }
}
