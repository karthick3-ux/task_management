<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateRoleModal = false;
    public $showEditRoleModal = false;
    public $showDeleteRoleModal = false;
    public $showPermissionsModal = false;
    public $selectedRole = null;

    // Role form properties
    public $roleName = '';
    public $roleGuardName = 'web';
    public $selectedPermissions = [];
    public $isEditing = false;

    // Permission management
    public $groupedPermissions = [];
    public $permissionGroups = [
        'users' => 'User Management',
        'departments' => 'Department Management',
        'projects' => 'Project Management',
        'tasks' => 'Task Management',
        'reports' => 'Reports & Analytics',
        'settings' => 'System Settings',
    ];

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'roleName' => 'required|string|max:255|unique:roles,name',
        'roleGuardName' => 'required|string',
        'selectedPermissions' => 'array',
    ];

    protected $messages = [
        'roleName.required' => 'Role name is required',
        'roleName.unique' => 'Role name already exists',
        'roleGuardName.required' => 'Guard name is required',
    ];

    public function mount()
    {
        // Check permissions
        if (!auth()->user()->hasPermissionTo('manage_roles') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'You do not have permission to manage roles.');
        }

        $this->loadGroupedPermissions();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function loadGroupedPermissions()
    {
        $permissions = Permission::orderBy('name')->get();
        $this->groupedPermissions = [];

        foreach ($permissions as $permission) {
            $parts = explode('_', $permission->name);
            $group = count($parts) > 1 ? $parts[1] : 'general';
            
            if (!isset($this->groupedPermissions[$group])) {
                $this->groupedPermissions[$group] = [];
            }
            
            $this->groupedPermissions[$group][] = $permission;
        }
    }

    public function openCreateRoleModal()
    {
        $this->resetForm();
        $this->showCreateRoleModal = true;
    }

    public function openEditRoleModal($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        
        if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            session()->flash('error', 'You cannot edit the super admin role.');
            return;
        }

        $this->selectedRole = $role;
        $this->roleName = $role->name;
        $this->roleGuardName = $role->guard_name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->isEditing = true;
        $this->showEditRoleModal = true;
    }

    public function openDeleteRoleModal($roleId)
    {
        $role = Role::withCount('users')->findOrFail($roleId);
        
        if ($role->name === 'super_admin') {
            session()->flash('error', 'You cannot delete the super admin role.');
            return;
        }

        $this->selectedRole = $role;
        $this->showDeleteRoleModal = true;
    }

    public function openPermissionsModal($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->selectedRole = $role;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showPermissionsModal = true;
    }

    public function createRole()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $this->roleName,
                'guard_name' => $this->roleGuardName,
            ]);

            if (!empty($this->selectedPermissions)) {
                $role->givePermissionTo($this->selectedPermissions);
            }

            DB::commit();

            Log::info('Role created successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'created_by' => auth()->id(),
            ]);

            session()->flash('success', 'Role created successfully!');
            $this->resetForm();
            $this->showCreateRoleModal = false;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role', [
                'error' => $e->getMessage(),
                'created_by' => auth()->id(),
            ]);

            session()->flash('error', 'Failed to create role. Please try again.');
        }
    }

    public function updateRole()
    {
        $this->rules['roleName'] = 'required|string|max:255|unique:roles,name,' . $this->selectedRole->id;
        $this->validate();

        try {
            DB::beginTransaction();

            $this->selectedRole->update([
                'name' => $this->roleName,
                'guard_name' => $this->roleGuardName,
            ]);

            $this->selectedRole->syncPermissions($this->selectedPermissions);

            DB::commit();

            Log::info('Role updated successfully', [
                'role_id' => $this->selectedRole->id,
                'role_name' => $this->selectedRole->name,
                'updated_by' => auth()->id(),
            ]);

            session()->flash('success', 'Role updated successfully!');
            $this->resetForm();
            $this->showEditRoleModal = false;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role', [
                'role_id' => $this->selectedRole->id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id(),
            ]);

            session()->flash('error', 'Failed to update role. Please try again.');
        }
    }

    public function deleteRole()
    {
        if ($this->selectedRole->users_count > 0) {
            session()->flash('error', 'Cannot delete role that has users assigned to it.');
            $this->showDeleteRoleModal = false;
            return;
        }

        try {
            $roleName = $this->selectedRole->name;
            $this->selectedRole->delete();

            Log::info('Role deleted successfully', [
                'role_name' => $roleName,
                'deleted_by' => auth()->id(),
            ]);

            session()->flash('success', 'Role deleted successfully!');
            $this->showDeleteRoleModal = false;

        } catch (\Exception $e) {
            Log::error('Failed to delete role', [
                'role_id' => $this->selectedRole->id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id(),
            ]);

            session()->flash('error', 'Failed to delete role. Please try again.');
        }
    }

    public function updatePermissions()
    {
        try {
            $this->selectedRole->syncPermissions($this->selectedPermissions);

            Log::info('Role permissions updated', [
                'role_id' => $this->selectedRole->id,
                'permissions' => $this->selectedPermissions,
                'updated_by' => auth()->id(),
            ]);

            session()->flash('success', 'Permissions updated successfully!');
            $this->showPermissionsModal = false;

        } catch (\Exception $e) {
            Log::error('Failed to update permissions', [
                'role_id' => $this->selectedRole->id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id(),
            ]);

            session()->flash('error', 'Failed to update permissions. Please try again.');
        }
    }

    public function togglePermissionGroup($group)
    {
        $groupPermissions = collect($this->groupedPermissions[$group] ?? [])->pluck('name')->toArray();
        
        $allSelected = !array_diff($groupPermissions, $this->selectedPermissions);
        
        if ($allSelected) {
            // Remove all permissions from this group
            $this->selectedPermissions = array_diff($this->selectedPermissions, $groupPermissions);
        } else {
            // Add all permissions from this group
            $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $groupPermissions));
        }
    }

    public function selectAllPermissions()
    {
        $this->selectedPermissions = Permission::pluck('name')->toArray();
    }

    public function deselectAllPermissions()
    {
        $this->selectedPermissions = [];
    }

    public function resetForm()
    {
        $this->roleName = '';
        $this->roleGuardName = 'web';
        $this->selectedPermissions = [];
        $this->isEditing = false;
        $this->selectedRole = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Role::where('name','!=','super_admin')->withCount('users');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $roles = $query->orderBy('name')->paginate(10);

        return view('livewire.roles.index', [
            'roles' => $roles,
        ])->layout('layouts.app');
    }
}
