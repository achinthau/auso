<?php
namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    use HasFactory;

    // Specify the table name if it's not the pluralized form of the model name
    protected $table = 'pos_order_reference';

    // Enable mass assignment for all fields in your table
    protected $guarded = [];

    // Specify the data type of certain fields, particularly for casting JSON data
    protected $casts = [
        'data' => 'array', // Cast 'data' field to array
        'success' => 'boolean', // Ensure 'success' field is treated as boolean
        'tran_date' => 'date', // Cast 'tran_date' to date
        'tran_time' => 'datetime:H:i:s', // Cast 'tran_time' with specific format
    ];
}
