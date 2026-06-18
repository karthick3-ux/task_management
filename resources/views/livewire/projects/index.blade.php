
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
                <h3 class="fw-bold mb-0">
                    <i class="icofont-tasks me-2"></i>Project Management
                </h3>
                <div class="col-auto d-flex w-sm-100 gap-2">
                    @if(auth()->user()->hasPermissionTo('create_tasks') || auth()->user()->isSuperAdmin())
                    <!-- <button type="button" class="btn btn-primary btn-set-task" wire:click="openCreateTaskModal">
                        <i class="icofont-plus-circle me-2 fs-6"></i>Add Task
                    </button> -->
                    @endif
                    @if(auth()->user()->hasPermissionTo('create_projects') || auth()->user()->isSuperAdmin())
                    <button type="button" class="btn btn-dark btn-set-task" wire:click="openCreateProjectModal">
                        <i class="icofont-plus-circle me-2 fs-6"></i>Create Project
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar lg rounded-1 bg-light-success text-success me-3">
                            <i class="icofont-tasks fs-4"></i>
                        </div>
                        <div class="flex-fill">
                            <span class="h6 fw-bold mb-0">{{ $activeProjectsCount }}</span>
                            <p class="text-muted mb-0 small">Active Projects</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar lg rounded-1 bg-light-danger text-danger me-3">
                            <i class="icofont-pause fs-4"></i>
                        </div>
                        <div class="flex-fill">
                            <span class="h6 fw-bold mb-0">{{ $inactiveProjectsCount }}</span>
                            <p class="text-muted mb-0 small">Inactive Projects</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar lg rounded-1 bg-light-info text-info me-3">
                            <i class="icofont-chart-bar-graph fs-4"></i>
                        </div>
                        <div class="flex-fill">
                            <span class="h6 fw-bold mb-0">{{ $projects_all->count() }}</span>
                            <p class="text-muted mb-0 small">Total Projects</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar lg rounded-1 bg-light-warning text-warning me-3">
                            <i class="icofont-clock-time fs-4"></i>
                        </div>
                        <div class="flex-fill">
                            @php
                                $recentProjects = $projects->filter(function($project) {
                                    return $project->created_at->isAfter(now()->subDays(7));
                                })->count();
                            @endphp
                            <span class="h6 fw-bold mb-0">{{ $recentProjects }}</span>
                            <p class="text-muted mb-0 small">This Week</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="row align-item-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="icofont-search-1 me-2"></i>Search & Filter Projects
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Search Projects</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icofont-search-1"></i></span>
                                <input type="text" wire:model="search" class="form-control" placeholder="Search by project name or description...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label">Project Status</label>
                            <select wire:model="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="active">Active Projects</option>
                                <option value="inactive">Inactive Projects</option>
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
                                <i class="icofont-close-line"></i> Clear All Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="row align-item-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="icofont-list me-2"></i>Projects List ({{ $projects_all->count() }} Total)
                    </h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success fs-6">
                            <i class="icofont-check-circled me-1"></i>{{ $activeProjectsCount }} Active
                        </span>
                        <span class="badge bg-danger fs-6">
                            <i class="icofont-pause me-1"></i>{{ $inactiveProjectsCount }} Inactive
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold">Project Details</th>
                                    <th class="fw-bold text-center">Status</th>
                                    <th class="fw-bold text-center">Task Summary</th>
                                    <th class="fw-bold text-center">Overall Progress</th>
                                    <th class="fw-bold text-center">Timeline</th>
                                    <th class="fw-bold text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                <tr class="border-bottom">
                                    <td>
                                        <div class="d-flex align-items-start">
                                            <div class="avatar lg rounded-2 no-thumbnail 
                                                @if($project->status === 'active') bg-light-success text-success
                                                @else bg-light-danger text-danger
                                                @endif me-3">
                                                <i class="icofont-tasks fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="fw-bold mb-1">{{ $project->project_name }}</h6>
                                                @if($project->project_description)
                                                    <p class="text-muted mb-1 small" title="{{ $project->project_description }}">
                                                        <i class="icofont-file-text me-1"></i>
                                                        {{ Str::limit($project->project_description, 80) }}
                                                    </p>
                                        
                                                @endif
                                            
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $project->status === 'active' ? 'bg-success' : 'bg-danger' }} fs-6 px-3 py-2">
                                            <i class="icofont-{{ $project->status === 'active' ? 'check-circled' : 'pause' }} me-1"></i>
                                            {{ ucfirst($project->status) }}
                                        </span>
                                       
                                     
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1 flex-wrap mb-2">
                                            <span class="badge bg-secondary" title="Total Tasks">
                                                <i class="icofont-tasks-alt me-1"></i>{{ $project->tasks_count }}
                                            </span>
                                            <span class="badge bg-warning" title="Pending Tasks">
                                                <i class="icofont-clock-time me-1"></i>{{ $project->pending_tasks_count }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                                            <span class="badge bg-info" title="In Progress">
                                                <i class="icofont-spinner-alt-6 me-1"></i>{{ $project->progress_tasks_count }}
                                            </span>
                                            <span class="badge bg-success" title="Completed">
                                                <i class="icofont-check-circled me-1"></i>{{ $project->completed_tasks_count }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $progress = $project->tasks_count > 0 ? round(($project->completed_tasks_count / $project->tasks_count) * 100, 1) : 0;
                                            $progressClass = $progress == 100 ? 'bg-success' : ($progress > 50 ? 'bg-info' : ($progress > 0 ? 'bg-warning' : 'bg-secondary'));
                                        @endphp
                                        <div class="progress mb-2" style="height: 25px;">
                                            <div class="progress-bar {{ $progressClass }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $progress }}%"
                                                 title="{{ $progress }}% Complete">
                                                <span class="fw-bold">{{ $progress }}%</span>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            @if($progress == 100)
                                                <i class="icofont-check-circled text-success me-1"></i>Complete
                                            @elseif($progress > 0)
                                                <i class="icofont-spinner-alt-6 text-info me-1"></i>In Progress
                                            @else
                                                <i class="icofont-clock-time text-warning me-1"></i>Not Started
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="mb-1">
                                            <small class="text-muted d-block">
                                                <i class="icofont-calendar me-1"></i>
                                                <strong>Created:</strong><br>{{ $project->created_at->format('M d, Y') }}
                                            </small>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">
                                                <i class="icofont-clock-time me-1"></i>
                                                <strong>Updated:</strong><br>{{ $project->updated_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group-vertical" role="group">
                                            <div class="btn-group mb-1" role="group">
                                         
                                                
                                                @if(auth()->user()->hasPermissionTo('edit_projects') || auth()->user()->isSuperAdmin())
                                                <button type="button" class="btn btn-outline-success btn-sm" wire:click="openEditProjectModal({{ $project->id }})" title="Edit Project">
                                                    <i class="icofont-edit"></i>
                                                </button>
                                                @endif
                                                 @if(auth()->user()->hasPermissionTo('delete_projects') || auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-danger btn-sm" wire:click="openDeleteProjectModal({{ $project->id }})" title="Delete Project">
                                                <i class="icofont-ui-delete"></i>
                                            </button>
                                            @endif
                                                
                                             
                                            </div>
                                            
                                           
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <div class="mb-3">
                                                <i class="icofont-tasks display-1 text-muted"></i>
                                            </div>
                                            <h5 class="text-muted">No Projects Found</h5>
                                            @if($search || $statusFilter !== '')
                                                <button type="button" class="btn btn-outline-primary" wire:click="clearFilters">
                                                    <i class="icofont-refresh me-1"></i>Clear Filters & Show All
                                                </button>
                                            @else
                                                @if(auth()->user()->hasPermissionTo('create_projects') || auth()->user()->isSuperAdmin())
                                                <button type="button" class="btn btn-primary" wire:click="openCreateProjectModal">
                                                    <i class="icofont-plus-circle me-1"></i>Create Your First Project
                                                </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                     @if($projects && method_exists($projects, 'hasPages') && $projects->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Showing {{ $projects->firstItem() }} to {{ $projects->lastItem() }} of {{ $projects->total() }} projects
                        </div>
                        <div>
                            {{ $projects->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Create Project Modal -->
    @if($showCreateProjectModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-plus-circle me-2"></i>Create New Project
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showCreateProjectModal', false)"></button>
                </div>
                <form wire:submit.prevent="createProject">
                    <div class="modal-body">
                        <div class="row g-4">
                           
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="icofont-tasks me-1"></i>Project Id/No*
                                </label>
                                <input type="text" 
                                       wire:model.defer="projectName" 
                                       class="form-control form-control-lg @error('projectName') is-invalid @enderror" 
                                       placeholder=""
                                       autocomplete="off">
                                @error('projectName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="icofont-toggle-on me-1"></i>Status *
                                </label>
                                <select wire:model.defer="projectStatus" class="form-select form-select-lg @error('projectStatus') is-invalid @enderror">
                                    <option value="active">🟢 Active</option>
                                    <option value="inactive">🔴 Inactive</option>
                                </select>
                                @error('projectStatus') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="icofont-file-text me-1"></i>Project Description
                                </label>
                                <textarea wire:model.defer="projectDescription" 
                                          class="form-control @error('projectDescription') is-invalid @enderror" 
                                          rows="5" 
                                          placeholder=""></textarea>
                                @error('projectDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Provide a detailed description to help your team understand the project</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showCreateProjectModal', false)">
                            <i class="icofont-close-line me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg" wire:loading.attr="disabled">
                            <span wire:loading.remove">
                                <i class="icofont-plus-circle me-1"></i>Create Project
                            </span>
                            <span wire:loading>
                                <i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Creating Project...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Project Modal -->
    @if($showEditProjectModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-edit me-2"></i>Edit Project
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showEditProjectModal', false)"></button>
                </div>
                <form wire:submit.prevent="updateProject">
                    <div class="modal-body">
                        <div class="row g-4">
                         
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="icofont-tasks me-1"></i>Project Id/No *
                                </label>
                                <input type="text" 
                                       wire:model.defer="projectName" 
                                       class="form-control form-control-lg @error('projectName') is-invalid @enderror" 
                                       placeholder="Enter project name"
                                       autocomplete="off">
                                @error('projectName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="icofont-toggle-on me-1"></i>Project Status *
                                </label>
                                <select wire:model.defer="projectStatus" class="form-select form-select-lg @error('projectStatus') is-invalid @enderror">
                                    <option value="active">🟢 Active</option>
                                    <option value="inactive">🔴 Inactive</option>
                                </select>
                                @error('projectStatus') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="icofont-file-text me-1"></i>Project Description
                                </label>
                                <textarea wire:model.defer="projectDescription" 
                                          class="form-control @error('projectDescription') is-invalid @enderror" 
                                          rows="5" 
                                          placeholder=""></textarea>
                                @error('projectDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showEditProjectModal', false)">
                            <i class="icofont-close-line me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" wire:loading.attr="disabled">
                            <span wire:loading.remove">
                                <i class="icofont-save me-1"></i>Update Project
                            </span>
                            <span wire:loading>
                                <i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Add Task Modal -->

    <!-- Add Task Modal -->
<!-- Add Task Modal -->



<style>
.bg-light-primary { background-color: rgba(13, 110, 253, 0.1) !important; }
.bg-light-success { background-color: rgba(25, 135, 84, 0.1) !important; }
.bg-light-info { background-color: rgba(13, 202, 240, 0.1) !important; }

.table td, .table th {
    vertical-align: middle;
    padding: 0.5rem;
}

.btn-group-vertical .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.form-control-sm, .form-select-sm {
    font-size: 0.875rem;
}

.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}
</style>

<!-- Select2 Script for Task Modal -->



  

    <!-- View Project Modal -->
    @if($showViewProjectModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-eye me-2"></i>Project Overview
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showViewProjectModal', false)"></button>
                </div>
                <div class="modal-body">
                    @if($selectedProject)
                    <div class="row g-4">
                        <!-- Project Header -->
                        <div class="col-12">
                            <div class="text-center mb-4">
                                <div class="avatar display-4 rounded-circle {{ $selectedProject->status === 'active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} mb-3 mx-auto" style="width: 100px; height: 100px;">
                                    <i class="icofont-tasks"></i>
                                </div>
                                <h3 class="fw-bold mb-2">{{ $selectedProject->project_name }}</h3>
                                <div class="d-flex gap-2 justify-content-center align-items-center">
                                    <span class="badge {{ $selectedProject->status === 'active' ? 'bg-success' : 'bg-danger' }} fs-5 px-3 py-2">
                                        <i class="icofont-{{ $selectedProject->status === 'active' ? 'check-circled' : 'pause' }} me-1"></i>
                                        {{ ucfirst($selectedProject->status) }}
                                    </span>
                                    @php
                                        $overallProgress = $selectedProject->tasks_count > 0 ? round(($selectedProject->completed_tasks_count / $selectedProject->tasks_count) * 100, 1) : 0;
                                    @endphp
                                    <span class="badge bg-primary fs-5 px-3 py-2">
                                        <i class="icofont-chart-bar-graph me-1"></i>{{ $overallProgress }}% Complete
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Project Statistics Cards -->
                        <div class="col-12">
                            <div class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-light-secondary text-center">
                                        <div class="card-body">
                                            <div class="avatar lg rounded-circle bg-secondary text-white mx-auto mb-2">
                                                <i class="icofont-tasks-alt fs-4"></i>
                                            </div>
                                            <h4 class="fw-bold mb-1">{{ $selectedProject->tasks_count }}</h4>
                                            <p class="text-muted mb-0 small">Total Tasks</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-light-warning text-center">
                                        <div class="card-body">
                                            <div class="avatar lg rounded-circle bg-warning text-white mx-auto mb-2">
                                                <i class="icofont-clock-time fs-4"></i>
                                            </div>
                                            <h4 class="fw-bold mb-1">{{ $selectedProject->pending_tasks_count }}</h4>
                                            <p class="text-muted mb-0 small">Pending Tasks</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-light-info text-center">
                                        <div class="card-body">
                                            <div class="avatar lg rounded-circle bg-info text-white mx-auto mb-2">
                                                <i class="icofont-spinner-alt-6 fs-4"></i>
                                            </div>
                                            <h4 class="fw-bold mb-1">{{ $selectedProject->progress_tasks_count }}</h4>
                                            <p class="text-muted mb-0 small">In Progress</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-light-success text-center">
                                        <div class="card-body">
                                            <div class="avatar lg rounded-circle bg-success text-white mx-auto mb-2">
                                                <i class="icofont-check-circled fs-4"></i>
                                            </div>
                                            <h4 class="fw-bold mb-1">{{ $selectedProject->completed_tasks_count }}</h4>
                                            <p class="text-muted mb-0 small">Completed</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Project Information -->
                        <div class="col-md-8">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0 fw-bold">
                                        <i class="icofont-info-circle me-2"></i>Project Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 text-muted">Project Name:</dt>
                                        <dd class="col-sm-8 fw-bold">{{ $selectedProject->project_name }}</dd>
                                        
                                        <dt class="col-sm-4 text-muted">Status:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge {{ $selectedProject->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                                <i class="icofont-{{ $selectedProject->status === 'active' ? 'check-circled' : 'pause' }} me-1"></i>
                                                {{ ucfirst($selectedProject->status) }}
                                            </span>
                                        </dd>
                                        
                                        <dt class="col-sm-4 text-muted">Overall Progress:</dt>
                                        <dd class="col-sm-8">
                                            <div class="progress mb-1" style="height: 20px;">
                                                <div class="progress-bar {{ $overallProgress == 100 ? 'bg-success' : ($overallProgress > 50 ? 'bg-info' : 'bg-warning') }}" 
                                                     role="progressbar" style="width: {{ $overallProgress }}%">
                                                    {{ $overallProgress }}%
                                                </div>
                                            </div>
                                        </dd>
                                        
                                        <dt class="col-sm-4 text-muted">Created Date:</dt>
                                        <dd class="col-sm-8">{{ $selectedProject->created_at->format('F d, Y \a\t g:i A') }}</dd>
                                        
                                        <dt class="col-sm-4 text-muted">Last Updated:</dt>
                                        <dd class="col-sm-8">{{ $selectedProject->updated_at->format('F d, Y \a\t g:i A') }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Project Timeline -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0 fw-bold">
                                        <i class="icofont-calendar me-2"></i>Project Timeline
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-primary"></div>
                                            <div class="timeline-content">
                                                <h6 class="fw-bold mb-1">Project Created</h6>
                                                <p class="text-muted mb-0 small">{{ $selectedProject->created_at->format('M d, Y') }}</p>
                                                <p class="text-muted mb-0 small">{{ $selectedProject->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                        
                                        @if($selectedProject->tasks_count > 0)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-info"></div>
                                            <div class="timeline-content">
                                                <h6 class="fw-bold mb-1">Tasks Added</h6>
                                                <p class="text-muted mb-0 small">{{ $selectedProject->tasks_count }} total tasks</p>
                                            </div>
                                        </div>
                                        @endif
                                        
                                        @if($selectedProject->completed_tasks_count > 0)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <h6 class="fw-bold mb-1">Progress Made</h6>
                                                <p class="text-muted mb-0 small">{{ $selectedProject->completed_tasks_count }} tasks completed</p>
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-warning"></div>
                                            <div class="timeline-content">
                                                <h6 class="fw-bold mb-1">Last Update</h6>
                                                <p class="text-muted mb-0 small">{{ $selectedProject->updated_at->format('M d, Y') }}</p>
                                                <p class="text-muted mb-0 small">{{ $selectedProject->updated_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Project Description -->
                        @if($selectedProject->project_description)
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0 fw-bold">
                                        <i class="icofont-file-text me-2"></i>Project Description
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $selectedProject->project_description }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Recent Tasks -->
                        @if($selectedProject->tasks && $selectedProject->tasks->count() > 0)
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold">
                                        <i class="icofont-tasks-alt me-2"></i>Recent Tasks
                                    </h6>
                                    <span class="badge bg-primary">{{ $selectedProject->tasks->count() }} total</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Task Name</th>
                                                    <th>Assigned To</th>
                                                    <th>Status</th>
                                                 
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($selectedProject->tasks->take(10) as $task)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar sm rounded-1 {{ $task->task_status === 'Completed' ? 'bg-light-success text-success' : ($task->task_status === 'Progress' ? 'bg-light-info text-info' : 'bg-light-warning text-warning') }} me-2">
                                                                <i class="icofont-{{ $task->task_status === 'Completed' ? 'check-circled' : ($task->task_status === 'Progress' ? 'spinner-alt-6' : 'clock-time') }}"></i>
                                                            </div>
                                                            <span class="fw-bold">{{ Str::limit($task->task_name, 30) }}</span>
                                                        </div>
                                                    </td>
                                                 
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($task->users->take(2) as $user)
                                                                <span class="badge bg-info small">{{ $user->name }}</span>
                                                            @endforeach
                                                            @if($task->users->count() > 2)
                                                                <span class="badge bg-secondary small">+{{ $task->users->count() - 2 }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $task->task_status === 'Completed' ? 'bg-success' : ($task->task_status === 'Progress' ? 'bg-info' : 'bg-warning') }}">
                                                            {{ $task->task_status }}
                                                        </span>
                                                    </td>
                                                  
                                                  
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="icofont-tasks-alt display-1 text-muted mb-3"></i>
                                    <h5 class="text-muted">No Tasks Yet</h5>
                                    <p class="text-muted mb-3">This project doesn't have any tasks assigned yet.</p>
                                    @if(auth()->user()->hasPermissionTo('create_tasks') || auth()->user()->isSuperAdmin())
                                    <button type="button" class="btn btn-primary" wire:click="openCreateTaskModal">
                                        <i class="icofont-plus-circle me-1"></i>Add First Task
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-lg" wire:click="$set('showViewProjectModal', false)">
                        <i class="icofont-close-line me-1"></i>Close
                    </button>
                    @if(auth()->user()->hasPermissionTo('edit_projects') || auth()->user()->isSuperAdmin())
                    <button type="button" class="btn btn-success btn-lg" wire:click="openEditProjectModal({{ $selectedProject->id ?? 0 }})">
                        <i class="icofont-edit me-1"></i>Edit Project
                    </button>
                    @endif
                    @if(auth()->user()->hasPermissionTo('create_tasks') || auth()->user()->isSuperAdmin())
                    <!-- <button type="button" class="btn btn-primary btn-lg" wire:click="openCreateTaskModal">
                        <i class="icofont-plus-circle me-1"></i>Add Task
                    </button> -->
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Project Modal -->
    @if($showDeleteProjectModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-ui-delete me-2"></i>Delete Project - Confirmation Required
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showDeleteProjectModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar display-1 rounded-circle bg-light-danger text-danger mb-3 mx-auto" style="width: 100px; height: 100px;">
                            <i class="icofont-ui-delete"></i>
                        </div>
                        <h4 class="fw-bold text-danger mb-2">Are you absolutely sure?</h4>
                        <p class="text-muted mb-3">This action cannot be undone. This will permanently delete the project and all associated data.</p>
                    </div>
                    
                    @if($selectedProject)
                    <div class="card border-danger mb-4">
                        <div class="card-header bg-light-danger">
                            <h6 class="card-title mb-0 fw-bold text-danger">
                                <i class="icofont-warning-alt me-2"></i>Project to be deleted:
                            </h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Project Name:</dt>
                                <dd class="col-sm-8 fw-bold">{{ $selectedProject->project_name }}</dd>
                                
                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge {{ $selectedProject->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ucfirst($selectedProject->status) }}
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-4">Total Tasks:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-info">{{ $selectedProject->tasks_count ?? 0 }} tasks</span>
                                </dd>
                                
                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $selectedProject->created_at->format('F d, Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                    
                    @if(($selectedProject->tasks_count ?? 0) > 0)
                    <div class="alert alert-danger">
                        <div class="d-flex">
                            <div class="avatar lg rounded-circle bg-danger text-white me-3 flex-shrink-0">
                                <i class="icofont-warning-alt"></i>
                            </div>
                            <div>
                                <h6 class="alert-heading fw-bold">Cannot Delete Project!</h6>
                                <p class="mb-2">This project has <strong>{{ $selectedProject->tasks_count }} active tasks</strong> assigned to it.</p>
                                <p class="mb-0">Please complete, reassign, or delete all tasks before attempting to delete this project.</p>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <div class="avatar lg rounded-circle bg-warning text-white me-3 flex-shrink-0">
                                <i class="icofont-warning-alt"></i>
                            </div>
                            <div>
                                <h6 class="alert-heading fw-bold">Final Warning!</h6>
                                <p class="mb-2">Deleting this project will permanently remove:</p>
                                <ul class="mb-2">
                                    <li>Project information and description</li>
                                    <li>All project history and timeline</li>
                                    <li>Any associated metadata</li>
                                </ul>
                                <p class="mb-0 fw-bold">This action is irreversible!</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check p-3 bg-light rounded">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label fw-bold" for="confirmDelete">
                            I understand that this action is permanent and cannot be undone.
                        </label>
                    </div>
                    @endif
                    @endif
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-lg" wire:click="$set('showDeleteProjectModal', false)">
                        <i class="icofont-close-line me-1"></i>Cancel - Keep Project
                    </button>
                    @if(($selectedProject->tasks_count ?? 0) == 0)
                <button type="button" 
            class="btn btn-danger btn-lg" 
           
            wire:loading.attr="disabled"
            @click.prevent="if (!document.getElementById('confirmDelete').checked) { alert('Please confirm you understand this action is permanent'); return; } $wire.deleteProject()">
        <span wire:loading.remove>
            <i class="icofont-ui-delete me-1"></i>Yes, Delete Project Permanently
        </span>
        <span wire:loading>
            <i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Deleting Project...
        </span>
    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif



    <!-- Custom CSS and JavaScript -->
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -23px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #dee2e6;
        }
        
        .progress-bar {
            transition: width 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .icofont-spin {
            animation: spin 1s linear infinite;
        }
    </style>

    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 8000);
            });
        });

        // Progress bar animation
        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(function(bar) {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(function() {
                    bar.style.width = width;
                }, 100);
            });
        }

        document.addEventListener('DOMContentLoaded', animateProgressBars);
    </script>

    <script>

       


// Handle modal close events


</script>

</div>
           

@push('styles')
<style>
    .modal-body {
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .table td, .table th {
        vertical-align: middle;
        padding: 0.5rem;
    }
    
    .form-control-sm, .form-select-sm {
        font-size: 0.875rem;
    }
    
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .card .table {
        margin-bottom: 0;
    }
    
    .table thead th {
        border-top: none;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .badge {
        font-size: 0.75rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .icofont-spin {
        animation: spin 1s linear infinite;
    }
</style>
@endpush
                                
                           