<?php
// resources/views/livewire/roles/index.blade.php
?>
<div>
    <!-- Success/Error Messages -->
    @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="icofont-check-circled me-2"></i>
        <strong>Success!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="icofont-close-circled me-2"></i>
        <strong>Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Page Header -->
    <div class="row align-items-center">
        <div class="border-0 mb-4">
            <div class="card-header py-3 no-bg bg-transparent d-flex align-items-center px-0 justify-content-between border-bottom flex-wrap">
                <h3 class="fw-bold mb-0">Roles & Permissions Management</h3>
                @if(auth()->user()->hasPermissionTo('create_roles') || auth()->user()->isSuperAdmin())
                <div class="col-auto d-flex w-sm-100">
                    <button type="button" class="btn btn-dark btn-set-task w-sm-100" wire:click="openCreateRoleModal">
                        <i class="icofont-plus-circle me-2 fs-6"></i>Create Role
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="row align-item-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
                    <h6 class="mb-0 fw-bold">Search Roles</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-6 col-md-8">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="search" class="form-control" placeholder="Search by role name...">
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary w-100 d-block" wire:click="$refresh">
                                <i class="icofont-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="row align-item-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
                    <h6 class="mb-0 fw-bold">Roles List ({{ $roles->total() }} Total)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Guard</th>
                                    <th>Users Count</th>
                                    <th>Permissions</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar sm rounded-1 no-thumbnail 
                                                @if($role->name === 'super_admin') bg-light-danger text-danger
                                                @elseif($role->name === 'admin') bg-light-primary text-primary
                                                @elseif($role->name === 'manager') bg-light-warning text-warning
                                                @else bg-light-secondary text-secondary
                                                @endif me-2">
                                                <i class="icofont-shield fs-6"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                                @if($role->name === 'super_admin')
                                                    <span class="badge bg-danger ms-2">SYSTEM</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $role->guard_name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $role->users_count }} {{ Str::plural('User', $role->users_count) }}</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openPermissionsModal({{ $role->id }})">
                                            <i class="icofont-key me-1"></i>
                                            {{ $role->permissions->count() }} Permissions
                                        </button>
                                    </td>
                                    <td>
                                        <small>{{ $role->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openPermissionsModal({{ $role->id }})" title="Manage Permissions">
                                                <i class="icofont-key text-primary"></i>
                                            </button>
                                            
                                            @if($role->name !== 'super_admin' || auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openEditRoleModal({{ $role->id }})" title="Edit Role">
                                                <i class="icofont-edit text-success"></i>
                                            </button>
                                            @endif
                                            
                                            @if($role->name !== 'super_admin')
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openDeleteRoleModal({{ $role->id }})" title="Delete Role">
                                                <i class="icofont-ui-delete text-danger"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="icofont-shield fs-1"></i>
                                            <p class="mt-2">No roles found</p>
                                            @if($search)
                                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="$set('search', '')">
                                                    Clear Search
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $roles->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Role Modal -->
    @if($showCreateRoleModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-plus-circle me-2"></i>Create New Role
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showCreateRoleModal', false)"></button>
                </div>
                <form wire:submit.prevent="createRole">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Role Name *</label>
                                <input type="text" 
                                       wire:model.defer="roleName" 
                                       class="form-control @error('roleName') is-invalid @enderror" 
                                       placeholder="Enter role name"
                                       autocomplete="off"
                                       x-data
                                       x-on:change="$wire.set('roleName', $event.target.value)"
                                       x-on:input="$wire.set('roleName', $event.target.value)">
                                @error('roleName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Guard Name *</label>
                                <select wire:model.defer="roleGuardName" class="form-select @error('roleGuardName') is-invalid @enderror">
                                    <option value="web">Web</option>
                                    <option value="api">API</option>
                                </select>
                                @error('roleGuardName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Permissions</label>
                                <div class="permissions-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 15px;">
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" wire:click="selectAllPermissions">
                                            <i class="icofont-check-alt me-1"></i>Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllPermissions">
                                            <i class="icofont-close-line me-1"></i>Deselect All
                                        </button>
                                    </div>
                                    @foreach($groupedPermissions as $group => $permissions)
                                    <div class="permission-group mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2 p-2 bg-light rounded">
                                            <div class="d-flex align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-info me-2" wire:click="togglePermissionGroup('{{ $group }}')">
                                                    <i class="icofont-{{ collect($permissions)->pluck('name')->diff($selectedPermissions)->isEmpty() ? 'check-alt' : 'plus' }} me-1"></i>
                                                    {{ $permissionGroups[$group] ?? ucfirst($group) }}
                                                </button>
                                                <small class="text-muted">({{ count($permissions) }} permissions)</small>
                                            </div>
                                            <span class="badge bg-secondary">
                                                {{ count(array_intersect(collect($permissions)->pluck('name')->toArray(), $selectedPermissions)) }}/{{ count($permissions) }}
                                            </span>
                                        </div>
                                        <div class="row ms-3">
                                            @foreach($permissions as $permission)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           wire:model.defer="selectedPermissions" 
                                                           value="{{ $permission->name }}" 
                                                           id="create_perm_{{ $permission->id }}">
                                                    <label class="form-check-label" for="create_perm_{{ $permission->id }}">
                                                        {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showCreateRoleModal', false)">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="icofont-plus-circle me-1"></i>Create Role</span>
                            <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Creating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Role Modal -->
    @if($showEditRoleModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-edit me-2"></i>Edit Role
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showEditRoleModal', false)"></button>
                </div>
                <form wire:submit.prevent="updateRole">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Role Name *</label>
                                <input type="text" 
                                       wire:model.defer="roleName" 
                                       class="form-control @error('roleName') is-invalid @enderror" 
                                       placeholder="Enter role name"
                                       autocomplete="off"
                                       x-data
                                       x-on:change="$wire.set('roleName', $event.target.value)"
                                       x-on:input="$wire.set('roleName', $event.target.value)">
                                @error('roleName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Guard Name *</label>
                                <select wire:model.defer="roleGuardName" class="form-select @error('roleGuardName') is-invalid @enderror">
                                    <option value="web">Web</option>
                                    <option value="api">API</option>
                                </select>
                                @error('roleGuardName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Permissions</label>
                                <div class="permissions-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 15px;">
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" wire:click="selectAllPermissions">
                                            <i class="icofont-check-alt me-1"></i>Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllPermissions">
                                            <i class="icofont-close-line me-1"></i>Deselect All
                                        </button>
                                    </div>
                                    @foreach($groupedPermissions as $group => $permissions)
                                    <div class="permission-group mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2 p-2 bg-light rounded">
                                            <div class="d-flex align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-info me-2" wire:click="togglePermissionGroup('{{ $group }}')">
                                                    <i class="icofont-{{ collect($permissions)->pluck('name')->diff($selectedPermissions)->isEmpty() ? 'check-alt' : 'plus' }} me-1"></i>
                                                    {{ $permissionGroups[$group] ?? ucfirst($group) }}
                                                </button>
                                                <small class="text-muted">({{ count($permissions) }} permissions)</small>
                                            </div>
                                            <span class="badge bg-secondary">
                                                {{ count(array_intersect(collect($permissions)->pluck('name')->toArray(), $selectedPermissions)) }}/{{ count($permissions) }}
                                            </span>
                                        </div>
                                        <div class="row ms-3">
                                            @foreach($permissions as $permission)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           wire:model.defer="selectedPermissions" 
                                                           value="{{ $permission->name }}" 
                                                           id="edit_perm_{{ $permission->id }}">
                                                    <label class="form-check-label" for="edit_perm_{{ $permission->id }}">
                                                        {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showEditRoleModal', false)">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="icofont-save me-1"></i>Update Role</span>
                            <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Updating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Permissions Management Modal -->
    @if($showPermissionsModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-key me-2"></i>Manage Permissions: {{ ucfirst(str_replace('_', ' ', $selectedRole->name ?? '')) }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showPermissionsModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="permissions-container" style="max-height: 500px; overflow-y: auto;">
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary me-2" wire:click="selectAllPermissions">
                                <i class="icofont-check-alt me-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllPermissions">
                                <i class="icofont-close-line me-1"></i>Deselect All
                            </button>
                            <span class="ms-3 text-muted">
                                <strong>{{ count($selectedPermissions) }}</strong> of <strong>{{ array_sum(array_map('count', $groupedPermissions)) }}</strong> permissions selected
                            </span>
                        </div>
                        @foreach($groupedPermissions as $group => $permissions)
                        <div class="card mb-3">
                            <div class="card-header py-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-info me-2" wire:click="togglePermissionGroup('{{ $group }}')">
                                            <i class="icofont-{{ collect($permissions)->pluck('name')->diff($selectedPermissions)->isEmpty() ? 'check-alt' : 'plus' }} me-1"></i>
                                            {{ $permissionGroups[$group] ?? ucfirst($group) }}
                                        </button>
                                        <small class="text-muted">({{ count($permissions) }} permissions)</small>
                                    </div>
                                    <span class="badge bg-secondary">
                                        {{ count(array_intersect(collect($permissions)->pluck('name')->toArray(), $selectedPermissions)) }}/{{ count($permissions) }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($permissions as $permission)
                                    <div class="col-md-4 col-lg-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   wire:model.defer="selectedPermissions" 
                                                   value="{{ $permission->name }}" 
                                                   id="perm_modal_{{ $permission->id }}">
                                            <label class="form-check-label" for="perm_modal_{{ $permission->id }}">
                                                <small>{{ ucfirst(str_replace('_', ' ', $permission->name)) }}</small>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showPermissionsModal', false)">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updatePermissions" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="icofont-save me-1"></i>Update Permissions</span>
                        <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Updating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Role Modal -->
    @if($showDeleteRoleModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="icofont-ui-delete me-2"></i>Delete Role
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showDeleteRoleModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="avatar xl rounded-circle bg-light-danger text-danger mb-3" style="width: 80px; height: 80px; margin: 0 auto;">
                            <i class="icofont-shield fs-1"></i>
                        </div>
                        <h6>Are you sure you want to delete this role?</h6>
                        <p class="mb-0"><strong>{{ ucfirst(str_replace('_', ' ', $selectedRole->name ?? '')) }}</strong></p>
                        <small class="text-muted">{{ $selectedRole->users_count ?? 0 }} users assigned</small>
                    </div>
                    
                    @if(($selectedRole->users_count ?? 0) > 0)
                    <div class="alert alert-danger">
                        <i class="icofont-warning-alt me-2"></i>
                        <strong>Cannot Delete!</strong> This role has {{ $selectedRole->users_count }} users assigned to it. Please reassign or remove users before deleting this role.
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="icofont-warning-alt me-2"></i>
                        <strong>Warning!</strong> This action cannot be undone. All permissions associated with this role will be permanently removed.
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showDeleteRoleModal', false)">
                        <i class="icofont-close-line me-1"></i>Cancel
                    </button>
                    @if(($selectedRole->users_count ?? 0) === 0)
                    <button type="button" class="btn btn-danger" wire:click="deleteRole" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="icofont-ui-delete me-1"></i>Delete Role</span>
                        <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Deleting...</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>