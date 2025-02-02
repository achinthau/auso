<?php

namespace App\Http\Livewire\Orders;

use App\Events\NotifyOrder;
use App\Jobs\SyncOrder;
use App\Jobs\SyncNewOrder;
use App\Models\Item;
use App\Models\ItemMaster;
use App\Models\Outlet;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketItem;
use Carbon\Carbon;
use Hamcrest\Type\IsNumeric;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use WireUi\Traits\Actions;
use Illuminate\Support\Facades\Log;

class Create extends Component
{

    use Actions;

    public $leadId;
    public $ticket;
    public $ticketItems = [];
    public $size = [];
    public $items = [];
    public $categories;
    public $subCategories;
    public $outlets;
    public $tags;
    public $creatingOrder = false;
    public $total = 0.0;
    public $selectedOutletId;
    public $outlet_item_type;
    public $selectedOutlet;
    public $generateBillNo;


    protected $listeners = ['showCreatingOrder' => 'showCreatingOrder'];

    protected $rules  = [
        'ticket.topic' => 'required_if:ticket.ticket_category_id,1,2',
        'ticket.description' => 'required_if:ticket.ticket_category_id,1,2',
        'ticket.outlet_id' => 'required_if:ticket.ticket_category_id,3|exists:outlets,id',
        'ticket.lead_id' => 'nullable',
        'ticket.ticket_category_id' => 'required|exists:ticket_categories,id',
        'ticket.ticket_sub_category_id' => 'required|exists:ticket_sub_categories,id',
        'ticket.tags' => 'required|array|min:1',
        'ticket.crm' => 'required',
        'ticket.due_at' => 'nullable',
        'ticket.order_ref' => 'required_if:ticket.crm,false',
        //'ticketItems.*.item_id' => 'required_if:ticket.crm,true',
        //'ticketItems.*.item_id' => 'required_if:ticket.ticket_category_id,3',
        //'ticketItems.*.size_id' => 'required_if:ticket.crm,true',
        //'ticketItems.*.size_id' => 'required_if:ticket.ticket_category_id,3',
    ];

    protected $validationAttributes = [
        'ticket.ticket_category_id' => 'category',
        'ticket.ticket_sub_category_id' => 'sub category',
        'ticket.outlet_id' => 'outlet',
        'ticketItems.*.item_id' => 'item',
        'ticketItems.*.size_id' => 'size',
    ];


    protected $messages = [
        'ticketItems.*.item_id.required_if' => 'The item field is required.',
        'ticketItems.*.size_id.required_if' => 'The size field is required.',
        'ticket.order_ref.required_if' => 'The order ref field is required.',
        'ticket.topic.required_if' => 'The topic field is required.',
        'ticket.description.required_if' => 'The description field is required.',
    ];


    public function mount($leadId = null)
    {
        $this->ticket = new Ticket;
        $this->ticket->lead_id = $leadId;

        $this->selectedOutlet = Outlet::first();
        $this->outlet_item_type = $this->selectedOutlet->outlet_item_type;   
        
        $this->categories = TicketCategory::with('subCategories')->order()->first();
        $this->subCategories = $this->categories->subCategories;
        $this->tags = [];
        $this->outlets = Outlet::select('id', 'title')->get();
        $this->items = Item::select('id', 'title', 'description')->get()->toArray();

        $this->addItem();
    }

    public function render()
    {
        return view('livewire.orders.create');
    }

    public function showCreatingOrder()
    {
        $this->creatingOrder = true;
    }
    public function updatedTicketTicketCategoryId($value)
    {
        if ($value) {
            $selectedCategory = $this->categories->where('id', $value)->first();
            $this->subCategories = $selectedCategory->subCategories ?? [];

            $this->ticket->outlet_id = $value != 3 ? null : 0;
        } else {
        }
        $this->ticket['ticket_sub_category_id'] = 0;
        $this->ticket['tags'] =  [];
        $this->tags = [];


        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatedTicketTicketSubCategoryId($value)
    {
        if ($value) {
            $selectedSubCategory = $this->subCategories->where('id', $value)->first();

            $this->tags = $selectedSubCategory->tags ?? [];
        }
        $this->ticket['tags'] =  [];
    }


    public function updatedCreatingOrder($value)
    {
        $this->resetForm();
    }

    public function updatedTicketCrm($value)
    {
    }


    public function addItem($parent = null)
    {


        $item = [
            'id' => 0,
            'item_id' => null,
            'size_id' => null,
            'unit_price' => 0.0,
            'qty' => 1,
            'item_remarks' => '', 
        ];

        if (!is_numeric($parent)) {
            $item['extras'] = [];
            array_push($this->ticketItems, $item);
        } else {
            $extraItem = $this->ticketItems[$parent]['extras'];
            array_push($this->ticketItems[$parent]['extras'], $item);
        }
    }

    public function removeItem($index, $extraItemIndex = null)
    {
        // dd($index);
        if (is_numeric($extraItemIndex)) {
            //$extras = $this->ticketItems[$index]['extras'];
            //dd($extras);
            array_splice($this->ticketItems[$index]['extras'], $extraItemIndex, 1);
        } else {
            array_splice($this->ticketItems, $index, 1);
        }

        $this->updateCart();
    }

    public function updatedTicketItems($value, $name)
    {
        $field = explode('.', $name);

        if ($field[1] == 'item_id') {
            if ($value) {
                $item = ItemMaster::find($value);
                $this->ticketItems[$field[0]]['unit_price'] = $item->retail1;
            } else {
                $this->ticketItems[$field[0]]['unit_price'] = 0.0;
            }
        } elseif ($field[1] == 'extras') {
            if ($field[3] == 'item_id') {
                $item = ItemMaster::find($value);
                $this->ticketItems[$field[0]]['extras'][$field[2]]['unit_price'] = $item->retail1;
            }
        } else {
        }
        $this->updateCart();
    }

    public function updateCart()
    {
        $this->total = 0.0;

        foreach ($this->ticketItems as $key => $ticketItem) {
            $this->total = $this->total + ($ticketItem['unit_price'] * $ticketItem['qty']);

            foreach ($ticketItem['extras'] as $key => $extra) {
                $this->total = $this->total + ($extra['unit_price'] * $extra['qty']);
            }
        }
    }

    public function save()
    {

    $this->validate([ 
    'ticket.description' => 'required_if:ticket.ticket_category_id,1,2',
    'selectedOutletId' => 'required|integer|min:1|exists:outlets,id',
    'ticket.ticket_category_id' => 'required|exists:ticket_categories,id',
    'ticket.ticket_sub_category_id' => 'required|exists:ticket_sub_categories,id',
    'ticket.tags' => 'required|array|min:1',
    ]);
        
        // $this->validate();
        if ($this->ticket->ticket_category_id == 3 && $this->ticket->crm == 1) {
            $this->validate([
                'ticketItems.*.item_id' => 'required',
            ]);
        }

        $this->ticket->topic = $this->ticket->ticket_category_id == 3 ? "Order" : $this->ticket->topic;
        $this->ticket->lead_id = $this->leadId;
        $this->ticket->outlet_id = $this->selectedOutletId;
        $this->generateBillNo = $this->generateBillNo($this->ticket->outlet_id);
        $this->ticket->bill_no = $this->generateBillNo;
        Log::debug($this->ticket->bill_no);
        $this->ticket->save();
        
        $this->ticket->logActivity("Created");

        if ($this->ticket->ticket_category_id == 3 && $this->ticket->crm) {
            foreach ($this->ticketItems as $key => $_ticketItem) {
                $ticketItem = TicketItem::updateOrCreate(
                    [
                        'id' => $_ticketItem['id']
                    ],
                    [
                        'item_id'  => $_ticketItem['item_id'],
                        'ticket_id'  => $this->ticket->id,
                        'qty'  => $_ticketItem['qty'],
                        'unit_price'  => $_ticketItem['unit_price'],
                        'line_total'  => $_ticketItem['unit_price'] * $_ticketItem['qty'],
                        'item_remarks'  => $_ticketItem['item_remarks'],
                    ]
                );

                foreach ($_ticketItem['extras'] as $key => $_extra) {
                    $extraTicketItem = TicketItem::updateOrCreate(
                        [
                            'id' => $_extra['id']
                        ],
                        [
                            'item_id'  => $_extra['item_id'],
                            'ticket_id'  => $this->ticket->id,
                            'qty'  => $_extra['qty'],
                            'parent_item_id'  => $ticketItem->id,
                            'unit_price'  => $_extra['unit_price'],
                            'line_total'  => $_extra['unit_price'] * $_extra['qty'],
                        ]
                    );
                }
            }
        }

        $order = Ticket::with('lead', 'category', 'subCategory', 'items', 'items.item', 'outlet')->where('id', $this->ticket->id)->first();
       
        // return;
        // Log::info($jsonOrderDetails);
       
        // Log::info($response);
        SyncNewOrder::dispatch($order)->onQueue('high');
             

        $this->creatingOrder = false;
        $this->resetForm();
        $this->notification()->success(
            $title = 'Success',
            $description = 'Order successfull created'
        );

        if (!$this->leadId) {
            $this->emitTo('orders.index', 'refreshList');
        } else {
            $this->emitTo('leads.show', 'refreshCard');
        }

        event(new NotifyOrder());
    }


    public function resetForm()
    {
        $this->ticket = new Ticket;
        $this->ticket->lead_id = $this->leadId;
        $this->ticket->ticket_category_id = 3;
        $this->total = 0;
        $this->tags = [];
        $this->ticket->tags = [];
        $this->ticketItems = [];
        $this->addItem();

        $this->resetErrorBag();
        $this->resetValidation();
    }


    public function updatedTicketOutletId($value)
    {
        if ($value) {
            // Logic to handle outlet change
            // For example, load items from the main database based on the selected outlet
        }
    }


    public function generateBillNo($outletId)
    {
        // Count the number of existing tickets for the given outlet_id and topic 'order'
        $ticketCount = Ticket::where('topic', 'order')
                            ->where('outlet_id', $outletId)
                            ->count();

        // The next bill number is the current count plus 1
        $nextBillNo = $outletId . '-' . ($ticketCount + 1);

        return $nextBillNo;
    }


}
