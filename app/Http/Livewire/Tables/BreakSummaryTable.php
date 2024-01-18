<?php

namespace App\Http\Livewire\Tables;

use App\Models\Agent;
use App\Models\AgentBreakSummary;
use Mediconesystems\LivewireDatatables\BooleanColumn;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\DateColumn;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class BreakSummaryTable extends LivewireDatatable
{
    public $hideable = 'select';
    public $exportable = true;

    public function builder()
    {
        return AgentBreakSummary::join('au_user', 'au_user.id', 'au_agentbreak_summery.agentid')->orderBy('breaktime','DESC');
    }

    public function columns()
    {
        return [
            Column::name('agent.username')->filterable(Agent::all()->pluck('username')->toArray()),
            BooleanColumn::name('agent.status')->filterable()->hide(),
            DateColumn::name('breaktime')->filterable(),
            DateColumn::name('unbreaktime')->filterable(),
            Column::name('desc')->filterable(),
        ];
    }
}
