<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;

class SyncInventoryController extends Controller
{
    public function __construct() {

    }

    public function sync_inventory(Request $request) {

        $store_id = env('SOFIAMAN_STORE_ID',1); 
        $store = Store::find($store_id);
        $store->sync_inventory();
    }

   

}
