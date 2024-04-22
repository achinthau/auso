<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\PosOrder; 
use App\Models\Ticket;
use Exception;

class PosOrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'Function' => 'nullable|string',
                'TranId' => 'nullable|string',
                'OrderRef' => 'required|string',
                'BillRef' => 'nullable|string',
                'ReceiverId' => 'required|string',
            ]);

            if ($request->OrderStatus === 'received') {
                $ticket = Ticket::where('bill_no', $request->ReceiverId)->first();

                if ($ticket) {
                    $ticket->update(['order_ref' => $request->OrderRef]);
                }
            }

            // Create a new PosOrder instance and save validated data
            $order = new PosOrder([
                'function' => $validatedData['Function'] ?? null,
                'tran_id' => $validatedData['TranId'] ?? null,
                'order_ref' => $validatedData['OrderRef'],
                'bill_ref' => $validatedData['BillRef'] ?? null,
                'sender_id' => $request['SenderId'],
                'receiver_id' => $request['ReceiverId'],
                'order_status' => $request['OrderStatus'],
                'success' => $request['Success'],
                'message' => $request['Message'] ?? null,
                'tran_date' => $request['TranDate'] ?? null,
                'tran_time' => $request['TranTime'] ?? null,
                'client_id' => $request['ClientId'] ?? null,
                'biz_type' => $request['BizType'] ?? null,
                'loc_id' => $request['LocId'] ?? null,
                'tran_type' => $request['TranType'] ?? null,
                'data' => json_encode($request['Data'] ?? []),
	            'res_type' =>$request['ResType'] ?? null,
	            'retry_count' => $request['RetryCount'] ?? null,
            ]);

            if ($order->save()) { // Save the order
                // Return a success response
                return response()->json(['message' => 'Order status updated successfully!'], 200);
                } else {
                // Return an error response if saving failed
                return response()->json(['error' => 'Failed to update the order status.'], 500);
                }

            // Return a response
            return response()->json(['message' => 'Order status updated successfully!'], 200);
        } catch (Exception $e) {
            // Log the error
            Log::error('Error updating order status: ' . $e->getMessage());

            // Return an error response
            return response()->json(['error' => 'An error occurred while updating the order status.'], 500);
        }
    }
}
