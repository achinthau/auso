<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_ref', 
        'barcode', 
        'descr', 
        'retail1',
        // Add any other attributes you need to be mass assignable
    ];

}
