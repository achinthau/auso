<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ItemMaster;
use Illuminate\Support\Facades\Http;

class UpdateItemPricesCommandTest extends TestCase
{
    public function testUpdateItemPricesSuccess()
    {
        // Mock a successful HTTP response
        Http::fake([
            'https://web2.mycomsys.com:8803/api/v1/opm/Download_ItemMaster' => Http::response([
                'DATA' => [
                    ['item_code' => '123', 'item_price' => 10.99],
                    ['item_code' => '456', 'item_price' => 20.99],
                ]
            ], 200),
        ]);

        // Run the command
        $this->artisan('update:item-prices')->expectsOutput('Item prices updated successfully.');

        // Assert that the item prices are updated in the database
        $this->assertEquals(10.99, Item::where('barcode', '123')->first()->retail1);
        $this->assertEquals(20.99, Item::where('barcode', '456')->first()->retail1);
    }

    public function testUpdateItemPricesFailure()
    {
        // Mock a failed HTTP response
        Http::fake([
            'https://web2.mycomsys.com:8803/api/v1/opm/Download_ItemMaster' => Http::response([], 500),
        ]);

        // Run the command
        $this->artisan('update:item-prices')->expectsOutput('Failed to fetch item data.');

        // Assert that the item prices are not updated in the database
        $this->assertNull(Item::where('barcode', '123')->first());
        $this->assertNull(Item::where('barcode', '456')->first());
    }
}
