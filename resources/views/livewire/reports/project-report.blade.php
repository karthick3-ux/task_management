<div>
    <!-- Page Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h3 class="fw-bold mb-0">
                <i class="icofont-chart-bar-graph me-2"></i>Project Reports
            </h3>
           
        </div>
      
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="icofont-filter me-2"></i>Report Filters
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select wire:model.live="reportType" class="form-select">
                        <option value="analytics">Analytics Report</option>
                        <option value="summary">Summary Report</option>
                        <option value="detailed">Detailed Report</option>
                        
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Project</label>
                    <select wire:model.live="projectFilter" class="form-select">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" wire:model.live="dateTo" class="form-control">
                </div>
                <div class="col-md-2">
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
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                        <i class="icofont-refresh me-1"></i>Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    @if($reportData['type'] === 'summary')
        <!-- Summary Report -->
        @if(isset($reportData['totals']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Overall Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <h4 class="text-primary">{{ $reportData['totals']['total_projects'] }}</h4>
                                <small class="text-muted">Total Projects</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-info">{{ $reportData['totals']['total_tasks'] }}</h4>
                                <small class="text-muted">Total Tasks</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-success">{{ $reportData['totals']['total_completed'] }}</h4>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-warning">{{ $reportData['totals']['total_in_progress'] }}</h4>
                                <small class="text-muted">In Progress</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-secondary">{{ $reportData['totals']['total_assignments'] }}</h4>
                                <small class="text-muted">Total Assignments</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-primary">{{ $reportData['totals']['avg_progress'] }}%</h4>
                                <small class="text-muted">Avg Progress</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Task Assignment Status Chart for Single Project -->
        @if($projectFilter)
            @php
                $selectedProjectData = $reportData['data']->firstWhere('project.id', $projectFilter);
            @endphp
          
        @endif

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Project Performance Summary</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project</th>
                                <th>Tasks</th>
                                <th>Progress</th>
                                <th>Team Size</th>
                                <th>Efficiency</th>
                                <th>Status Distribution</th>
                               
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['data'] as $project)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $project['project']->project_name }}</div>
                                    <small class="text-muted">{{ $project['total_assignments'] }} assignments</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">{{ $project['total_tasks'] }}</span>
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $project['completion_rate'] }}%"></div>
                                        </div>
                                        <small class="ms-2">{{ $project['completion_rate'] }}%</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" style="width: {{ $project['overall_progress'] }}%">
                                            {{ $project['overall_progress'] }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $project['team_size'] }} users</span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="fw-bold {{ $project['efficiency_rate'] >= 80 ? 'text-success' : ($project['efficiency_rate'] >= 60 ? 'text-warning' : 'text-danger') }}">
                                            {{ $project['efficiency_rate'] }}%
                                        </div>
                                        <small class="text-muted">{{ $project['avg_task_duration'] }} days avg</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if($project['completed_tasks'] > 0)
                                            <span class="badge bg-success">{{ $project['completed_tasks'] }} Done</span>
                                        @endif
                                        @if($project['in_progress_tasks'] > 0)
                                            <span class="badge bg-info">{{ $project['in_progress_tasks'] }} Progress</span>
                                        @endif
                                        @if($project['pending_tasks'] > 0)
                                            <span class="badge bg-warning">{{ $project['pending_tasks'] }} Pending</span>
                                        @endif
                                        @if($project['overdue_assignments'] > 0)
                                            <span class="badge bg-danger">{{ $project['overdue_assignments'] }} Overdue</span>
                                        @endif
                                    </div>
                                </td>
                             
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($reportData['type'] === 'detailed')
        <!-- Detailed Report -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Detailed Project Analysis</h6>
            </div>
            <div class="card-body">
                @foreach($reportData['data'] as $projectData)
                <div class="project-section mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold">{{ $projectData['project']->project_name }}</h5>
                        <span class="badge bg-primary">{{ $projectData['task_count'] }} Tasks</span>
                    </div>
                    
                    @foreach($projectData['tasks'] as $taskData)
                    <div class="task-card border rounded p-3 mb-3">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="fw-bold">{{ $taskData['task']->task_name }}</h6>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: {{ $taskData['progress'] }}%"></div>
                                </div>
                                <small class="text-muted">Progress: {{ $taskData['progress'] }}%</small>
                            </div>
                            <div class="col-md-4">
                                <span class="badge bg-{{ $taskData['task']->task_status === 'completed' ? 'success' : ($taskData['task']->task_status === 'in progress' ? 'info' : 'warning') }}">
                                    {{ ucwords($taskData['task']->task_status) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Assignment Details -->
                        <div class="mt-3">
                            <h6 class="small fw-bold text-muted">ASSIGNMENTS:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Seq</th>
                                            <th>User</th>
                                            <th>Work Description</th>
                                            <th>Timeline</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($taskData['assignments'] as $assignment)
                                        <tr class="{{ $assignment['is_overdue'] ? 'table-danger' : '' }}">
                                            <td>{{ $assignment['sequence'] }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar sm rounded-circle me-2" style="background-color: {{ $assignment['user']->color }}; color: white;">
                                                        {{ substr($assignment['user']->name, 0, 1) }}
                                                    </div>
                                                    <span>{{ $assignment['user']->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="{{ $assignment['work_description'] }}">
                                                    {{ $assignment['work_description'] }}
                                                </div>
                                            </td>
                                            <td>
                                                <small class="d-block">Start: {{ $assignment['start_date']?$assignment['start_date']->format('M d'):'' }}</small>
                                                <small class="d-block">Due: {{ $assignment['deadline']?$assignment['deadline']->format('M d'):'' }}</small>
                                                <small class="text-muted">{{ $assignment['days'] }} days</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $assignment['status'] === 'Completed' ? 'success' : ($assignment['status'] === 'Inprogress' ? 'info' : 'warning') }}">
                                                    {{ $assignment['status'] }}
                                                </span>
                                                @if($assignment['is_overdue'])
                                                    <br><small class="text-danger">{{ $assignment['days_remaining'] }} days overdue</small>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
                
                <!-- Pagination -->
                @if(isset($reportData['pagination']))
                    {{ $reportData['pagination']->links() }}
                @endif
            </div>
        </div>

    @elseif($reportData['type'] === 'analytics')
   
        <!-- Analytics Report -->
        @foreach($reportData['data'] as $analytics)
        <div class="project-analytics mb-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $analytics['project']->project_name }} - Analytics</h5>
                </div>
                <div class="card-body">
                    <!-- Key Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $analytics['key_metrics']['total_tasks'] }}</h4>
                                    <small>Total Tasks</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $analytics['key_metrics']['completion_rate'] }}%</h4>
                                    <small>Completion Rate</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $analytics['key_metrics']['avg_task_duration'] }}</h4>
                                    <small>Avg Duration (days)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $analytics['key_metrics']['overdue_rate'] }}%</h4>
                                    <small>Overdue Rate</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Task Assignment Status Chart -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Task Assignment Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 300px; position: relative;">
                                        <div id="taskStatusChart{{ $analytics['project']->id }}"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Team Performance</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Assignments</th>
                                                    <th>Completed</th>
                                                    <th>Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($analytics['user_stats']->take(5) as $userStat)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar sm rounded-circle me-2" style="background-color: {{ $userStat['user']->color }}; color: white;">
                                                                {{ substr($userStat['user']->name, 0, 1) }}
                                                            </div>
                                                            <small>{{ $userStat['user']->name }}</small>
                                                        </div>
                                                    </td>
                                                    <td>{{ $userStat['total_assignments'] }}</td>
                                                    <td>{{ $userStat['completed'] }}</td>
                                                    <td>
                                                        <small class="text-{{ $userStat['completion_rate'] >= 80 ? 'success' : ($userStat['completion_rate'] >= 60 ? 'warning' : 'danger') }}">
                                                            {{ $userStat['completion_rate'] }}%
                                                        </small>
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
            </div>
        </div>
        @endforeach
    @endif

    <!-- Project Details Modal -->
    @if($showProjectModal && $selectedProject)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $selectedProject->project_name }} - Detailed View</h5>
                    <button type="button" class="btn-close" wire:click="closeProjectModal"></button>
                </div>
                <div class="modal-body">
                    <!-- Modal content would show detailed project information -->
                    <p>Detailed project analysis would be displayed here...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeProjectModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
.project-section {
    border-left: 4px solid #0d6efd;
    padding-left: 1rem;
}

.task-card {
    background-color: #f8f9fa;
    border-left: 3px solid #6c757d;
}

.task-card:hover {
    background-color: #e9ecef;
    transition: background-color 0.2s ease;
}

.chart-container {
    position: relative;
}

.progress {
    border-radius: 0.375rem;
}

.avatar {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.avatar.sm {
    width: 24px;
    height: 24px;
    font-size: 0.75rem;
}

.table-sm th,
.table-sm td {
    padding: 0.375rem;
    vertical-align: middle;
}

.card-body .row [class*="col-"] .card {
    height: 100%;
}

.badge {
    font-size: 0.75em;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
<script src="{{ asset('assets/bundles/apexcharts.bundle.js')}}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initializeProjectCharts, 500);
});

document.addEventListener('livewire:navigated', function() {
   // setTimeout(initializeProjectCharts, 100);
   Livewire.on('report_type_changed', data => {
    if(data[0]=='analytics')
    setTimeout(initializeProjectCharts, 500);
    console.log('Event received in JS:', data);
});
});










function initializeProjectCharts() {
   
   
    // Initialize task status charts for analytics
    @if(isset($reportData) && $reportData['type'] === 'analytics')
      

        @foreach($reportData['data'] as $analytics)
     //  check_previous_chart({{ $analytics['project']->id }})
            initializeTaskStatusChart({{ $analytics['project']->id }}, {!! json_encode($analytics['status_distribution']) !!});
        @endforeach
    @endif
}



function initializeProjectAssignmentChart(data) {
    const chartElement = document.querySelector('#projectAssignmentStatusChart');
    if (!chartElement) return;
    
    // Destroy existing chart if it exists
    if (window.projectAssignmentChart) {
        window.projectAssignmentChart.destroy();
    }
    
    const options = {
        chart: {
            height: 280,
            type: 'donut',
        },
        labels: ['Completed', 'In Progress', 'Pending', 'Overdue'],
        dataLabels: {
            enabled: false,
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            show: true,
        },
        colors: ['#198754', '#0dcaf0', '#ffc107', '#dc3545'],
        series: [data.completed, data.in_progress, data.pending, data.overdue],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        plotOptions: {
            pie: {
                donut: {
                    size: '60%'
                }
            }
        },
        title: {
            text: 'Assignment Status Distribution',
            align: 'center',
            style: {
                fontSize: '14px',
                fontWeight: 'bold'
            }
        }
    };
    
    window.projectAssignmentChart = new ApexCharts(chartElement, options);
    window.projectAssignmentChart.render();
   
}

function initializeTaskStatusChart(projectId, statusData) {
   
    const chartElement = document.querySelector('#taskStatusChart' + projectId);
    //if (!chartElement) return;
    
    // Destroy existing chart if it exists
   
    console.log(statusData);
    
    const labels = Object.keys(statusData).map(status => 
        status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
    );
    const data = Object.values(statusData);
    const colors = [
        '#ffc107', // pending - yellow
        '#0dcaf0', // in_progress - cyan
        '#6c757d', // on_hold - gray
        '#198754', // completed - green
        '#dc3545'  // not_completed - red
    ];
    
    const options = {
        chart: {
            height: 280,
            type: 'donut',
        },
        labels: labels,
        dataLabels: {
            enabled: false,
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            show: true,
        },
        colors: colors,
        series: data,
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        plotOptions: {
            pie: {
                donut: {
                    size: '60%'
                }
            }
        },
        title: {
            text: 'Task Status Distribution',
            align: 'center',
            style: {
                fontSize: '14px',
                fontWeight: 'bold'
            }
        }
    };
    
    window['taskStatusChart' + projectId] = new ApexCharts(chartElement, options);
    window['taskStatusChart' + projectId].render();

    
   
}

function check_previous_chart(projectId){
   if (window['taskStatusChart' + projectId]) {
       
        window['taskStatusChart' + projectId].destroy();
    }
}
</script>
</div>