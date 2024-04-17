<?php

use App\Events\CallAnswered;
use App\Http\Requests\StoreAnsweredCall;
use App\Models\Agent;
use App\Models\ItemMaster;
use App\Models\Lead;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PosOrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/items', function (Request $request) {
    return ItemMaster::query()
        // ->join('cities','cities.id','hotels.city_id')
        ->selectRaw("id,descr,CONCAT ('Rs. ',ROUND(retail1,2)) as description ")
        ->orderBy('descr')
        ->when(
            $request->search,
            fn (Builder $query) => $query
                ->where('descr', 'like', "%{$request->search}%")
                ->orWhere('barcode', 'like', "%{$request->search}%")
        )
        ->when(
            $request->exists('selected'),
            fn (Builder $query) => $query->whereIn('id', $request->selected),
            fn (Builder $query) => $query->limit(10)
        )
        ->get();
})->name('api.items.index');

Route::get('/items_new', function (Request $request) {
    return ItemMaster::query()
        // ->join('cities','cities.id','hotels.city_id')
        ->selectRaw("id,descr,CONCAT ('Rs. ',ROUND(retail1,2)) as description ")
        ->orderBy('descr')
        ->whereIn('item_status', [1, 2])
        ->when(
            $request->search,
            fn (Builder $query) => $query
                ->where('descr', 'like', "%{$request->search}%")
                ->orWhere('barcode', 'like', "%{$request->search}%")
        )
        ->when(
            $request->exists('selected'),
            fn (Builder $query) => $query->whereIn('id', $request->selected),
            fn (Builder $query) => $query->limit(10)
        )
        ->get();
})->name('api.items.index_new');

Route::post('/call-answered', function (StoreAnsweredCall $request) {
    Log::info($request);
    $lead = Lead::where('contact_number', $request['ani'])->first();
    // $agent = Agent::where('extension', $request['agent'])->first();
    $user = User::where('extension', $request['agent'])->first();
    // $skill = Skill::where('skillname', $request['queuename'])->first();
    Cache::forever('agent-in-call-' . $user->agent_id, 1);
    Cache::forever('call-' . $request['unique_id'], $user->agent_id);

    Cache::add('current-call-count', 0, 99999999);
    Cache::add($request['queuename']."-current-call-count", 0, 99999999);

    Cache::increment('current-call-count');
    Cache::increment($request['queuename']."-current-call-count");


    if (!$lead) {
        if ($user) {
            $lead = new Lead;
            $lead->contact_number = $request['ani'];
            $lead->unique_id = $request['unique_id'];
            $lead->agent_id = $user->agent_id;
            $lead->extension = $request['agent'];
            // $lead->skill_id = $skill->skillid;
            $lead->skill_id = $request['skill_id'];
            $lead->status_id = 1;
            $lead->save();
            event(new CallAnswered($lead->id));
            return $lead;
        }
    } else {
        // $agent = Agent::where('extension', $request['agent'])->first();
        $lead->agent_id = $user->agent_id;
        $lead->extension = $request['agent'];
        $lead->skill_id = $request['skill_id'];
        $lead->save();
        event(new CallAnswered($lead->id));
        return $lead;
    }
});


Route::post('/call-disconnected', function (Request $request) {
    Log::info($request);
    if (Cache::has('call-' . $request['unique_id'])) {
        Cache::forget('agent-in-call-' . Cache::get('call-' . $request['unique_id']));
        Cache::forget('call-' . $request['unique_id']);

        Cache::decrement('current-call-count');
        Cache::decrement($request['queuename']."-current-call-count");
    }
});


Route::post('/update-order-info',  function (Request $request) {
   // $data = $request->json()->all();

    // Update order status in your database
    // $order = Order::findOrFail($data['order_id']);
    // $order->status = $data['status'];
    // $order->save();

    return response()->json(['message' => 'Order status updated successfully']);

});

 Route::post('/order/status_update', [PosOrderController::class, 'store']);
