<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Department;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $totalUsers;
    public $totalDepartments;
    public $recentUsers;
    public $usersByDepartment;

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $this->totalUsers = User::count();
        $this->totalDepartments = Department::where('is_active', true)->count();
        $this->recentUsers = User::with('department', 'roles')
            ->latest()
            ->take(5)
            ->get();
        
        $this->usersByDepartment = Department::withCount('users')
            ->where('is_active', true)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}