<?php

namespace App\Livewire\Departments;

use App\Models\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Index extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    // Search and Filter Properties
    public $search = '';
    public $statusFilter = '';

    // Modal Properties
    public $showCreateDepartmentModal = false;
    public $showEditDepartmentModal = false;
    public $showViewDepartmentModal = false;
    public $showDeleteDepartmentModal = false;

    // Form Properties
    public $departmentName = '';
    public $departmentDescription = '';
    public $departmentStatus = true;

    // Selected Department
    public $selectedDepartment = null;
    public $selectedDepartmentId = null;

    // Statistics
    public $activeDepartmentsCount = 0;
    public $inactiveDepartmentsCount = 0;

    protected $listeners = [
        'refreshDepartments' => '$refresh',
    ];

    protected $rules = [
        'departmentName' => 'required|string|max:255|unique:departments,name',
        'departmentDescription' => 'nullable|string|max:1000',
        'departmentStatus' => 'required|boolean',
    ];

    protected $messages = [
        'departmentName.required' => 'Department name is required.',
        'departmentName.unique' => 'This department name already exists.',
        'departmentName.max' => 'Department name cannot exceed 255 characters.',
        'departmentDescription.max' => 'Description cannot exceed 1000 characters.',
        'departmentStatus.required' => 'Status is required.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->authorize('view_departments');
        $this->updateStatistics();
    }

    public function render()
    {
        $departments = Department::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('is_active', $this->statusFilter);
            })
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $this->updateStatistics();

        return view('livewire.departments.index', [
            'departments' => $departments,
        ])->layout('layouts.app');
    }

    public function openCreateDepartmentModal()
    {
        $this->authorize('create_departments');
        $this->resetForm();
        $this->showCreateDepartmentModal = true;
    }

    public function createDepartment()
    {
        $this->authorize('create_departments');
        
        $this->validate();

        try {
            Department::create([
                'name' => $this->departmentName,
                'description' => $this->departmentDescription,
                'is_active' => $this->departmentStatus,
            ]);

            $this->showCreateDepartmentModal = false;
            $this->resetForm();
            
            session()->flash('success', 'Department created successfully!');
            $this->dispatch('refreshDepartments');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create department. Please try again.');
        }
    }

    public function openEditDepartmentModal($departmentId)
    {
        $this->authorize('edit_departments');
        
        $this->selectedDepartment = Department::findOrFail($departmentId);
        $this->selectedDepartmentId = $departmentId;
        
        $this->departmentName = $this->selectedDepartment->name;
        $this->departmentDescription = $this->selectedDepartment->description;
        $this->departmentStatus = $this->selectedDepartment->is_active;
        
        $this->showEditDepartmentModal = true;
    }

    public function updateDepartment()
    {
        $this->authorize('edit_departments');
        
        $this->rules['departmentName'] = 'required|string|max:255|unique:departments,name,' . $this->selectedDepartmentId;
        
        $this->validate();

        try {
            $this->selectedDepartment->update([
                'name' => $this->departmentName,
                'description' => $this->departmentDescription,
                'is_active' => $this->departmentStatus,
            ]);

            $this->showEditDepartmentModal = false;
            $this->resetForm();
            
            session()->flash('success', 'Department updated successfully!');
            $this->dispatch('refreshDepartments');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update department. Please try again.');
        }
    }

    public function openViewDepartmentModal($departmentId)
    {
        $this->authorize('view_departments');
        
        $this->selectedDepartment = Department::withCount('users')->findOrFail($departmentId);
        
        $this->showViewDepartmentModal = true;
    }

    public function openDeleteDepartmentModal($departmentId)
    {
        $this->authorize('delete_departments');
        
        $this->selectedDepartment = Department::withCount('users')->findOrFail($departmentId);
        
        $this->selectedDepartmentId = $departmentId;
        $this->showDeleteDepartmentModal = true;
    }

    public function deleteDepartment()
    {
        $this->authorize('delete_departments');
        
        try {
            // Check if department has users
            if ($this->selectedDepartment->users_count > 0) {
                session()->flash('error', 'Cannot delete department with assigned users.');
                return;
            }

            $this->selectedDepartment->delete();
            
            $this->showDeleteDepartmentModal = false;
            $this->resetForm();
            
            session()->flash('success', 'Department deleted successfully!');
            $this->dispatch('refreshDepartments');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete department. Please try again.');
        }
    }

    public function toggleDepartmentStatus($departmentId)
    {
        $this->authorize('edit_departments');
        
        try {
            $department = Department::findOrFail($departmentId);
            $department->update([
                'is_active' => !$department->is_active
            ]);
            
            $status = $department->is_active ? 'activated' : 'deactivated';
            session()->flash('success', "Department {$status} successfully!");
            $this->dispatch('refreshDepartments');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update department status. Please try again.');
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    private function resetForm()
    {
        $this->departmentName = '';
        $this->departmentDescription = '';
        $this->departmentStatus = true;
        $this->selectedDepartment = null;
        $this->selectedDepartmentId = null;
        $this->resetValidation();
    }

    private function updateStatistics()
    {
        $this->activeDepartmentsCount = Department::where('is_active', true)->count();
        $this->inactiveDepartmentsCount = Department::where('is_active', false)->count();
    }
}