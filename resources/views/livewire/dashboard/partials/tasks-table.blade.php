<!-- Tasks Table -->
<div class="card">
    <div class="card-header py-3 d-flex justify-content-between bg-transparent border-bottom-0">
        <h6 class="mb-0 fw-bold">
            <i class="icofont-list me-2"></i>All Tasks ({{ $tasks->total() ?? 0 }} Total)
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive" style="max-height:800px">
            <table class="table table-hover align-middle mb-0" style="width:{{ $displayMode === 'table-full' ? '100%' : '100%' }}">
                <thead class="table-light">
                    <tr>
                        <th class="fw-bold">S.no</th>
                        <th class="fw-bold">Project</th>
                        <th class="fw-bold">Task Name</th>
                       
                        <th class="fw-bold">Assigned Users</th>
                   
                        <th class="fw-bold">Status</th>
                        <th class="fw-bold">Due date</th>
                        <th class="fw-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                    @php
                        $isOverdue = $task->is_overdue;
                        $earliestStart = $task->earliest_start_date;
                        $latestDeadline = $task->latest_deadline;
                        $overallProgress = $task->overall_progress;
                    @endphp
                    <tr class="{{ $isOverdue ? 'table-danger' : '' }}" wire:key="task-row-{{ $task->id }}">
                        <!-- Serial Number -->
                        <td>{{ ($tasks->firstItem()-1)+$loop->iteration }}</td>
                        
                        <!-- Project -->
                        <td>
                            <span class="fw-semibold">{{ $task->project->project_name }}</span>
                        </td>
                        
                        <!-- Task Name -->
                        <td>
                            <div class="d-flex align-items-start">
                                <div class="avatar sm rounded-1 no-thumbnail 
                                    @if($task->task_status === 'completed') bg-light-success text-success
                                    @elseif($task->task_status === 'in progress') bg-light-info text-info
                                    @elseif($task->task_status === 'on hold') bg-light-warning text-warning
                                    @else bg-light-secondary text-secondary
                                    @endif me-3">
                                    <i class="icofont-{{ $task->task_status === 'completed' ? 'check-circled' : ($task->task_status === 'in progress' ? 'spinner-alt-6' : ($task->task_status === 'on hold' ? 'pause' : 'clock-time')) }} fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $task->task_name }}</h6>
                                    @if($isOverdue)
                                        <span class="badge bg-danger ms-2">OVERDUE</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <!-- Assignment Count -->
                       
                        
                        <!-- Assigned Users -->
                        <td>
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                @php
                                    $uniqueUsers = $task->assignments->pluck('user')->unique('id');
                                @endphp
                                @foreach($uniqueUsers->take(2) as $user)
                                    <span class="badge position-relative user-badge" style="color: {{ $user->color }}; background-color: rgba({{ hexdec(substr($user->color, 1, 2)) }}, {{ hexdec(substr($user->color, 3, 2)) }}, {{ hexdec(substr($user->color, 5, 2)) }}, 0.1); border: 1px solid {{ $user->color }};" wire:key="assignment-{{ $task->id }}-user-{{ $user->id }}">
                                        {{ $user->name }}
                                        @php
                                            $userAssignmentCount = $task->assignments->where('user_id', $user->id)->count();
                                        @endphp
                                        @if($userAssignmentCount > 1)
                                            <small class="ms-1">({{ $userAssignmentCount }})</small>
                                        @endif
                                    </span>
                                @endforeach
                                @if($uniqueUsers->count() > 2)
                                    <span wire:key="assignment-extra-{{ $task->id }}-user-{{ $user->id }}" class="badge bg-secondary" title="View all assigned users" 
                                          style="cursor: pointer;" 
                                          wire:click="viewTask({{ $task->id }})">
                                        +{{ $uniqueUsers->count() - 2 }} more
                                    </span>
                                @endif
                            </div>
                        </td>
                        
                      
                        
                        <!-- Status -->
                        <td>
                            <span class="badge {{ $task->task_status === 'completed' ? 'bg-success' : ($task->task_status === 'in progress' ? 'bg-info' : ($task->task_status === 'on hold' ? 'bg-warning' : ($task->task_status === 'not completed' ? 'bg-danger' : 'bg-secondary'))) }}">
                                {{ ucwords($task->task_status) }}
                            </span>
                        </td>
                        
                        <!-- Review/Feedback -->
                        <td>
                          {{date('d-m-Y',strtotime($task->latest_date))}}
                        </td>
                        
                        <!-- Actions -->
                        <td>
                           
                                <div class="btn-group" role="group">
                                    @can('view_tasks')
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            wire:click="viewTask({{ $task->id }})" 
                                            title="View Task Details">
                                        <i class="icofont-eye"></i>
                                    </button>
                                    @endcan
                                    
                                    <!-- Assignment Management Button -->
                                    
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            wire:click="openAssignmentModal({{ $task->id }})" 
                                            title="Manage Assignments">
                                        <i class="icofont-users-alt-4"></i>
                                    </button>
                                   
                                    
                                    @can('edit_tasks')
                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                            wire:click="editTask({{ $task->id }})" 
                                            title="Edit Task">
                                        <i class="icofont-edit"></i>
                                    </button>
                                    @endcan
                                    
                                    @can('delete_tasks')
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                              x-on:click="if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) { $wire.deleteTask({{ $task->id }}) }"

                                            title="Delete Task">
                                        <i class="icofont-trash"></i>
                                    </button>
                                    @endcan
                                    
                                    @can('view_update_history')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                            wire:click="showUpdateHistory({{ $task->id }})" 
                                            title="View Update History">
                                        <i class="icofont-history"></i>
                                    </button>
                                    @endcan
                                </div>
                         
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5">
                            <div class="text-muted">
                                <div class="mb-3">
                                    <i class="icofont-tasks-alt display-1 text-muted"></i>
                                </div>
                                <h5 class="text-muted mb-3">No Tasks Found</h5>
                                @if($search || $projectFilter || $userFilter || $statusFilter || $urgencyFilter || $dateFilter)
                                    <p class="text-muted mb-3">Try adjusting your search criteria or filters</p>
                                    <button type="button" class="btn btn-outline-primary" wire:click="clearFilters">
                                        <i class="icofont-refresh me-1"></i>Clear All Filters
                                    </button>
                                @else
                                    <p class="text-muted mb-3">No tasks have been created yet</p>
                                    @if(auth()->user()->hasPermissionTo('create_tasks') || auth()->user()->isSuperAdmin())
                                    <button type="button" class="btn btn-primary" wire:click="openCreateTaskModal">
                                        <i class="icofont-plus-circle me-1"></i>Create Your First Task
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
        @if($tasks && method_exists($tasks, 'hasPages') && $tasks->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted small">
                Showing {{ $tasks->firstItem() ?? 0 }} to {{ $tasks->lastItem() ?? 0 }} of {{ $tasks->total() ?? 0 }} tasks
            </div>
            <div>
                {{ $tasks->links() }}
            </div>
        </div>
        @endif
    </div>
</div>