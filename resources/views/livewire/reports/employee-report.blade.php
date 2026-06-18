<div>
    <!-- Page Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h3 class="fw-bold mb-0">
                <i class="icofont-users me-2"></i>Employee Reports
            </h3>
            <!-- <p class="text-muted mb-0">Comprehensive employee performance, productivity and attendance analysis</p> -->
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
                        <option value="performance">Performance Report</option>
                     
                        <option value="detailed">Detailed Report</option>
                      
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select wire:model.live="departmentFilter" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employee</label>
                    <select wire:model.live="userFilter" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Inprogress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Reassigned">Reassigned</option>
                        <option value="Not Completed">Not Completed</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" wire:model.live="dateTo" class="form-control">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                        <i class="icofont-refresh me-1"></i>Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Summary (if department filter is applied) -->
    @if($departmentFilter && $this->getDepartmentStats())
        @php $deptStats = $this->getDepartmentStats(); @endphp
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-light-info">
                        <h6 class="mb-0">{{ $deptStats['department']->name }} Department Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary">{{ $deptStats['total_employees'] }}</h4>
                                <small class="text-muted">Total Employees</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-info">{{ $deptStats['total_assignments'] }}</h4>
                                <small class="text-muted">Total Assignments</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success">{{ $deptStats['completed_assignments'] }}</h4>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning">{{ $deptStats['completion_rate'] }}%</h4>
                                <small class="text-muted">Completion Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Report Content -->
    @if($reportData['type'] === 'performance')
        <!-- Performance Report -->
        @if(isset($reportData['summary']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <h4 class="text-primary">{{ $reportData['summary']['total_employees'] }}</h4>
                                <small class="text-muted">Total Employees</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-success">{{ number_format($reportData['summary']['avg_completion_rate'], 1) }}%</h4>
                                <small class="text-muted">Avg Completion</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-info">{{ number_format($reportData['summary']['avg_on_time_rate'], 1) }}%</h4>
                                <small class="text-muted">Avg On-Time</small>
                            </div>
                            <!-- <div class="col-md-2">
                                <h4 class="text-warning">{{ number_format($reportData['summary']['avg_quality_score'], 1) }}%</h4>
                                <small class="text-muted">Avg Quality</small>
                            </div> -->
                            <div class="col-md-2">
                                <h4 class="text-secondary">{{ $reportData['summary']['total_assignments'] }}</h4>
                                <small class="text-muted">Total Assignments</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-primary">{{ $reportData['summary']['total_completed'] }}</h4>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Assignment Status Chart for Single Employee -->
        @if($userFilter)
            @php
                $selectedEmployeeData = $reportData['data']->firstWhere('user.id', $userFilter);
            @endphp
         
        @endif

        <!-- Performance Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Employee Performance Rankings</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Assignments</th>
                                <th>Completion Rate</th>
                                <th>On-Time Rate</th>
                              
                                <th>Current Workload</th>
                                <!-- <th>Actions</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['data'] as $index => $employee)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $index < 3 ? 'success' : ($index < 10 ? 'info' : 'secondary') }} fs-6">
                                        #{{ $index + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar sm rounded-circle me-2" style="background-color: {{ $employee['user']->color }}; color: white;">
                                            {{ substr($employee['user']->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $employee['user']->name }}</div>
                                            <small class="text-muted">{{ $employee['user']->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ $employee['user']->department->name ?? 'No Department' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="fw-bold">{{ $employee['total_assignments'] }}</div>
                                        <small class="text-success">{{ $employee['completed_assignments'] }} completed</small>
                                        @if($employee['overdue_assignments'] > 0)
                                            <br><small class="text-danger">{{ $employee['overdue_assignments'] }} overdue</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="progress mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $employee['completion_rate'] >= 80 ? 'success' : ($employee['completion_rate'] >= 60 ? 'warning' : 'danger') }}" 
                                                 style="width: {{ $employee['completion_rate'] }}%"></div>
                                        </div>
                                        <small class="fw-bold">{{ $employee['completion_rate'] }}%</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="fw-bold {{ $employee['on_time_rate'] >= 80 ? 'text-success' : ($employee['on_time_rate'] >= 60 ? 'text-warning' : 'text-danger') }}">
                                            {{ $employee['on_time_rate'] }}%
                                        </div>
                                    </div>
                                </td>
                              
                                <td>
                                    <div class="text-center">
                                        <span class="badge bg-{{ $employee['current_workload'] > 5 ? 'danger' : ($employee['current_workload'] > 3 ? 'warning' : 'success') }}">
                                            {{ $employee['current_workload'] }} tasks
                                        </span>
                                    </div>
                                </td>
                                <!-- <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            wire:click="viewEmployeeDetails({{ $employee['user']->id }})">
                                        <i class="icofont-eye"></i> View
                                    </button>
                                </td> -->
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($reportData['type'] === 'productivity')
        <!-- Productivity Report -->
        <div class="row">
            @foreach($reportData['data'] as $employee)
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="avatar sm rounded-circle me-2" style="background-color: {{ $employee['user']->color }}; color: white;">
                                {{ substr($employee['user']->name, 0, 1) }}
                            </div>
                            <h6 class="mb-0">{{ $employee['user']->name }}</h6>
                            <span class="badge bg-{{ $employee['productivity_trend'] === 'improving' ? 'success' : ($employee['productivity_trend'] === 'declining' ? 'danger' : 'secondary') }} ms-auto">
                                {{ ucfirst($employee['productivity_trend']) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Task Complexity Distribution -->
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-success fw-bold">{{ $employee['task_complexity']['simple'] }}</div>
                                <small class="text-muted">Simple</small>
                            </div>
                            <div class="col-4">
                                <div class="text-warning fw-bold">{{ $employee['task_complexity']['medium'] }}</div>
                                <small class="text-muted">Medium</small>
                            </div>
                            <div class="col-4">
                                <div class="text-danger fw-bold">{{ $employee['task_complexity']['complex'] }}</div>
                                <small class="text-muted">Complex</small>
                            </div>
                        </div>

                        <!-- Key Metrics -->
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="text-primary fw-bold">{{ $employee['total_output'] }}</div>
                                <small class="text-muted">Total Output</small>
                            </div>
                            <div class="col-6">
                                <div class="text-info fw-bold">{{ $employee['avg_daily_output'] }}</div>
                                <small class="text-muted">Daily Avg</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    @elseif($reportData['type'] === 'detailed')

    
        <!-- Detailed Report -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Detailed Employee Analysis</h6>
            </div>
            <div class="card-body">
                @foreach($reportData['data'] as $employeeData)
                <div class="employee-section mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar rounded-circle me-3" style="background-color: {{ $employeeData['user']->color }}; color: white;">
                                {{ substr($employeeData['user']->name, 0, 1) }}
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">{{ $employeeData['user']->name }}</h5>
                                <small class="text-muted">{{ $employeeData['user']->department->name ?? 'No Department' }}</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary">{{ $employeeData['total_assignments'] }} Assignments</span>
                            <span class="badge bg-warning">{{ $employeeData['current_workload'] }} Active</span>
                        </div>
                    </div>
                    
                    <!-- Assignment History -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project</th>
                                    <th>Task</th>
                                    <th>Work Description</th>
                                    <th>Timeline</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <!-- <th>Efficiency</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employeeData['assignments'] as $assignmentDetail)
                                <tr class="{{ $assignmentDetail['assignment']->is_overdue ? 'table-danger' : '' }}">
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $assignmentDetail['project']->project_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $assignmentDetail['task']->task_name }}</div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $assignmentDetail['assignment']->work_description }}">
                                            {{ $assignmentDetail['assignment']->work_description }}
                                        </div>
                                    </td>
                                    <td>
                                        <small class="d-block">{{ $assignmentDetail['assignment']->start_date->format('M d, Y') }}</small>
                                        <small class="d-block">{{ $assignmentDetail['assignment']->deadline->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $assignmentDetail['duration_days'] }} days</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $assignmentDetail['assignment']->status === 'Completed' ? 'success' : ($assignmentDetail['assignment']->status === 'Inprogress' ? 'info' : 'warning') }}">
                                            {{ $assignmentDetail['assignment']->status }}
                                        </span>
                                    </td>
                                    <!-- <td>
                                        <span class="badge bg-{{ $assignmentDetail['efficiency_rating'] === 'Excellent' ? 'success' : ($assignmentDetail['efficiency_rating'] === 'Good' ? 'info' : ($assignmentDetail['efficiency_rating'] === 'Average' ? 'warning' : 'danger')) }}">
                                            {{ $assignmentDetail['efficiency_rating'] }}
                                        </span>
                                    </td> -->
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                
                <!-- Pagination -->
                @if(isset($reportData['pagination']))
                    {{ $reportData['pagination']->links() }}
                @endif
            </div>
        </div>

    @elseif($reportData['type'] === 'attendance')
        <!-- Attendance Report -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Employee Attendance Analysis</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Working Days</th>
                                <th>Active Days</th>
                                <th>Attendance Rate</th>
                                <th>Avg Tasks/Day</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['data'] as $employee)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar sm rounded-circle me-2" style="background-color: {{ $employee['user']->color }}; color: white;">
                                            {{ substr($employee['user']->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $employee['user']->name }}</div>
                                            <small class="text-muted">{{ $employee['user']->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ $employee['user']->department->name ?? 'No Department' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $employee['working_days'] }}</td>
                                <td class="text-center">
                                    <span class="fw-bold">{{ $employee['active_days'] }}</span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="progress mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $employee['attendance_rate'] >= 90 ? 'success' : ($employee['attendance_rate'] >= 75 ? 'warning' : 'danger') }}" 
                                                 style="width: {{ $employee['attendance_rate'] }}%"></div>
                                        </div>
                                        <small class="fw-bold">{{ $employee['attendance_rate'] }}%</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $employee['avg_tasks_per_day'] }}</span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @if($employee['attendance_rate'] >= 90)
                                            <span class="badge bg-success">Excellent</span>
                                        @elseif($employee['attendance_rate'] >= 75)
                                            <span class="badge bg-warning">Good</span>
                                        @else
                                            <span class="badge bg-danger">Needs Improvement</span>
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
    @endif

    <!-- Employee Details Modal -->
    @if($showEmployeeModal && $selectedEmployee)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $selectedEmployee->name }} - Detailed Analysis</h5>
                    <button type="button" class="btn-close" wire:click="closeEmployeeModal"></button>
                </div>
                <div class="modal-body">
                    <!-- Detailed employee analysis would be displayed here -->
                    <p>Comprehensive employee performance analysis would be shown here...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeEmployeeModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
.bg-light-info {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

.employee-section {
    border-left: 4px solid #0d6efd;
    padding-left: 1rem;
}

.chart-container {
    position: relative;
}

.avatar {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.avatar.sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}

.table-sm th,
.table-sm td {
    padding: 0.375rem;
    vertical-align: middle;
}

.progress {
    border-radius: 0.375rem;
}

.badge {
    font-size: 0.75em;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-body .row [class*="col-"] .card {
    height: 100%;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.fw-medium {
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeEmployeeCharts();
});

document.addEventListener('livewire:navigated', function() {
    setTimeout(initializeEmployeeCharts, 100);
});

function initializeEmployeeCharts() {
    @if(isset($reportData) && $reportData['type'] === 'performance' && $userFilter)
        @php
            $selectedEmployeeData = $reportData['data']->firstWhere('user.id', $userFilter);
        @endphp
        @if($selectedEmployeeData)
            initializeEmployeeAssignmentChart({!! json_encode([
                'completed' => $selectedEmployeeData['completed_assignments'],
                'in_progress' => $selectedEmployeeData['in_progress_assignments'],
                'pending' => $selectedEmployeeData['pending_assignments'],
                'overdue' => $selectedEmployeeData['overdue_assignments']
            ]) !!});
        @endif
    @endif
}

function initializeEmployeeAssignmentChart(data) {
    const canvas = document.getElementById('employeeAssignmentStatusChart');
    if (!canvas) return;
    
    // Destroy existing chart if it exists
    if (window.employeeAssignmentChart) {
        window.employeeAssignmentChart.destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    window.employeeAssignmentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Pending', 'Overdue'],
            datasets: [{
                data: [data.completed, data.in_progress, data.pending, data.overdue],
                backgroundColor: [
                    '#198754', // green - completed
                    '#0dcaf0', // cyan - in progress
                    '#ffc107', // yellow - pending
                    '#dc3545'  // red - overdue
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                title: {
                    display: true,
                    text: 'Assignment Status Distribution'
                }
            }
        }
    });
}
</script>
</div>