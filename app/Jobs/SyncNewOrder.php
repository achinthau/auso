<?php

namespace App\Jobs;

use App\Mail\OrderSynFailEmail;
use App\Mail\OrderSyncNewPosEmail;
use App\Mail\TestMail;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SyncNewOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Ticket $ticket;
   
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $this->ticket->createPosOrder();

        $email = config('app.debug') ? "macorera@gmail.com" : "callcenter.kottugrand@gmail.com";
      

        try {
          
            // Assuming $this->ticket['items'] contains the items to be mapped
            $itemsMapped = $this->ticket['items']->map(function ($item, $index) {
                 // Calculate discount amount
                 $discountAmount = ($item->unit_price * $item->qty ) * ($item->item['disc_per'] / 100);

                 // Calculate net amount (lineTotal - discount)
                 $netAmount =  ($item->unit_price * $item->qty ) - $discountAmount;
                return [
                    "LINE_NO" => (string)($index + 1), // Starting index from 1
                    "TRAN_TYPE" => "S",
                    "MENU_CODE" => $item->item['item_ref'], // Make sure to adjust this based on actual data structure
                    "UNIT_CODE" => "1",
                    "TRAN_DESC" => $item->item['descr'], // Adjusted for object access
                    "TRAN_QTY" => $item->qty,
                    "UNIT_PRICE" => $item->unit_price,
                    "TRAN_AMT" => $item->line_total,
                    "DISC_AMT" =>  $discountAmount,
                    "TAX_AMOUNT" => 0,
                    "NET_AMT" => $netAmount,
                    "SIDE_ITEM" => [],
                    "MODIFIERS" => [],
                ];
            }); // Convert the result back to an array if needed
          

            $totalNetAmount = $itemsMapped->reduce(function ($carry, $item) {
                return $carry + $item['NET_AMT'];
            }, 0); // Initialize carry with 0

            $orderDetails = [
                "COMMAND_TYPE" => "NEW",
                "LOCATION_ID" => $this->ticket['outlet']['contact_no'],
                "AUTH_KEY" => "TXlDb206UmVzdFBvczEyMw==", // Example, use actual auth key
                "HEADER" => [
                    "ORDER_SOURCE" => "CC",
                    "ORDER_REF" => $this->ticket['bill_no'] ?? "TESTORDER", // Example, adjust as needed
                    "BILL_DATE" => now()->format('Y-m-d'),
                    "BILL_TIME" => now()->format('H:i'),
                    "BILL_AMT" => $this->ticket['order_total'],
                    "NO_OF_PAX" => 2,
                    "SERVICE_CHARGE_AMT" => 0,
                    "DISCOUNT_AMT" => 0,
                    "DELIVERY_CHARGE" => 0,
                    "TAX_AMOUNT" => 0,
                    "NET_AMT" => $totalNetAmount,
                    "REMARKS" => $this->ticket['description']
                ],
                "CUSTOMER" => [
                    "CUST_INFOENABLE" => "True",
                    "CUST_NUM" => $this->ticket['lead']['contact_number'] ?? "123456789", // Example, adjust as needed
                    "CUST_NAME" => $this->ticket['lead']['full_name'] ?? "Test", // Example, adjust as needed
                    "CUST_INFO1" => "",
                    "CUST_INFO2" => "",
                    "CUST_INFO3" => "",
                    "CUST_INSTRUCTIONS" => ""
                ],
                "ITEMS" => $itemsMapped
                
            ];
        
            // Encode the order details as JSON
            $jsonOrderDetails = json_encode($orderDetails);
                    
            Log::info('Order Details :'.$jsonOrderDetails);

            $bill_no = $this->ticket['bill_no'];
            $dash_position = strpos($bill_no, "-");
            $outlet_id = substr($bill_no, 0, $dash_position);
            if (strlen($outlet_id) < 10) {
                $outlet_id = str_pad($outlet_id, 3, "0", STR_PAD_LEFT); // Two leading zeros
            } else {
                $outlet_id = str_pad($outlet_id, 2, "0", STR_PAD_LEFT); // One leading zero
            }
            Log::info('Outlet ID :'.$outlet_id);
            // Constructing the URL with query parameters
            $queryParams = http_build_query([
                'ReceiverId' =>  sprintf('1-%s', $outlet_id),
                'OrderRef' => $this->ticket['bill_no'],
                'Function' => 'neworder',
                'SenderId' => 'S2',
            ]);
            // 'ReceiverId' => '1-'+$this->ticket['outlet']['id'],

            Log::info('Receiver ID :'. sprintf('1-%s', $outlet_id));
    
            // The base API URL from your configuration
            $baseUrl = config('auso.mycom_api_url') . "/orders";
    
            // Complete URL with query parameters
            $urlWithParams = "{$baseUrl}?{$queryParams}";
    
            // Making the HTTP POST request
            $response = Http::post($urlWithParams, $jsonOrderDetails);

            if ($response->successful()) {
                Log::info('Request successful. Response code: ' . $response->status());
            } elseif ($response->failed()) { 
                // Choose appropriate level (warning, error, critical) based on severity
                Log::error('Request failed. Response code: ' . $response->status());
                Log::debug('Response body:', [$response->body()]); // For debugging
            }

            // Decode the JSON response into an object
            $responseObject = json_decode($response);

            // Access the TranId property
            $tranId = $responseObject->TranId;

            // Log::debug('Response : ' .$responseObject); // Outputs: 1188
            // Log::info($response);
            if ($response->successful()) {
                $this->ticket->synced_at = Carbon::now();
                $this->ticket->is_synced = 1;
                $this->ticket->ticket_status_id = 2;

                $this->ticket->logActivity("Start Processing");


                Mail::to($email)->send(new OrderSyncNewPosEmail($this->ticket, $this->ticket->outlet->title . 'Order Placed Ref: ' . $this->ticket->bill_no, 1));
            } else {
                $this->ticket->is_synced = 0;
                $this->ticket->bill_no = "Sync Failed : myCOM POS Server Down";
                Mail::to($email)->send(new OrderSynFailEmail($this->ticket, $this->ticket->outlet->title . ' mycom POS Server Down Ref: ' . $this->ticket->order_ref));
                $this->ticket->logActivity("Sync Failed : myCOM POS Server Down");
            }
            $this->ticket->save();
        } catch (\Throwable $th) {
            Mail::to($email)->send(new OrderSynFailEmail($this->ticket, 'POS Middleware Connector Down Ref: ' . $this->ticket->order_ref));
            
            $this->ticket->is_synced = 0;
            $this->ticket->bill_no = "Sync Failed : POS Middleware Connector Down";
            $this->ticket->save();
            $this->ticket->logActivity("Sync Failed : POS Middleware Connector Down");
            
            Log::error($th);
        }
    }
}
