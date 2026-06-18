
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
                <h3 class="fw-bold mb-0">Employee Management</h3>
                @can('create', App\Models\User::class)
                <div class="col-auto d-flex w-sm-100">
                    <button type="button" class="btn btn-dark btn-set-task w-sm-100" wire:click="openCreateModal">
                        <i class="icofont-plus-circle me-2 fs-6"></i>Add Employee
                    </button>
                </div>
                @endcan
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="row align-item-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
                    <h6 class="mb-0 fw-bold">Filter Employees</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="search" class="form-control" placeholder="Search by name or email...">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Department</label>
                            <select wire:model="selectedDepartment" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Role</label>
                            <select wire:model="selectedRole" class="form-select">
                                <option value="">All Roles</option>
                                @foreach($roles as $roleOption)
                                    <option value="{{ $roleOption->name }}">{{ ucfirst($roleOption->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
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

    <!-- Users Table -->
    <div class="row align-item-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
                    <h6 class="mb-0 fw-bold">Employee List ({{ $users->total() }} Total)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="width:100%">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="#" wire:click.prevent="sortBy('name')" class="text-decoration-none text-dark">
                                            Employee
                                            @if($sortBy == 'name')
                                                <i class="icofont-{{ $sortDirection == 'asc' ? 'caret-up' : 'caret-down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="#" wire:click.prevent="sortBy('email')" class="text-decoration-none text-dark">
                                            Email
                                            @if($sortBy == 'email')
                                                <i class="icofont-{{ $sortDirection == 'asc' ? 'caret-up' : 'caret-down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Color</th>
                                    <th>Last Login</th>
                                    <th>
                                        <a href="#" wire:click.prevent="sortBy('created_at')" class="text-decoration-none text-dark">
                                            Joined
                                            @if($sortBy == 'created_at')
                                                <i class="icofont-{{ $sortDirection == 'asc' ? 'caret-up' : 'caret-down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar sm rounded-circle me-2" style="background-color: {{ $user->color }}; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <span class="fw-bold">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->department)
                                            <span class="badge bg-light text-dark">{{ $user->department->name }}</span>
                                        @else
                                            <span class="text-muted">No Department</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->roles->isNotEmpty())
                                            @php
                                                $roleColors = [
                                                    'super_admin' => 'bg-danger',
                                                    'admin' => 'bg-primary',
                                                    'manager' => 'bg-warning text-dark',
                                                    'user' => 'bg-secondary'
                                                ];
                                                $roleColor = $roleColors[$user->roles->first()->name] ?? 'bg-secondary';
                                            @endphp
                                            <span class="badge {{ $roleColor }}">{{ ucfirst(str_replace('_', ' ', $user->roles->first()->name)) }}</span>
                                        @else
                                            <span class="text-muted">No Role</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="color-swatch me-2" style="width: 20px; height: 20px; background-color: {{ $user->color }}; border-radius: 50%; border: 2px solid #dee2e6;"></div>
                                            <small class="text-muted">{{ $user->color }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($user->last_login_at)
                                            <small>{{ $user->last_login_at->format('M d, Y') }}</small><br>
                                            <small class="text-muted">{{ $user->last_login_at->format('h:i A') }}</small>
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $user->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('update', $user)
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openEditModal({{ $user->id }})" title="Edit User">
                                                <i class="icofont-edit text-success"></i>
                                            </button>
                                            @endcan
                                            
                                            @can('delete', $user)
                                            @if(!$user->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openDeleteModal({{ $user->id }})" title="Delete User">
                                                <i class="icofont-ui-delete text-danger"></i>
                                            </button>
                                            @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="icofont-users-alt-4 fs-1"></i>
                                            <p class="mt-2">No employees found</p>
                                            @if($search || $selectedDepartment || $selectedRole)
                                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="$set('search', ''); $set('selectedDepartment', ''); $set('selectedRole', '')">
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
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

   @if($showCreateModal)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New Employee</h5>
                <button type="button" class="btn-close" wire:click="$set('showCreateModal', false)"></button>
            </div>
            <form wire:submit.prevent="createUser">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" 
                                   wire:model.defer="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   placeholder="Enter full name"
                                   autocomplete="off"
                                   x-data
                                   x-on:change="$wire.set('name', $event.target.value)"
                                   x-on:input="$wire.set('name', $event.target.value)">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" 
                                   wire:model.defer="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   placeholder="Enter email address"
                                   autocomplete="new-email"
                                   x-data
                                   x-on:change="$wire.set('email', $event.target.value)"
                                   x-on:input="$wire.set('email', $event.target.value)">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                             <div class="input-group">
                            <input type="password" id="password"
                                   wire:model.defer="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   placeholder="Enter password"
                                   autocomplete="new-password"
                                   x-data
                                   x-on:change="$wire.set('password', $event.target.value)"
                                  >
                                   <span class="input-group-text toggle-password" onclick="togglePassword()">
                                    <i class="icofont-eye-blocked toggle-icon" id="toggleIcon" ></i>
                                            </span>
                                            </div>
                            @error('password')    
<div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password *</label>
                              <div class="input-group">
                            <input type="password" id="password2"
                                   wire:model.defer="password_confirmation" 
                                   class="form-control" 
                                   placeholder="Confirm password"
                                   autocomplete="new-password"
                                   x-data
                                   x-on:change="$wire.set('password_confirmation', $event.target.value)"
                                  >
                                   <span class="input-group-text toggle-password" onclick="togglePassword2()">
                                    <i class="icofont-eye-blocked toggle-icon" id="toggleIcon2" ></i>
                                 </span>
                                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select wire:model.defer="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select wire:model.defer="role" class="form-select @error('role') is-invalid @enderror">
                                <option value="">Select Role</option>
                                @foreach($roles as $roleOption)
                                    <option value="{{ $roleOption->name }}">{{ ucfirst(str_replace('_', ' ', $roleOption->name)) }}</option>
                                @endforeach
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Employee Color *</label>
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar rounded-circle me-3" style="background-color: {{ $color }}; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    {{ $name ? strtoupper(substr($name, 0, 2)) : 'AB' }}
                                </div>
                                <input type="color" wire:model.defer="color" class="form-control form-control-color @error('color') is-invalid @enderror" style="width: 60px; height: 40px;">
                                <small class="text-muted ms-2">{{ $color }}</small>
                            </div>
                            <div class="color-palette mb-2">
                                <small class="text-muted d-block mb-2">Quick Colors:</small>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($defaultColors as $defaultColor)
                                    <button type="button" 
                                            class="btn p-0 border {{ $color === $defaultColor ? 'border-dark border-2' : 'border-light' }}" 
                                            style="width: 30px; height: 30px; background-color: {{ $defaultColor }}; border-radius: 50%;"
                                            wire:click="selectColor('{{ $defaultColor }}')"
                                            title="{{ $defaultColor }}">
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @error('color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showCreateModal', false)">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="icofont-plus-circle me-1"></i>Create Employee</span>
                        <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Creating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Edit User Modal with Autocomplete Fix -->
@if($showEditModal)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Employee</h5>
                <button type="button" class="btn-close" wire:click="$set('showEditModal', false)"></button>
            </div>
            <form wire:submit.prevent="updateUser">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" 
                                   wire:model.defer="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   placeholder="Enter full name"
                                   autocomplete="off"
                                   x-data
                                   x-on:change="$wire.set('name', $event.target.value)"
                                   x-on:input="$wire.set('name', $event.target.value)">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" 
                                   wire:model.defer="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   placeholder="Enter email address"
                                   autocomplete="email"
                                   x-data
                                   x-on:change="$wire.set('email', $event.target.value)"
                                   x-on:input="$wire.set('email', $event.target.value)">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                             <div class="input-group">
                            <input type="password" id="password"
                                   wire:model.defer="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   placeholder="Enter new password"
                                   autocomplete="new-password"
                                   x-data
                                   x-on:change="$wire.set('password', $event.target.value)">
                                     <span class="input-group-text toggle-password" onclick="togglePassword()">
                                    <i class="icofont-eye-blocked toggle-icon" id="toggleIcon" ></i>
                                            </span>
                                            </div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                              <div class="input-group">
                            <input type="password" id="password2"
                                   wire:model.defer="password_confirmation" 
                                   class="form-control" 
                                   placeholder="Confirm new password"
                                   autocomplete="new-password"
                                   x-data
                                   x-on:change="$wire.set('password_confirmation', $event.target.value)">
                                     <span class="input-group-text toggle-password" onclick="togglePassword2()">
                                    <i class="icofont-eye-blocked toggle-icon" id="toggleIcon2" ></i>
                                            </span>
                                </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select wire:model.defer="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select wire:model.defer="role" class="form-select @error('role') is-invalid @enderror">
                                <option value="">Select Role</option>
                                @foreach($roles as $roleOption)
                                    <option value="{{ $roleOption->name }}">{{ ucfirst(str_replace('_', ' ', $roleOption->name)) }}</option>
                                @endforeach
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Employee Color *</label>
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar rounded-circle me-3" style="background-color: {{ $color }}; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    {{ $name ? strtoupper(substr($name, 0, 2)) : 'AB' }}
                                </div>
                                <input type="color" wire:model.defer="color" class="form-control form-control-color @error('color') is-invalid @enderror" style="width: 60px; height: 40px;">
                                <small class="text-muted ms-2">{{ $color }}</small>
                            </div>
                            <div class="color-palette mb-2">
                                <small class="text-muted d-block mb-2">Quick Colors:</small>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($defaultColors as $defaultColor)
                                    <button type="button" 
                                            class="btn p-0 border {{ $color === $defaultColor ? 'border-dark border-2' : 'border-light' }}" 
                                            style="width: 30px; height: 30px; background-color: {{ $defaultColor }}; border-radius: 50%;"
                                            wire:click="selectColor('{{ $defaultColor }}')"
                                            title="{{ $defaultColor }}">
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @error('color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showEditModal', false)">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="icofont-save me-1"></i>Update Employee</span>
                        <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
    <!-- Delete User Modal -->
    @if($showDeleteModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="icofont-ui-delete me-2"></i>Delete Employee
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showDeleteModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="avatar xl rounded-circle mb-3" style="background-color: {{ $selectedUser->color ?? '#6238B3' }}; color: white; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto;">
                            <i class="icofont-ui-delete fs-1"></i>
                        </div>
                        <h6>Are you sure you want to delete this employee?</h6>
                        <p class="mb-0"><strong>{{ $selectedUser->name ?? '' }}</strong></p>
                        <small class="text-muted">{{ $selectedUser->email ?? '' }}</small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="icofont-warning-alt me-2"></i>
                        <strong>Warning!</strong> This action cannot be undone. All data associated with this employee will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showDeleteModal', false)">
                        <i class="icofont-close-line me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="deleteUser" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="icofont-ui-delete me-1"></i>Delete Employee</span>
                        <span wire:loading><i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
     <script>
    function togglePassword() {
      const passwordInput = document.getElementById("password");
      const toggleIcon = document.getElementById("toggleIcon");

      const isPasswordVisible = passwordInput.type === "text";
      passwordInput.type = isPasswordVisible ? "password" : "text";

      toggleIcon.classList.toggle("icofont-eye");
      toggleIcon.classList.toggle("icofont-eye-blocked");
    }

     function togglePassword2() {
      const passwordInput = document.getElementById("password2");
      const toggleIcon = document.getElementById("toggleIcon2");

      const isPasswordVisible = passwordInput.type === "text";
      passwordInput.type = isPasswordVisible ? "password" : "text";

      toggleIcon.classList.toggle("icofont-eye");
      toggleIcon.classList.toggle("icofont-eye-blocked");
    }
  </script>
</div>