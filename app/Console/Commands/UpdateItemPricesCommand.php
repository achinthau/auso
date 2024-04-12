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
        $response = Http::withOptions([
        'verify' => false, // Disable SSL certificate verification
        ])->withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://web2.mycomsys.com:8803/api/v1/opm/Download_ItemMaster', [
            'DATA' => json_encode([
                'auth_key' => 'TXlDb206UmVzdFBvczEyMw==',
                'client_id' => 80939,
                'loc_code' => '001'
            ])
        ]);

  
        if ($response->successful()) {
       
   
            $responseBody = $response->body(); // Get the raw JSON string
            $jsonResponse = json_decode($responseBody, true); // Decode to an associative array

            \Log::info("Full API response", [          
                'body' => $jsonResponse
            ]);
    
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decoding error: ' . json_last_error_msg());
            }
            
    
            \Log::info("Decoded API response", [          
                'body' => $jsonResponse,
            ]);

            if (isset($jsonResponse['DATA'])) {
                $data = $jsonResponse['DATA'];
                foreach ($data as $itemData) {
                    // Process each item, for example, update or create records in the database
                }
            } else {
                \Log::error('Unexpected JSON structure or DATA key missing', ['response' => $jsonResponse]);
            }

            if (isset($jsonResponse['DATA'])) {
                $data = $jsonResponse['DATA'];

                foreach ($data as $itemData) {
                    // Use firstOrCreate to find an existing item by barcode or create a new one
                        $item = ItemMaster::firstOrCreate(
                            ['item_ref' => $itemData['item_code']], // Conditions to find the existing item
                            [
                                // Default values for new item creation
                                'barcode' => $itemData['item_code'],
                                'descr' => $itemData['item_name'],
                                'retail1' => $itemData['item_price'],
                                'item_ref' => $itemData['item_code'],
                                // Add more fields as necessary
                            ]
                        );

                        // If the item was found, it might still need its price updated
                        if (!$item->wasRecentlyCreated) {
                            $item->update(['retail1' => $itemData['item_price']]);
                        }
                }
    
                $this->info('Item prices updated successfully.');
            } else {
                // Handle unexpected response structure or log for debugging
                \Log::error("Unexpected response structure", ['response' => $jsonResponse]);
                
            }
            
          
        } else {
            $this->error('Failed to fetch item data.');
        }
    }
}
