<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ItemMaster;

class UpdateItemPricesCommand extends Command
{
    protected $signature = 'update:item-prices';
    protected $description = 'Update item prices from API';

    public function handle()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://web2.mycomsys.com:8803/api/v1/opm/Download_ItemMaster', [
            'DATA' => [
                'auth_key' => 'TXlDb206UmVzdFBvczEyMw==',
                'client_id' => 80939,
                'loc_code' => '001'
            ]
        ]);
    
    
        if ($response->successful()) {
            $data = $response->json()['DATA'];

            foreach ($data as $itemData) {
                $item = Item::where('barcode', $itemData['item_code'])->first();

                if ($item) {
                    $item->update(['retail1' => $itemData['item_price']]);
                }
            }

            $this->info('Item prices updated successfully.');
        } else {
            $this->error('Failed to fetch item data.');
        }
    }
}
