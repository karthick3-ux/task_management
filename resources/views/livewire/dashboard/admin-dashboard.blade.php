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
    <div class="row align-items-center mb-3">
        <div class="col-md-8">
            <h3 class="fw-bold mb-0">
                <i class="icofont-tasks me-2"></i>Admin Dashboard
            </h3>
        </div>
        <div class="col-md-4 text-end">
            @if(auth()->user()->hasPermissionTo('create_tasks') || auth()->user()->isSuperAdmin())
            <button type="button" class="btn btn-primary" wire:click="openCreateTaskModal">
                <i class="icofont-plus-circle me-2"></i>Add New Task
            </button>
            @endif
        </div>
    </div>

    <!-- Display Mode Toggle -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-group" role="group" aria-label="Display modes">
                <input type="radio" checked class="btn-check" name="task_view" wire:ignore value="split" id="split-view" autocomplete="off">
                <label class="btn btn-outline-primary" for="split-view">
                    <i class="icofont-layout me-1"></i>Split View
                </label>

                <input type="radio" class="btn-check" name="task_view" wire:ignore value="table-full" id="table-full" autocomplete="off">
                <label class="btn btn-outline-primary" for="table-full">
                    <i class="icofont-table me-1"></i>Full Table
                </label>

                <input type="radio" class="btn-check" name="task_view" wire:ignore value="calendar-full" id="calendar-full" autocomplete="off">
                <label class="btn btn-outline-primary" for="calendar-full">
                    <i class="icofont-calendar me-1"></i>Full Calendar
                </label>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <!-- <div class="row mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar lg rounded-1 bg-light-danger text-danger me-3">
                            <i class="icofont-warning-alt fs-4"></i>
                        </div>
                        <div class="flex-fill">
                            <span class="h6 fw-bold mb-0">{{ $overdueTasks }}</span>
                            <p class="text-muted mb-0 small">Overdue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Filters Section -->
    <div class="row mb-4 @if($displayMode=='split') d-block @elseif($displayMode=='table-full') d-block @else d-none @endif">
        <div class="card mb-3">
            <div class="card-header py-3 bg-transparent border-bottom-0">
                <h6 class="mb-0 fw-bold">
                    <i class="icofont-filter me-2"></i>Filters & Search
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Search Tasks</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="icofont-search-1"></i></span>
                            <input type="text" wire:model.live="search" class="form-control" placeholder="Search tasks or projects...">
                        </div>
                    </div>

                    <!-- Project Filter -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Project</label>
                        <select wire:model.live="projectFilter" class="form-select">
                            <option value="">All Projects</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>

                

                    <!-- Status Filter -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Status</label>
                        <select wire:model.live="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in progress">In Progress</option>
                            <option value="on hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="not completed">Not Completed</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Date Filter</label>
                        <select wire:model.live="dateFilter" class="form-select">
                            <option value="">All Dates</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Sort By</label>
                        <select wire:model.live="sortBy" class="form-select">
                            <option value="created_at">Created Date</option>
                            <option value="task_name">Task Name</option>
                            <option value="task_status">Status</option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    <div class="col-lg-1 col-md-6">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-outline-secondary w-100 d-block" wire:click="clearFilters">
                            <i class="icofont-refresh"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Based on Display Mode -->
    <div class="row">
        <!-- Tasks Table -->
        <div class="@if($displayMode=='split') col-xl-8 col-lg-7 @elseif($displayMode=='table-full') col-12 @else d-none @endif" id="task_table_part">
            @include('livewire.dashboard.partials.tasks-table')
        </div>

        <!-- Calendar -->
        <div class="@if($displayMode=='split') col-xl-4 col-lg-5 @elseif($displayMode=='calendar-full') col-12 @else d-none @endif" id="task_calendar_part">
            @include('livewire.dashboard.partials.tasks-calendar')
        </div>
    </div>

    <!-- Create Task Modal with Assignments -->
    @include('livewire.dashboard.partials.modals')

        @include('livewire.dashboard.partials.task-assignment-modal')

    <!-- Edit Task Modal -->
    @if($showEditModal && $editingTask)
    <div class="modal fade @if($showEditModal && $editingTask)show d-block @else d-none @endif" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="icofont-edit me-2"></i>Edit Task: {{ $editingTask->task_name }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showEditModal', false)"></button>
                </div>
                <form wire:submit.prevent="updateTask">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Task Name</label>
                                <input type="text" class="form-control @error('editForm.task_name') is-invalid @enderror" 
                                       wire:model="editForm.task_name" required>
                                @error('editForm.task_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Project</label>
                                <select class="form-select @error('editForm.project_id') is-invalid @enderror" 
                                        wire:model="editForm.project_id" required>
                                    <option value="">Select Project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                    @endforeach
                                </select>
                                @error('editForm.project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select @error('editForm.task_status') is-invalid @enderror" 
                                        wire:model="editForm.task_status" required>
                                    <option value="pending">Pending</option>
                                    <option value="in progress">In Progress</option>
                                    <option value="on hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="not completed">Not Completed</option>
                                </select>
                                @error('editForm.task_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Feedback</label>
                                <textarea class="form-control @error('editForm.feedback') is-invalid @enderror" 
                                          wire:model="editForm.feedback" 
                                          rows="4" 
                                          placeholder="Add your feedback, comments, or progress notes..."></textarea>
                                @error('editForm.feedback') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showEditModal', false)">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="icofont-save me-1"></i>Update Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Other modals -->


    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css">
    @endpush

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>
    
    <script>
   document.addEventListener('DOMContentLoaded', function() {
    initializeTasksCalendar();
});

function initializeTasksCalendar() {
    const calendarEl = document.getElementById('tasks-calendar');
    
    if (!calendarEl) return;

    // Destroy existing calendar if it exists
    if (window.tasksCalendar) {
        window.tasksCalendar.destroy();
    }

    const calendarEvents = @json($calendarEvents);

    window.tasksCalendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: 'local',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        height: 'auto',
        events: calendarEvents,
        eventDisplay: 'block',
        dayMaxEvents: 3,
        eventClick: function(info) {
            const event = info.event;
            const taskId = event.extendedProps.taskId;
            
            // Call the view task modal instead of user tasks modal
            @this.call('viewTask', taskId);
        },
        eventDidMount: function(info) {
            const event = info.event;
            const eventEl = info.el;
            const props = event.extendedProps;
            
            // Create tooltip text
            let tooltipText = `Task: ${props.taskName}\n`;
            tooltipText += `Project: ${props.projectName}\n`;
            tooltipText += `Status: ${props.taskStatus}\n`;
            tooltipText += `Assignments: ${props.assignmentCount}\n`;
            
            if (props.activeUsers && props.activeUsers.length > 0) {
                tooltipText += `Active Users: ${props.activeUsers.join(', ')}\n`;
            }
            
            if (props.hasOverdue) {
                tooltipText += `⚠️ Has Overdue Assignments`;
            }
            
            eventEl.setAttribute('title', tooltipText);
            eventEl.style.cursor = 'pointer';
            
            // Add user color indicators below task name
            if (props.activeUserColors && props.activeUserColors.length > 0) {
                // Create a container for user colors
                const colorContainer = document.createElement('div');
                colorContainer.style.cssText = `
                    display: flex;
                    gap: 1px;
                    margin-top: 2px;
                    flex-wrap: wrap;
                    justify-content: left;
                `;
                
                // Add color dots for each active user
                props.activeUserColors.forEach(color => {
                    const colorDot = document.createElement('div');
                    colorDot.style.cssText = `
                        width: 10px;
                        height: 10px;
                        border-radius: 10%;
                        background-color: ${color};
                        border: 1px solid rgba(255,255,255,0.8);
                        flex-shrink: 0;
                    `;
                    colorContainer.appendChild(colorDot);
                });
                
                // Find the event content and append color container
                const eventContent = eventEl.querySelector('.fc-event-title') || eventEl.querySelector('.fc-event-main');
                if (eventContent) {
                    eventContent.appendChild(colorContainer);
                }
            }
            
            // Add overdue styling
            if (props.hasOverdue) {
                eventEl.style.boxShadow = '0 0 8px rgba(220, 53, 69, 0.8)';
                eventEl.style.animation = 'pulse 2s infinite';
            }
        },
        
        // Custom event rendering to ensure proper layout
        eventContent: function(arg) {
            const props = arg.event.extendedProps;
            let html=''
            // Create custom HTML for the event
            if(props.total_task==0){
                 html='';
            }
            else{
            html = `
                <div style="padding: 2px;">
                    <div style="font-weight: bold; font-size: 11px; line-height: 1.2;">
                        ${arg.event.title}
                    </div>
                
                </div>
            `;
            }
            
            return { html: html };
        }
    });

    // Add CSS for pulse animation if not already present
    if (!document.getElementById('calendar-styles')) {
        const style = document.createElement('style');
        style.id = 'calendar-styles';
        style.textContent = `
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.7; }
                100% { opacity: 1; }
            }
            
            .fc-event {
                border-radius: 4px !important;
                font-size: 11px !important;
                background-color:#ffff !important;
                overflow-x:auto !important;
            }
            
            .fc-event-title {
                font-weight: bold !important;
            }
            
            .fc-daygrid-event {
                margin: 1px !important;
            }
        `;
        document.head.appendChild(style);
    }

    window.tasksCalendar.render();
}

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });

        $("#table-full").click(function(){
            @this.set('displayMode', 'table-full');
            window.tasksCalendar.render();
        })

        $("#calendar-full").click(function(){
            @this.set('displayMode', 'calendar-full');
            window.tasksCalendar.render();
        })

        $("#split-view").click(function(){
            @this.set('displayMode', 'split');
            window.tasksCalendar.render();
        })
    </script>

    @endpush

</div>