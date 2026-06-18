<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedDepartment = '';
    public $selectedRole = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $selectedUser = null;

    // Create/Edit form properties
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $department_id = '';
    public $role = '';
    public $color = '#6238B3'; // Add color field
    public $isEditing = false;

    protected $paginationTheme = 'bootstrap';

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|exists:roles,name',
            'color' => 'required|regex:/^#[a-fA-F0-9]{6}$/', // Validate hex color
        ];

        if ($this->isEditing) {
            $rules['email'] = 'required|email|unique:users,email,' . $this->selectedUser->id;
            $rules['password'] = 'nullable|min:8|confirmed';
        } else {
            $rules['email'] = 'required|email|unique:users,email';
            $rules['password'] = 'required|min:8|confirmed';
        }

        return $rules;
    }

    public function mount()
    {
        $this->authorize('view_users', User::class);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedDepartment()
    {
        $this->resetPage();
    }

    public function updatingSelectedRole()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateModal()
    {
        $this->authorize('create', User::class);
        
        $this->resetForm();
        $this->color = User::getRandomColor(); // Auto-assign random color
        $this->showCreateModal = true;
    }

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);
        
        $this->selectedUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->department_id = $user->department_id;
        $this->role = $user->roles->first()?->name ?? '';
        $this->color = $user->color; // Load user's current color
        $this->isEditing = true;
        $this->showEditModal = true;
    }

    public function openDeleteModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->authorize('delete', $user);
        
        $this->selectedUser = $user;
        $this->showDeleteModal = true;
    }

    public function createUser()
    {
        $this->authorize('create', User::class);
        $this->validate();

        try {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'department_id' => $this->department_id ?: null,
                'color' => $this->color, // Add color to creation
            ]);

            $user->assignRole($this->role);

            session()->flash('success', 'User created successfully!');
            $this->resetForm();
            $this->showCreateModal = false;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create user. Please try again.');
        }
    }

    public function updateUser()
    {
        $this->authorize('update', $this->selectedUser);
        $this->validate();

        try {
            $updateData = [
                'name' => $this->name,
                'email' => $this->email,
                'department_id' => $this->department_id ?: null,
                'color' => $this->color, // Add color to update
            ];

            if ($this->password) {
                $updateData['password'] = Hash::make($this->password);
            }

            $this->selectedUser->update($updateData);
            $this->selectedUser->syncRoles([$this->role]);

            session()->flash('success', 'User updated successfully!');
            $this->resetForm();
            $this->showEditModal = false;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update user. Please try again.');
        }
    }

    public function deleteUser()
    {
        $this->authorize('delete', $this->selectedUser);

        if ($this->selectedUser->isSuperAdmin()) {
            session()->flash('error', 'Cannot delete super admin user.');
            $this->showDeleteModal = false;
            return;
        }

        try {
            $this->selectedUser->delete();
            session()->flash('success', 'User deleted successfully!');
            $this->showDeleteModal = false;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete user. Please try again.');
        }
    }

    public function selectColor($color)
    {
        $this->color = $color;
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->department_id = '';
        $this->role = '';
        $this->color = '#6238B3'; // Reset to default color
        $this->isEditing = false;
        $this->selectedUser = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = User::with(['department', 'roles']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedDepartment) {
            $query->where('department_id', $this->selectedDepartment);
        }

        if ($this->selectedRole) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->selectedRole);
            });
        }

        $users = $query->orderBy($this->sortBy, $this->sortDirection)
                      ->paginate(15);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $defaultColors = User::getDefaultColors(); // Get available colors

        return view('livewire.users.index', [
            'users' => $users,
            'departments' => $departments,
            'roles' => $roles,
            'defaultColors' => $defaultColors, // Pass colors to view
        ])->layout('layouts.app');
    }
}