<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Store;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores_data = [
            [ 
                'url' => 'sofiaman-store.myshopify.com',
                'token' => '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'
            ],
            [ 
                'url' => 'sofiaman-global.myshopify.com',
                'token' => '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'
            ]
        ]; 

        foreach( $stores_data as $data){
            $store = Store::updateOrCreate(
                ['shop_url' => $data['url']],
                ['shopify_token' => $data['token']]
            );
        }
    }
}
