<!-- User Tasks Modal -->
@if($showUserTasksModal && $selectedUser)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="icofont-user me-2"></i>
                    <span style="color: white;">{{ $selectedUser->name }}</span>'s Assignments - {{ Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="$set('showUserTasksModal', false)"></button>
            </div>
            <div class="modal-body">
                @if($userTasks->count() > 0)
                    <div class="row">
                        @foreach($userTasks as $task)
                            @php
                                $userAssignments = $task->assignments->where('user_id', $selectedUser->id);
                            @endphp
                            <div class="col-12 mb-4">
                                <div class="card border-start border-4 border-{{ $task->task_status === 'completed' ? 'success' : ($task->task_status === 'in progress' ? 'info' : 'warning') }}">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-bold">{{ $task->task_name }}</h6>
                                            <span class="badge {{ $task->task_status === 'completed' ? 'bg-success' : ($task->task_status === 'in progress' ? 'bg-info' : 'bg-warning') }}">
                                                {{ ucwords($task->task_status) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="icofont-building me-1"></i>{{ $task->project->project_name }}
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Seq</th>
                                                        <th>Work Description</th>
                                                        <th>Start Date</th>
                                                        <th>Expected</th>
                                                        <th>Deadline</th>
                                                        <th>Days</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($userAssignments as $assignment)
                                                    <tr class="{{ $assignment->is_overdue ? 'table-danger' : '' }}">
                                                        <td>
                                                            <span class="badge bg-primary">{{ $assignment->sequence_number }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $assignment->work_description }}">
                                                                {{ $assignment->work_description }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <small>{{ $assignment->start_date->format('M d, Y') }}</small>
                                                        </td>
                                                        <td>
                                                            <small>{{ $assignment->expected_date->format('M d, Y') }}</small>
                                                        </td>
                                                        <td>
                                                            <small class="{{ $assignment->is_overdue ? 'text-danger fw-bold' : '' }}">
                                                                {{ $assignment->deadline->format('M d, Y') }}
                                                                @if($assignment->is_overdue)
                                                                    <br><span class="badge bg-danger">{{ $assignment->deadline?->diffForHumans() }}</span>
                                                                @endif
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info">{{ $assignment->calculated_days }} days</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $assignment->status === 'Completed' ? 'bg-success' : ($assignment->status === 'Inprogress' ? 'bg-info' : ($assignment->status === 'Reassigned' ? 'bg-secondary' : ($assignment->status === 'Not Completed' ? 'bg-danger' : 'bg-warning'))) }}">
                                                                {{ $assignment->status }}
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
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="icofont-tasks-alt fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No assignments found for this date</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="$set('showUserTasksModal', false)">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- View Task Modal -->
@if($showViewModal && $viewingTask)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold">
                    <i class="icofont-eye me-2"></i>View Task: {{ $viewingTask->task_name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="$set('showViewModal', false)"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Task Basic Information -->
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-light-info">
                                <h6 class="card-title mb-0 fw-bold">
                                    <i class="icofont-tasks-alt me-2"></i>Task Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <h6 class="text-muted">Task Name</h6>
                                        <p class="fw-bold">{{ $viewingTask->task_name }}</p>
                                    </div>
                                        <div class="col-md-4">
                                        <h6 class="text-muted">Status</h6>
                                        <span class="badge {{ $viewingTask->task_status === 'completed' ? 'bg-success' : ($viewingTask->task_status === 'in progress' ? 'bg-info' : ($viewingTask->task_status === 'on hold' ? 'bg-warning' : ($viewingTask->task_status === 'not completed' ? 'bg-danger' : 'bg-secondary'))) }}">
                                            {{ ucwords($viewingTask->task_status) }}
                                        </span>
                                    </div>
                               
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Project</h6>
                                        <p>{{ $viewingTask->project->project_name }}</p>
                                    </div>

                                  
                                    @if($viewingTask->feedback)
                                    <div class="col-12">
                                        <h6 class="text-muted">Feedback</h6>
                                        <p>{{ $viewingTask->feedback }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Task Assignments -->
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-header bg-light-success">
                                <h6 class="card-title mb-0 fw-bold">
                                    <i class="icofont-users me-2"></i>Task Assignments ({{ $viewingTask->assignments->count() }})
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Seq</th>
                                                <th>User</th>
                                                <th>Work Description</th>
                                                <th>Start Date</th>
                                                <th>Expected</th>
                                                <th>Deadline</th>
                                                <th>DOC</th>
                                                <th>Days</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($viewingTask->assignments as $assignment)
                                            <tr class="{{ $assignment->is_overdue ? 'table-danger' : '' }}">
                                                <td>
                                                    <span class="badge bg-primary">{{ $assignment->sequence_number }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar sm rounded-circle me-2" style="background-color: {{ $assignment->user->color }}; color: white;">
                                                            {{ substr($assignment->user->name, 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium">{{ $assignment->user->name }}</div>
                                                            @if($assignment->user->department)
                                                                <small class="text-muted">{{ $assignment->user->department->name }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-wrap" style="max-width: 250px;">
                                                        {{ $assignment->work_description }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>{{ $assignment->start_date?$assignment->start_date->format('M d, Y'):'--' }}</small>
                                                </td>
                                                <td>
                                                    <small>{{ $assignment->expected_date?$assignment->expected_date->format('M d, Y'):'--' }}</small>
                                                </td>
                                                <td>
                                                    <small class="{{ $assignment->is_overdue ? 'text-danger fw-bold' : '' }}">
                                                        {{ $assignment->deadline?$assignment->deadline->format('M d, Y'):'' }}
                                                        @if($assignment->is_overdue)
                                                            <br><span class="badge bg-danger">{{ $assignment->deadline?->diffForHumans() }}</span>
                                                        @endif
                                                    </small>
                                                </td>
                                                   <td>
                                                    <small>{{ $assignment->doc?$assignment->doc->format('M d, Y'):'--' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $assignment->no_of_days?$assignment->no_of_days.' days':'--' }} </span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $assignment->status === 'Completed' ? 'bg-success' : ($assignment->status === 'Inprogress' ? 'bg-info' : ($assignment->status === 'Reassigned' ? 'bg-secondary' : ($assignment->status === 'Not Completed' ? 'bg-danger' : 'bg-warning'))) }}">
                                                        {{ $assignment->status }}
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

                 
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="$set('showViewModal', false)">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Update History Modal -->
<!-- Enhanced Update History Modal -->
@if($showUpdateHistoryModal)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="icofont-history me-2"></i>Complete Update History
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="$set('showUpdateHistoryModal', false)"></button>
            </div>
            <div class="modal-body">
                @if($taskUpdateHistory->count() > 0)
                    <!-- Filter Tabs -->
                    <div class="mb-4">
                        <ul class="nav nav-pills" id="historyTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all-history" type="button" role="tab">
                                    <i class="icofont-list me-1"></i>All Changes ({{ $taskUpdateHistory->count() }})
                                </button>
                            </li>
                           
                        </ul>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content" id="historyTabContent">
                        <!-- All History Tab -->
                        <div class="tab-pane fade show active" id="all-history" role="tabpanel">
                            <div class="timeline">
                                @foreach($taskUpdateHistory as $history)
                                <div class="timeline-item border-start border-3 border-{{ $this->getHistoryBorderColor($history->type) }} ps-3 pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-{{ $this->getHistoryBadgeColor($history->type) }} me-2">
                                                    <i class="icofont-{{ $this->getHistoryIcon($history->type) }} me-1"></i>
                                                    {{ $this->getHistoryTypeLabel($history->type) }}
                                                </span>
                                                <span style="color: {{ $history->user->color }};" class="fw-bold">
                                                    {{ $history->user->name }}
                                                </span>
                                            </div>
                                              <h6 class="mb-1"><b>Task name: {{ $history->task->task_name }}</b></h6>
                                            <p class="mb-1">{{ $history->message }}</p>
                                            <small class="text-muted">
                                                <i class="icofont-clock-time me-1"></i>
                                                {{ $history->formatted_date }} ({{ $history->time_ago }})
                                            </small>
                                        </div>
                                        @if($this->isAssignmentHistory($history->type))
                                            <span class="badge bg-light text-dark border">Assignment</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                     
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="icofont-history fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No update history found</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="$set('showUpdateHistoryModal', false)">Close</button>
            </div>
        </div>
    </div>
</div>




@endif
@if($showCreateTaskModal)
<!-- Add Task Modal - Updated with Assignment Rows -->
<div class="modal fade show  d-block" tabindex="-1" id="create_task_model" wire:key="create-task-modal" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold">
                    <i class="icofont-plus-circle me-2"></i>Add New Task with Assignments
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="$set('showCreateTaskModal', false)"></button>
            </div>
            <form wire:submit.prevent="createTask">
                <div class="modal-body">
                    <div class="row g-4">

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

                        <!-- Task Basic Information -->
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-light-primary">
                                    <h6 class="card-title mb-0 fw-bold">
                                        <i class="icofont-tasks-alt me-2"></i>Task Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Task Name -->
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold">
                                                <i class="icofont-tasks-alt me-1"></i>Task Name *
                                            </label>
                                            <input type="text" 
                                                   wire:model.defer="taskName" 
                                                   class="form-control form-control-lg @error('taskName') is-invalid @enderror" 
                                                   placeholder="Enter a clear, specific task name"
                                                   autocomplete="off">
                                            @error('taskName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        
                                        <!-- Project Selection -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">
                                                <i class="icofont-tasks me-1"></i>Select Project *
                                            </label>
                                            <select wire:model.defer="selectedProjectId" class="form-select form-select-lg @error('selectedProjectId') is-invalid @enderror">
                                                <option value="">Choose a project...</option>
                                                @foreach($projects_all as $project)
                                                    <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('selectedProjectId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Task Assignments Section -->
<div class="col-12">
    <div class="card border-success">
        <div class="card-header bg-light-success d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0 fw-bold">
                <i class="icofont-users me-2"></i>Task Assignments
            </h6>
            <button type="button" class="btn btn-success btn-sm" wire:click="addTaskAssignment">
                <i class="icofont-plus-circle me-1"></i>Add Assignment
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">S.No</th>
                            <th width="15%">User *</th>
                            <th width="25%">Work Description *</th>
                            <th width="8%">No of Days</th>
                            <th width="12%">Start Date</th>
                            <th width="12%">Expected Date</th>
                            <th width="12%">Deadline</th>
                            <th width="8%">Status *</th>
                            <th width="8%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($taskAssignments as $index => $assignment)
                        <tr class="border-bottom">
                            <td class="text-center align-middle">
                                <span class="badge bg-primary fs-6">{{ $index + 1 }}</span>
                            </td>
                            
                            <!-- User Selection -->
                            <td>
                                @can('assign_task_users')
                                <select wire:model.defer="taskAssignments.{{ $index }}.user_id" 
                                        class="form-select form-select-sm @error('taskAssignments.'.$index.'.user_id') is-invalid @enderror">
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}
                                            @if($user->department)
                                                ({{ $user->department->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @else
                                <div class="text-center">
                                    <div class="avatar sm rounded-circle bg-info text-white mb-1">
                                        <i class="icofont-user"></i>
                                    </div>
                                    <small class="d-block fw-bold">{{ auth()->user()->name }}</small>
                                    <small class="text-muted">Self-assigned</small>
                                </div>
                                @endcan
                                @error('taskAssignments.'.$index.'.user_id') 
                                    <div class="invalid-feedback d-block">{{ $message }}</div> 
                                @enderror
                            </td>
                            
                            <!-- Work Description -->
                            <td>
                                <textarea wire:model.defer="taskAssignments.{{ $index }}.work_description"
                                          class="form-control form-control-sm @error('taskAssignments.'.$index.'.work_description') is-invalid @enderror"
                                          rows="2"
                                          placeholder="Describe the work to be done..."></textarea>
                                @error('taskAssignments.'.$index.'.work_description') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </td>
                            
                            <!-- No of Days -->
                            <td>
                                <input type="number" 
                                       wire:model="taskAssignments.{{ $index }}.no_of_days"
                                       wire:change="calculateDatesFromDays({{ $index }})"
                                       class="form-control form-control-sm @error('taskAssignments.'.$index.'.no_of_days') is-invalid @enderror"
                                       min="0"
                                       placeholder="Days">
                                @error('taskAssignments.'.$index.'.no_of_days') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </td>
                            
                            <!-- Start Date -->
                            <td>
                                @if($index === 0)
                                    <!-- First row: Manual input -->
                                    <input type="date" 
                                           wire:model="taskAssignments.{{ $index }}.start_date"
                                           class="form-control form-control-sm @error('taskAssignments.'.$index.'.start_date') is-invalid @enderror"
                                          >
                                @else
                                    <!-- Auto-calculated for subsequent rows -->
                                    <input type="date" 
                                           wire:model="taskAssignments.{{ $index }}.start_date"
                                           class="form-control form-control-sm"
                                          
                                           style="background-color: #f8f9fa;">
                                @endif
                                @error('taskAssignments.'.$index.'.start_date') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </td>
                            
                            <!-- Expected Date -->
                            <td>
                                @if($index === 0)
                                    <!-- First row: Manual input -->
                                    <input type="date" 
                                           wire:model="taskAssignments.{{ $index }}.expected_date"
                                          
                                           class="form-control form-control-sm @error('taskAssignments.'.$index.'.expected_date') is-invalid @enderror"
                                           >
                                @else
                                    <!-- Auto-copied from first row -->
                                    <input type="date" 
                                           wire:model="taskAssignments.{{ $index }}.expected_date"
                                           class="form-control form-control-sm"
                                          
                                           style="background-color: #f8f9fa;">
                                @endif
                                @error('taskAssignments.'.$index.'.expected_date') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </td>
                            
                            <!-- Deadline -->
                            <td>
                                @if($index === 0)
                                    <!-- First row: Manual input -->
                                    <input type="date" 
                                           wire:model="taskAssignments.{{ $index }}.deadline"
                                           class="form-control form-control-sm @error('taskAssignments.'.$index.'.deadline') is-invalid @enderror"
                                           min="{{ isset($assignment['expected_date']) ? $assignment['expected_date'] : date('Y-m-d', strtotime('+1 day')) }}">
                                @else
                                    <!-- Auto-copied from first row -->
                                    <input type="date" 
                                           wire:model="taskAssignments.{{ $index }}.deadline"
                                           class="form-control form-control-sm"
                                          
                                           style="background-color: #f8f9fa;">
                                @endif
                                @error('taskAssignments.'.$index.'.deadline') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </td>
                            
                            <!-- Status -->
                            <td>
                                <select wire:model.defer="taskAssignments.{{ $index }}.status"
                                        class="form-select form-select-sm @error('taskAssignments.'.$index.'.status') is-invalid @enderror">
                                    <option value="Pending">Pending</option>
                                    <option value="Inprogress">In Progress</option>
                                    <option value="Reassigned">Reassigned</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Not Completed">Not Completed</option>
                                </select>
                                @error('taskAssignments.'.$index.'.status') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </td>
                            
                            <!-- Actions -->
                            <td>
                                <div class="btn-group-vertical" role="group">
                                    <button type="button" 
                                            class="btn btn-outline-success btn-sm mb-1" 
                                            wire:click="addTaskAssignment({{ $index }})"
                                            title="Add row after this">
                                        <i class="icofont-plus-circle"></i>
                                    </button>
                                    
                                    @if(count($taskAssignments) > 1)
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm mb-1" 
                                            wire:click="removeTaskAssignment({{ $index }})"
                                            title="Remove this row">
                                        <i class="icofont-ui-delete"></i>
                                    </button>
                                    @endif
                                    
                                    @if($index > 0)
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-sm mb-1" 
                                            wire:click="moveAssignmentUp({{ $index }})"
                                            title="Move up">
                                        <i class="icofont-arrow-up"></i>
                                    </button>
                                    @endif
                                    
                                    @if($index < count($taskAssignments) - 1)
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-sm" 
                                            wire:click="moveAssignmentDown({{ $index }})"
                                            title="Move down">
                                        <i class="icofont-arrow-down"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if(empty($taskAssignments))
            <div class="text-center py-4">
                <i class="icofont-users display-4 text-muted mb-3"></i>
                <h6 class="text-muted">No assignments added yet</h6>
                <button type="button" class="btn btn-primary" wire:click="addTaskAssignment">
                    <i class="icofont-plus-circle me-1"></i>Add First Assignment
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
                        
                        <!-- Assignment Summary -->
                     
                        
                        @error('taskAssignments')
                        <div class="col-12">
                            <div class="alert alert-danger">
                                <i class="icofont-warning-alt me-2"></i>{{ $message }}
                            </div>
                        </div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-lg" wire:click="$set('showCreateTaskModal', false)">
                        <i class="icofont-close-line me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-info btn-lg" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="icofont-plus-circle me-1"></i>Create Task with {{ count($taskAssignments) }} Assignment(s)
                        </span>
                        <span wire:loading>
                            <i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Creating Task...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif

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