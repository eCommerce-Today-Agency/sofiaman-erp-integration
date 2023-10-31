<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;

class SyncInvenory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync store inventory from ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {   
        $store_id = env('SOFIAMAN_STORE_ID',1); 
        $store = Store::find($store_id);
		_log('sync started');
        if( $store ){
            $store->sync_inventory();
        }
        
        _log('sync ended');
    }
	public function failed($e = null){
		_log('sync ended with exception');
	}
}
