<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueCount extends Model
{
    use HasFactory;

    protected $connection = "mysql-old";
    protected $table = "queuecount";

    protected $casts=[
        'date'=>'datetime'
    ];

    public function agentInfo()
    {
        return $this->belongsTo(Agent::class,'agent','extension');
    }


    public function scopeAnswered($query)
    {
        $query->where('status',2);
    }

    public function scopeToday($query)
    {
        $query->whereBetween('date',[Carbon::now()->startOfDay(),Carbon::now()->endOfDay()]);
    }

    public function scopeTest($query)
    {
        return $query;
    }
    
}
