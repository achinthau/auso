<?php

namespace App\Http\Livewire\Tables;

use App\Models\Abandoned;
use App\Models\CallCenter\AbandonedCall;
use Mediconesystems\LivewireDatatables\BooleanColumn;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\DateColumn;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;
use Mediconesystems\LivewireDatatables\TimeColumn;

class AbandonedCallTable extends LivewireDatatable
{

    public $hideable = 'select';
    public $exportable = true;

    public function builder()
    {
        return AbandonedCall::orderBy('called_at','DESC');
    }

    public function columns()
    {
        return [
            Column::name('ani')->label('From')->filterable(),
            Column::name('queuename')->label('Queue')->filterable($this->statues),
            DateColumn::name('called_at')->label('Called At')->filterable(),
            BooleanColumn::name('recalled_status')->label('Recalled')->filterable(),
            DateColumn::name('recalled_at')->label('Recalled At')->filterable(),
        ];
    }

    public function getStatuesProperty()
    {
        return array_unique(AbandonedCall::all()->pluck('queuename')->toArray());
    }


    public function doDateFilterStart($index, $start)
    {

        $this->activeDateFilters[$index]['start'] = $start=="" ? $start : $start." 00:00:00";
        $this->page = 1;
        $this->setSessionStoredFilters();
    }

    public function doDateFilterEnd($index, $end)
    {
        $this->activeDateFilters[$index]['end'] = $end=="" ? $end : $end." 23:59:59";;
        $this->page = 1;
        $this->setSessionStoredFilters();
    }
}
