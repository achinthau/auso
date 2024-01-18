<?php

namespace App\Http\Livewire\Dashboard\Admin\Partials;

use App\Models\Agent;
use App\Repositories\ApiManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UserSection extends Component
{
    public function render()
    {
        $users = Agent::with('currentActiveQueues')->get();

        return view(
            'livewire.dashboard.admin.partials.user-section',
            ['users' => $users]
        );
    }

    
    public function listenCall($extension)
    {
        $data = [
            [
                'name' => 'agent',
                'contents' => $extension
            ],
            [
                'name' => 'supervisor',
                'contents' => Auth::user()->extension ?? '999'
            ],
            
        ];
        ApiManager::listentCall($data);
    }
}
