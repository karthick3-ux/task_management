<?php
// resources/views/livewire/departments/index.blade.php
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
                <h3 class="fw-bold mb-0">Department Management</h3>
                @if(auth()->user()->hasPermissionTo('create_departments') || auth()->user()->isSuperAdmin())
                <div class="col-auto d-flex w-sm-100">
                    <button type="button" class="btn btn-dark btn-set-task w-sm-100" wire:click="openCreateDepartmentModal">
                        <i class="icofont-plus-circle me-2 fs-6"></i>Create Department
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
                    <h6 class="mb-0 fw-bold">Search & Filter Departments</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="search" class="form-control" placeholder="Search by department name...">
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label">Status</label>
                            <select wire:model="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary w-100 d-block" wire:click="$refresh">
                                <i class="icofont-refresh"></i> Refresh
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-8">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100 d-block" wire:click="clearFilters">
                                <i class="icofont-close-line"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="row align-item-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
                    <h6 class="mb-0 fw-bold">Departments List ({{ $departments->total() }} Total)</h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success">{{ $activeDepartmentsCount }} Active</span>
                        <span class="badge bg-danger">{{ $inactiveDepartmentsCount }} Inactive</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Department Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Users Count</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $department)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar sm rounded-1 no-thumbnail 
                                                @if($department->is_active) bg-light-success text-success
                                                @else bg-light-danger text-danger
                                                @endif me-2">
                                                <i class="icofont-building fs-6"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold">{{ $department->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($department->description)
                                            <span class="text-muted" title="{{ $department->description }}">
                                                {{ Str::limit($department->description, 50) }}
                                            </span>
                                        @else
                                            <span class="text-muted fst-italic">No description</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $department->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $department->users_count ?? 0 }} {{ Str::plural('User', $department->users_count ?? 0) }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $department->created_at->format('M d, Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $department->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $department->updated_at->format('M d, Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $department->updated_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if(auth()->user()->hasPermissionTo('view_departments') || auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openViewDepartmentModal({{ $department->id }})" title="View Department">
                                                <i class="icofont-eye text-info"></i>
                                            </button>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermissionTo('edit_departments') || auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openEditDepartmentModal({{ $department->id }})" title="Edit Department">
                                                <i class="icofont-edit text-success"></i>
                                            </button>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermissionTo('delete_departments') || auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openDeleteDepartmentModal({{ $department->id }})" title="Delete Department">
                                                <i class="icofont-ui-delete text-danger"></i>
                                            </button>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermissionTo('edit_departments') || auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="toggleDepartmentStatus({{ $department->id }})" title="{{ $department->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="icofont-{{ $department->is_active ? 'close-line text-warning' : 'check text-success' }}"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="icofont-building fs-1"></i>
                                            <p class="mt-2">No departments found</p>
                                            @if($search || $statusFilter !== '')
                                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="clearFilters">
                                                    Clear Filters
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
                        {{ $departments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Department Modal -->
    @if($showCreateDepartmentModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-plus-circle me-2"></i>Create New Department
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showCreateDepartmentModal', false)"></button>
                </div>
                <form wire:submit.prevent="createDepartment">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Department Name *</label>
                                <input type="text" 
                                       wire:model.defer="departmentName" 
                                       class="form-control @error('departmentName') is-invalid @enderror" 
                                       placeholder="Enter department name"
                                       autocomplete="off">
                                @error('departmentName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status *</label>
                                <select wire:model.defer="departmentStatus" class="form-select @error('departmentStatus') is-invalid @enderror">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                @error('departmentStatus') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea wire:model.defer="departmentDescription" 
                                          class="form-control @error('departmentDescription') is-invalid @enderror" 
                                          rows="4" 
                                          placeholder="Enter department description (optional)"></textarea>
                                @error('departmentDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showCreateDepartmentModal', false)">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="icofont-plus-circle me-1"></i>Create Department</span>
                            <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Creating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Department Modal -->
    @if($showEditDepartmentModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-edit me-2"></i>Edit Department
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showEditDepartmentModal', false)"></button>
                </div>
                <form wire:submit.prevent="updateDepartment">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Department Name *</label>
                                <input type="text" 
                                       wire:model.defer="departmentName" 
                                       class="form-control @error('departmentName') is-invalid @enderror" 
                                       placeholder="Enter department name"
                                       autocomplete="off">
                                @error('departmentName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status *</label>
                                <select wire:model.defer="departmentStatus" class="form-select @error('departmentStatus') is-invalid @enderror">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                @error('departmentStatus') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea wire:model.defer="departmentDescription" 
                                          class="form-control @error('departmentDescription') is-invalid @enderror" 
                                          rows="4" 
                                          placeholder="Enter department description (optional)"></textarea>
                                @error('departmentDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showEditDepartmentModal', false)">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="icofont-save me-1"></i>Update Department</span>
                            <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Updating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- View Department Modal -->
    @if($showViewDepartmentModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-eye me-2"></i>Department Details
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showViewDepartmentModal', false)"></button>
                </div>
                <div class="modal-body">
                    @if($selectedDepartment)
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="text-center mb-4">
                                <div class="avatar xl rounded-circle {{ $selectedDepartment->is_active ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} mb-3" style="width: 80px; height: 80px; margin: 0 auto;">
                                    <i class="icofont-building fs-1"></i>
                                </div>
                                <h4>{{ $selectedDepartment->name }}</h4>
                                <span class="badge {{ $selectedDepartment->is_active ? 'bg-success' : 'bg-danger' }} fs-6">
                                    {{ $selectedDepartment->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="icofont-info-circle me-2"></i>Basic Information</h6>
                                    <p class="mb-1"><strong>Name:</strong> {{ $selectedDepartment->name }}</p>
                                    <p class="mb-1"><strong>Status:</strong> 
                                        <span class="badge {{ $selectedDepartment->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $selectedDepartment->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                    <p class="mb-0"><strong>Users:</strong> {{ $selectedDepartment->users_count ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="icofont-calendar me-2"></i>Timestamps</h6>
                                    <p class="mb-1"><strong>Created:</strong> {{ $selectedDepartment->created_at->format('M d, Y h:i A') }}</p>
                                    <p class="mb-0"><strong>Updated:</strong> {{ $selectedDepartment->updated_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                        @if($selectedDepartment->description)
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="icofont-file-text me-2"></i>Description</h6>
                                    <p class="mb-0">{{ $selectedDepartment->description }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showViewDepartmentModal', false)">Close</button>
                    @if(auth()->user()->hasPermissionTo('edit_departments') || auth()->user()->isSuperAdmin())
                    <button type="button" class="btn btn-primary" wire:click="openEditDepartmentModal({{ $selectedDepartment->id ?? 0 }})">
                        <i class="icofont-edit me-1"></i>Edit Department
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Department Modal -->
    @if($showDeleteDepartmentModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="icofont-ui-delete me-2"></i>Delete Department
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showDeleteDepartmentModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="avatar xl rounded-circle bg-light-danger text-danger mb-3" style="width: 80px; height: 80px; margin: 0 auto;">
                            <i class="icofont-building fs-1"></i>
                        </div>
                        <h6>Are you sure you want to delete this department?</h6>
                        <p class="mb-0"><strong>{{ $selectedDepartment->name ?? '' }}</strong></p>
                        <small class="text-muted">{{ $selectedDepartment->users_count ?? 0 }} users assigned</small>
                    </div>
                    
                    @if(($selectedDepartment->users_count ?? 0) > 0)
                    <div class="alert alert-danger">
                        <i class="icofont-warning-alt me-2"></i>
                        <strong>Cannot Delete!</strong> This department has {{ $selectedDepartment->users_count }} users assigned to it. Please reassign or remove users before deleting this department.
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="icofont-warning-alt me-2"></i>
                        <strong>Warning!</strong> This action cannot be undone. All data associated with this department will be permanently removed.
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showDeleteDepartmentModal', false)">
                        <i class="icofont-close-line me-1"></i>Cancel
                    </button>
                    @if(($selectedDepartment->users_count ?? 0) == 0)
                    <button type="button" class="btn btn-danger" wire:click="deleteDepartment" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="icofont-ui-delete me-1"></i>Delete Department</span>
                        <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Deleting...</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>