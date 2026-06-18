<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Department;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter Properties
    public $dateFrom = '';
    public $dateTo = '';
    public $departmentFilter = '';
    public $userFilter = '';
    public $statusFilter = '';
    public $reportType = 'performance'; // performance, productivity, attendance, detailed

    // Data Properties
    public $departments = [];
    public $users = [];
    public $selectedEmployee = null;
    public $showEmployeeModal = false;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadData();
    }

    public function render()
    {
        $reportData = $this->generateReportData();

        return view('livewire.reports.employee-report', [
            'reportData' => $reportData,
        ])->layout('layouts.app');
    }

    private function loadData()
    {
        $this->departments = Department::where('is_active', true)->get();
        $this->users = User::with('department')->get();
    }

    private function generateReportData()
    {
        $query = User::with(['department'])
            ->when($this->departmentFilter, function ($q) {
                $q->where('department_id', $this->departmentFilter);
            })
            ->when($this->userFilter, function ($q) {
                $q->where('id', $this->userFilter);
            });

        if ($this->reportType === 'performance') {
            return $this->generatePerformanceReport($query);
        } elseif ($this->reportType === 'productivity') {
            return $this->generateProductivityReport($query);
        } elseif ($this->reportType === 'detailed') {
            return $this->generateDetailedReport($query);
        } else {
            return $this->generateAttendanceReport($query);
        }
    }

    private function generatePerformanceReport($query)
    {
        $users = $query->get();
        $performanceData = [];

        foreach ($users as $user) {
            $assignments = TaskAssignment::where('user_id', $user->id)
                ->when($this->dateFrom && $this->dateTo, function ($q) {
                    $q->whereBetween('start_date', [$this->dateFrom, $this->dateTo])
                      ->orWhereBetween('deadline', [$this->dateFrom, $this->dateTo]);
                })
                ->when($this->statusFilter, function ($q) {
                    $q->where('status', $this->statusFilter);
                }) ->where('is_admin',0)
                ->with(['task.project'])
                ->get();

            $totalAssignments = $assignments->count();
            $completedAssignments = $assignments->where('status', 'Completed')->count();
            $inProgressAssignments = $assignments->where('status', 'Inprogress')->count();
            $pendingAssignments = $assignments->where('status', 'Pending')->count();
            $overdueAssignments = $assignments->filter(function ($assignment) {
                return $assignment->deadline < Carbon::today() && 
                       !in_array($assignment->status, ['Completed', 'Reassigned']);
            })->count();

            // Performance metrics
            $completionRate = $totalAssignments > 0 ? 
                round(($completedAssignments / $totalAssignments) * 100, 1) : 0;

            $onTimeCompletion = $assignments->filter(function ($assignment) {
                return $assignment->status === 'Completed' && 
                       $assignment->updated_at <= $assignment->deadline;
            })->count();

            $onTimeRate = $completedAssignments > 0 ? 
                round(($onTimeCompletion / $completedAssignments) * 100, 1) : 0;

            $avgTaskDuration = $assignments->avg('no_of_days') ?? 0;

            // Quality metrics (based on reassignments and not completed)
            $reassignments = $assignments->where('status', 'Reassigned')->count();
            $notCompleted = $assignments->where('status', 'Not Completed')->count();
            $qualityScore = $totalAssignments > 0 ? 
                round((($totalAssignments - $reassignments - $notCompleted) / $totalAssignments) * 100, 1) : 100;

            // Workload analysis
            $currentWorkload = TaskAssignment::where('user_id', $user->id)
                ->whereIn('status', ['Pending', 'Inprogress'])
                ->count();

            // Project diversity
            $uniqueProjects = $assignments->pluck('task.project_id')->unique()->count();

            $performanceData[] = [
                'user' => $user,
                'total_assignments' => $totalAssignments,
                'completed_assignments' => $completedAssignments,
                'in_progress_assignments' => $inProgressAssignments,
                'pending_assignments' => $pendingAssignments,
                'overdue_assignments' => $overdueAssignments,
                'completion_rate' => $completionRate,
                'on_time_rate' => $onTimeRate,
                'avg_task_duration' => round($avgTaskDuration, 1),
                'quality_score' => $qualityScore,
                'current_workload' => $currentWorkload,
                'project_diversity' => $uniqueProjects,
                'performance_score' => $this->calculatePerformanceScore([
                    'completion_rate' => $completionRate,
                    'on_time_rate' => $onTimeRate,
                    'quality_score' => $qualityScore,
                ]),
            ];
        }

        // Sort by performance score
        $performanceData = collect($performanceData)->sortByDesc('performance_score')->values();

        return [
            'type' => 'performance',
            'data' => $performanceData,
            'summary' => $this->calculatePerformanceSummary($performanceData),
        ];
    }

    private function generateProductivityReport($query)
    {
        $users = $query->get();
        $productivityData = [];

        foreach ($users as $user) {
            // Daily productivity over the date range
            $dailyStats = [];
            $period = Carbon::parse($this->dateFrom);
            $endDate = Carbon::parse($this->dateTo);

            while ($period <= $endDate) {
                $dayAssignments = TaskAssignment::where('user_id', $user->id)
                    ->whereDate('start_date', '<=', $period)
                    ->whereDate('deadline', '>=', $period)
                     ->where('is_admin',0)
                    ->get();

                $dailyStats[] = [
                    'date' => $period->format('Y-m-d'),
                    'active_assignments' => $dayAssignments->count(),
                    'completed_today' => $dayAssignments->where('status', 'Completed')
                        ->where('updated_at', '>=', $period->startOfDay())
                        ->where('updated_at', '<=', $period->endOfDay())
                        ->count(),
                ];

                $period->addDay();
            }

            // Weekly productivity trends
            $weeklyStats = [];
            for ($i = 3; $i >= 0; $i--) {
                $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
                $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();

                $weekAssignments = TaskAssignment::where('user_id', $user->id)
                 ->where('is_admin',0)
                    ->whereBetween('start_date', [$weekStart, $weekEnd])
                    ->orWhereBetween('deadline', [$weekStart, $weekEnd])
                    ->get();

                $weeklyStats[] = [
                    'week' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                    'assignments' => $weekAssignments->count(),
                    'completed' => $weekAssignments->where('status', 'Completed')->count(),
                    'productivity_score' => $this->calculateWeeklyProductivity($weekAssignments),
                ];
            }

            // Task complexity analysis
            $assignments = TaskAssignment::where('user_id', $user->id)
             ->where('is_admin',0)
                ->when($this->dateFrom && $this->dateTo, function ($q) {
                    $q->whereBetween('start_date', [$this->dateFrom, $this->dateTo]);
                })
                ->get();

            $taskComplexity = [
                'simple' => $assignments->where('no_of_days', '<=', 3)->count(),
                'medium' => $assignments->where('no_of_days', '>', 3)->where('no_of_days', '<=', 7)->count(),
                'complex' => $assignments->where('no_of_days', '>', 7)->count(),
            ];

            $productivityData[] = [
                'user' => $user,
                'daily_stats' => $dailyStats,
                'weekly_stats' => $weeklyStats,
                'task_complexity' => $taskComplexity,
                'total_output' => $assignments->where('status', 'Completed')->count(),
                'avg_daily_output' => $this->calculateAvgDailyOutput($user->id),
                'productivity_trend' => $this->calculateProductivityTrend($weeklyStats),
            ];
        }

        return [
            'type' => 'productivity',
            'data' => collect($productivityData),
        ];
    }

    private function generateDetailedReport($query)
    {
        $users = $query->paginate(10);
        $detailedData = [];

        foreach ($users as $user) {
            $assignments = TaskAssignment::where('user_id', $user->id)
                ->with(['task.project'])
                 ->where('is_admin',0)
                ->when($this->dateFrom && $this->dateTo, function ($q) {
                    $q->whereBetween('start_date', [$this->dateFrom, $this->dateTo]);
                })
                ->orderBy('start_date', 'desc')
                ->get();

            $assignmentDetails = [];
            foreach ($assignments as $assignment) {
                $assignmentDetails[] = [
                    'assignment' => $assignment,
                    'task' => $assignment->task,
                    'project' => $assignment->task->project,
                    'duration_days' => $assignment->no_of_days,
                    'status_timeline' => $this->getAssignmentTimeline($assignment),
                    'efficiency_rating' => $this->calculateEfficiencyRating($assignment),
                ];
            }

            $detailedData[] = [
                'user' => $user,
                'assignments' => $assignmentDetails,
                'total_assignments' => $assignments->count(),
                'current_workload' => $assignments->whereIn('status', ['Pending', 'Inprogress'])->count(),
            ];
        }

        return [
            'type' => 'detailed',
            'data' => $detailedData,
            'pagination' => $users,
        ];
    }

    private function generateAttendanceReport($query)
    {
        $users = $query->get();
        $attendanceData = [];

        foreach ($users as $user) {
            // Task-based attendance tracking
            $period = Carbon::parse($this->dateFrom);
            $endDate = Carbon::parse($this->dateTo);
            $workingDays = 0;
            $activeDays = 0;

            while ($period <= $endDate) {
                if ($period->isWeekday()) {
                    $workingDays++;
                    
                    // Check if user had any activity on this day
                    $hasActivity = TaskAssignment::where('user_id', $user->id)
                     ->where('is_admin',0)
                        ->where(function ($q) use ($period) {
                            $q->whereDate('start_date', $period)
                              ->orWhereDate('updated_at', $period);
                        })
                        ->exists();
                    
                    if ($hasActivity) {
                        $activeDays++;
                    }
                }
                $period->addDay();
            }

            $attendanceRate = $workingDays > 0 ? round(($activeDays / $workingDays) * 100, 1) : 0;

            $attendanceData[] = [
                'user' => $user,
                'working_days' => $workingDays,
                'active_days' => $activeDays,
                'attendance_rate' => $attendanceRate,
                'avg_tasks_per_day' => $activeDays > 0 ? 
                    round(TaskAssignment::where('user_id', $user->id)
                     ->where('is_admin',0)
                        ->whereBetween('start_date', [$this->dateFrom, $this->dateTo])
                        ->count() / $activeDays, 1) : 0,
            ];
        }

        return [
            'type' => 'attendance',
            'data' => collect($attendanceData)->sortByDesc('attendance_rate'),
        ];
    }

    // Helper methods
    private function calculatePerformanceScore($metrics)
    {
        return round(
            ($metrics['completion_rate'] * 0.4) + 
            ($metrics['on_time_rate'] * 0.3) + 
            ($metrics['quality_score'] * 0.3),
            1
        );
    }

    private function calculatePerformanceSummary($performanceData)
    {
        $data = collect($performanceData);
        
        return [
            'total_employees' => $data->count(),
            'avg_completion_rate' => $data->avg('completion_rate'),
            'avg_on_time_rate' => $data->avg('on_time_rate'),
            'avg_quality_score' => $data->avg('quality_score'),
            'top_performer' => $data->first(),
            'total_assignments' => $data->sum('total_assignments'),
            'total_completed' => $data->sum('completed_assignments'),
        ];
    }

    private function calculateWeeklyProductivity($assignments)
    {
        if ($assignments->isEmpty()) return 0;
        
        $completed = $assignments->where('status', 'Completed')->count();
        $total = $assignments->count();
        
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    }

    private function calculateAvgDailyOutput($userId)
    {
        $days = Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo)) + 1;
        $completed = TaskAssignment::where('user_id', $userId)
         ->where('is_admin',0)
            ->where('status', 'Completed')
            ->whereBetween('updated_at', [$this->dateFrom, $this->dateTo])
            ->count();
        
        return $days > 0 ? round($completed / $days, 2) : 0;
    }

    private function calculateProductivityTrend($weeklyStats)
    {
        if (count($weeklyStats) < 2) return 'stable';
        
        $lastWeek = end($weeklyStats)['productivity_score'];
        $previousWeek = prev($weeklyStats)['productivity_score'];
        
        if ($lastWeek > $previousWeek + 5) return 'improving';
        if ($lastWeek < $previousWeek - 5) return 'declining';
        return 'stable';
    }

    private function getAssignmentTimeline($assignment)
    {
        // This would typically fetch from task update history
        return [
            'created' => $assignment->created_at,
            'started' => $assignment->start_date,
            'last_updated' => $assignment->updated_at,
            'status_changes' => [], // Would be populated from history
        ];
    }

    private function calculateEfficiencyRating($assignment)
    {
        if ($assignment->status !== 'Completed') return 'N/A';
        
        $plannedDays = $assignment->no_of_days;
        $actualDays = $assignment->created_at->diffInDays($assignment->updated_at);
        
        if ($actualDays <= $plannedDays) return 'Excellent';
        if ($actualDays <= $plannedDays * 1.2) return 'Good';
        if ($actualDays <= $plannedDays * 1.5) return 'Average';
        return 'Below Average';
    }

    public function exportReport($format = 'excel')
    {
        $reportData = $this->generateReportData();
        
        $this->dispatch('downloadReport', [
            'type' => 'employee',
            'format' => $format,
            'data' => $reportData,
            'filters' => [
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'department' => $this->departmentFilter,
                'user' => $this->userFilter,
                'status' => $this->statusFilter,
                'reportType' => $this->reportType,
            ]
        ]);
    }

    public function viewEmployeeDetails($userId)
    {
        $this->selectedEmployee = User::with(['department'])
            ->where('id', $userId)
            ->first();
        
        $this->showEmployeeModal = true;
    }

    public function closeEmployeeModal()
    {
        $this->showEmployeeModal = false;
        $this->selectedEmployee = null;
    }

    // Filter methods
    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter()
    {
        $this->resetPage();
        // Reset user filter when department changes
        $this->userFilter = '';
        
        // Load users for selected department
        if ($this->departmentFilter) {
            $this->users = User::where('department_id', $this->departmentFilter)->get();
        } else {
            $this->users = User::with('department')->get();
        }
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedReportType()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->departmentFilter = '';
        $this->userFilter = '';
        $this->statusFilter = '';
        $this->reportType = 'performance';
        $this->loadData(); // Reload all users
        $this->resetPage();
    }

    public function getDepartmentStats()
    {
        if (!$this->departmentFilter) return null;
        
        $department = Department::find($this->departmentFilter);
        if (!$department) return null;
        
        $users = User::where('department_id', $this->departmentFilter)->get();
        $totalAssignments = 0;
        $completedAssignments = 0;
        
        foreach ($users as $user) {
            $assignments = TaskAssignment::where('user_id', $user->id)
             ->where('is_admin',0)
                ->when($this->dateFrom && $this->dateTo, function ($q) {
                    $q->whereBetween('start_date', [$this->dateFrom, $this->dateTo]);
                })
                ->get();
                
            $totalAssignments += $assignments->count();
            $completedAssignments += $assignments->where('status', 'Completed')->count();
        }
        
        return [
            'department' => $department,
            'total_employees' => $users->count(),
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'completion_rate' => $totalAssignments > 0 ? 
                round(($completedAssignments / $totalAssignments) * 100, 1) : 0,
        ];
    }

    public function getTopPerformers($limit = 5)
    {
        $reportData = $this->generateReportData();
        
        if ($reportData['type'] === 'performance') {
            return $reportData['data']->take($limit);
        }
        
        return collect();
    }

    public function getBottomPerformers($limit = 5)
    {
        $reportData = $this->generateReportData();
        
        if ($reportData['type'] === 'performance') {
            return $reportData['data']->reverse()->take($limit);
        }
        
        return collect();
    }

    public function generateComparisonReport($userId1, $userId2)
    {
        $user1Data = $this->getUserPerformanceData($userId1);
        $user2Data = $this->getUserPerformanceData($userId2);
        
        return [
            'user1' => $user1Data,
            'user2' => $user2Data,
            'comparison' => [
                'completion_rate_diff' => $user1Data['completion_rate'] - $user2Data['completion_rate'],
                'on_time_rate_diff' => $user1Data['on_time_rate'] - $user2Data['on_time_rate'],
                'quality_score_diff' => $user1Data['quality_score'] - $user2Data['quality_score'],
                'productivity_diff' => $user1Data['avg_daily_output'] - $user2Data['avg_daily_output'],
            ]
        ];
    }

    private function getUserPerformanceData($userId)
    {
        $user = User::find($userId);
        $assignments = TaskAssignment::where('user_id', $userId)
         ->where('is_admin',0)
            ->whereBetween('start_date', [$this->dateFrom, $this->dateTo])
            ->get();
            
        $completed = $assignments->where('status', 'Completed')->count();
        $total = $assignments->count();
        
        return [
            'user' => $user,
            'total_assignments' => $total,
            'completed_assignments' => $completed,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'on_time_rate' => $this->calculateOnTimeRate($assignments),
            'quality_score' => $this->calculateQualityScore($assignments),
            'avg_daily_output' => $this->calculateAvgDailyOutput($userId),
        ];
    }

    private function calculateOnTimeRate($assignments)
    {
        $completed = $assignments->where('status', 'Completed');
        if ($completed->isEmpty()) return 0;
        
        $onTime = $completed->filter(function ($assignment) {
            return $assignment->updated_at <= $assignment->deadline;
        });
        
        return round(($onTime->count() / $completed->count()) * 100, 1);
    }

    private function calculateQualityScore($assignments)
    {
        if ($assignments->isEmpty()) return 100;
        
        $reassignments = $assignments->where('status', 'Reassigned')->count();
        $notCompleted = $assignments->where('status', 'Not Completed')->count();
        $total = $assignments->count();
        
        return round((($total - $reassignments - $notCompleted) / $total) * 100, 1);
    }

    public function getEmployeeAssignmentStatus($userId)
{
    $assignments = TaskAssignment::where('user_id', $userId)
     ->where('is_admin',0)
        ->when($this->dateFrom && $this->dateTo, function ($q) {
            $q->whereBetween('start_date', [$this->dateFrom, $this->dateTo])
              ->orWhereBetween('deadline', [$this->dateFrom, $this->dateTo]);
        })
        ->when($this->statusFilter, function ($q) {
            $q->where('status', $this->statusFilter);
        })
        ->get();

    $statusCounts = [
        'completed' => $assignments->where('status', 'Completed')->count(),
        'in_progress' => $assignments->where('status', 'Inprogress')->count(),
        'pending' => $assignments->where('status', 'Pending')->count(),
        'overdue' => $assignments->filter(function ($assignment) {
            return $assignment->deadline < Carbon::today() && 
                   !in_array($assignment->status, ['Completed', 'Reassigned']);
        })->count(),
        'reassigned' => $assignments->where('status', 'Reassigned')->count(),
        'not_completed' => $assignments->where('status', 'Not Completed')->count(),
    ];

    return $statusCounts;
}

/**
 * Get assignment status data for chart rendering
 */
public function getEmployeeChartData($userId)
{
    $statusData = $this->getEmployeeAssignmentStatus($userId);
    
    return [
        'labels' => ['Completed', 'In Progress', 'Pending', 'Overdue'],
        'data' => [
            $statusData['completed'],
            $statusData['in_progress'],
            $statusData['pending'],
            $statusData['overdue']
        ],
        'colors' => ['#198754', '#0dcaf0', '#ffc107', '#dc3545']
    ];
}





/**
 * Get task assignment status breakdown for a specific project
 */
public function getProjectAssignmentStatus($projectId)
{
    $project = Project::with(['tasks.assignments'])->find($projectId);
    
    if (!$project) {
        return null;
    }

    $assignments = $project->tasks->flatMap->assignments;

      $assignments = $assignments->filter(function ($assignment) {
            return  ($assignment->is_admin ==0);
        });
    
    // Filter by date range if specified
    if ($this->dateFrom && $this->dateTo) {
        $assignments = $assignments->filter(function ($assignment) {
            return ($assignment->start_date >= $this->dateFrom && $assignment->start_date <= $this->dateTo) ||
                   ($assignment->deadline >= $this->dateFrom && $assignment->deadline <= $this->dateTo);
        });
    }

    // Filter by status if specified
    if ($this->statusFilter) {
        $assignments = $assignments->filter(function ($assignment) {
            return $assignment->task->task_status === $this->statusFilter;
        });
    }

    $statusCounts = [
        'completed' => $assignments->where('status', 'Completed')->count(),
        'in_progress' => $assignments->where('status', 'Inprogress')->count(),
        'pending' => $assignments->where('status', 'Pending')->count(),
        'overdue' => $assignments->filter(function ($assignment) {
            return $assignment->deadline < Carbon::today() && 
                   !in_array($assignment->status, ['Completed', 'Reassigned']);
        })->count(),
        'reassigned' => $assignments->where('status', 'Reassigned')->count(),
        'not_completed' => $assignments->where('status', 'Not Completed')->count(),
    ];

    return $statusCounts;
}

/**
 * Get task status breakdown for a specific project (for analytics)
 */
public function getProjectTaskStatus($projectId)
{
    $project = Project::with(['tasks'])->find($projectId);
    
    if (!$project) {
        return null;
    }

    $tasks = $project->tasks;
    
    // Filter by date range if specified
    if ($this->dateFrom && $this->dateTo) {
        $tasks = $tasks->filter(function ($task) {
            return $task->assignments->some(function ($assignment) {
                return (($assignment->start_date >= $this->dateFrom && $assignment->start_date <= $this->dateTo) ||
                       ($assignment->deadline >= $this->dateFrom && $assignment->deadline <= $this->dateTo)) && ($assignment->is_admin==0);
            });
        });
    }

    $statusCounts = [
        'pending' => $tasks->where('task_status', 'pending')->count(),
        'in_progress' => $tasks->where('task_status', 'in progress')->count(),
        'on_hold' => $tasks->where('task_status', 'on hold')->count(),
        'completed' => $tasks->where('task_status', 'completed')->count(),
        'not_completed' => $tasks->where('task_status', 'not completed')->count(),
    ];

    return $statusCounts;
}

/**
 * Get project chart data for rendering
 */
public function getProjectChartData($projectId)
{
    $statusData = $this->getProjectAssignmentStatus($projectId);
    
    if (!$statusData) {
        return null;
    }
    
    return [
        'labels' => ['Completed', 'In Progress', 'Pending', 'Overdue'],
        'data' => [
            $statusData['completed'],
            $statusData['in_progress'],
            $statusData['pending'],
            $statusData['overdue']
        ],
        'colors' => ['#198754', '#0dcaf0', '#ffc107', '#dc3545']
    ];
}

/**
 * Enhanced analytics report generation with proper status data
 */
private function generateAnalyticsReportEnhanced($query)
{
    $projects = $query->get();
    $analyticsData = [];

    foreach ($projects as $project) {
        // Get task status distribution
        $statusDistribution = $this->getProjectTaskStatus($project->id);
        
        // Get assignment status for detailed analysis
        $assignmentStatus = $this->getProjectAssignmentStatus($project->id);

        // User performance analysis (keeping existing logic)
        $userStats = [];
        $assignments = $project->tasks->flatMap->assignments;
        $userGroups = $assignments->groupBy('user_id');

        foreach ($userGroups as $userId => $userAssignments) {
            $user = $userAssignments->first()->user;
            $completed = $userAssignments->where('status', 'Completed')->count();
            $overdue = $userAssignments->filter(function ($assignment) {
                return $assignment->is_overdue;
            })->count();
            
            $avgDuration = $userAssignments->avg('no_of_days');
            $onTimeCompletion = $userAssignments->filter(function ($assignment) {
                return $assignment->status === 'Completed' && 
                       $assignment->updated_at <= $assignment->deadline;
            })->count();

            $userStats[] = [
                'user' => $user,
                'total_assignments' => $userAssignments->count(),
                'completed' => $completed,
                'overdue' => $overdue,
                'completion_rate' => $userAssignments->count() > 0 ? 
                    round(($completed / $userAssignments->count()) * 100, 1) : 0,
                'avg_duration' => round($avgDuration, 1),
                'on_time_rate' => $completed > 0 ? 
                    round(($onTimeCompletion / $completed) * 100, 1) : 0,
            ];
        }

        $analyticsData[] = [
            'project' => $project,
            'user_stats' => collect($userStats)->sortByDesc('completion_rate'),
            'status_distribution' => $statusDistribution,
            'assignment_status' => $assignmentStatus,
            'key_metrics' => [
                'total_tasks' => $project->tasks->count(),
                'total_assignments' => $assignments->count(),
                'completion_rate' => $project->tasks->count() > 0 ? 
                    round(($project->tasks->where('task_status', 'completed')->count() / $project->tasks->count()) * 100, 1) : 0,
                'assignment_completion_rate' => $assignments->count() > 0 ?
                    round(($assignments->where('status', 'Completed')->count() / $assignments->count()) * 100, 1) : 0,
                'avg_task_duration' => round($assignments->avg('no_of_days'), 1),
                'overdue_rate' => $assignments->count() > 0 ? 
                    round(($assignments->filter->is_overdue->count() / $assignments->count()) * 100, 1) : 0,
            ],
        ];
    }

    return [
        'type' => 'analytics',
        'data' => $analyticsData,
    ];
}




// Update the existing generateReportData method in both components to include assignment status

// In EmployeeReport.php - update the performance report section:
private function generatePerformanceReportEnhanced($query)
{
    $users = $query->get();
    $performanceData = [];

    foreach ($users as $user) {
        $assignments = TaskAssignment::where('user_id', $user->id)
         ->where('is_admin',0)
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('start_date', [$this->dateFrom, $this->dateTo])
                  ->orWhereBetween('deadline', [$this->dateFrom, $this->dateTo]);
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->with(['task.project'])
            ->get();

        $totalAssignments = $assignments->count();
        $completedAssignments = $assignments->where('status', 'Completed')->count();
        $inProgressAssignments = $assignments->where('status', 'Inprogress')->count();
        $pendingAssignments = $assignments->where('status', 'Pending')->count();
        $overdueAssignments = $assignments->filter(function ($assignment) {
            return $assignment->deadline < Carbon::today() && 
                   !in_array($assignment->status, ['Completed', 'Reassigned']);
        })->count();

        // Calculate performance metrics (keeping existing logic)
        $completionRate = $totalAssignments > 0 ? 
            round(($completedAssignments / $totalAssignments) * 100, 1) : 0;

        $onTimeCompletion = $assignments->filter(function ($assignment) {
            return $assignment->status === 'Completed' && 
                   $assignment->updated_at <= $assignment->deadline;
        })->count();

        $onTimeRate = $completedAssignments > 0 ? 
            round(($onTimeCompletion / $completedAssignments) * 100, 1) : 0;

        $avgTaskDuration = $assignments->avg('no_of_days') ?? 0;

        $reassignments = $assignments->where('status', 'Reassigned')->count();
        $notCompleted = $assignments->where('status', 'Not Completed')->count();
        $qualityScore = $totalAssignments > 0 ? 
            round((($totalAssignments - $reassignments - $notCompleted) / $totalAssignments) * 100, 1) : 100;

        $currentWorkload = TaskAssignment::where('user_id', $user->id)
            ->whereIn('status', ['Pending', 'Inprogress'])
            ->count();

        $uniqueProjects = $assignments->pluck('task.project_id')->unique()->count();

        $performanceData[] = [
            'user' => $user,
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'in_progress_assignments' => $inProgressAssignments,
            'pending_assignments' => $pendingAssignments,
            'overdue_assignments' => $overdueAssignments,
            'completion_rate' => $completionRate,
            'on_time_rate' => $onTimeRate,
            'avg_task_duration' => round($avgTaskDuration, 1),
            'quality_score' => $qualityScore,
            'current_workload' => $currentWorkload,
            'project_diversity' => $uniqueProjects,
            'performance_score' => $this->calculatePerformanceScore([
                'completion_rate' => $completionRate,
                'on_time_rate' => $onTimeRate,
                'quality_score' => $qualityScore,
            ]),
            // Add assignment status breakdown for charts
            'assignment_status' => [
                'completed' => $completedAssignments,
                'in_progress' => $inProgressAssignments,
                'pending' => $pendingAssignments,
                'overdue' => $overdueAssignments,
                'reassigned' => $reassignments,
                'not_completed' => $notCompleted,
            ]
        ];
    }

    // Sort by performance score
    $performanceData = collect($performanceData)->sortByDesc('performance_score')->values();

    return [
        'type' => 'performance',
        'data' => $performanceData,
        'summary' => $this->calculatePerformanceSummary($performanceData),
    ];
}
}
?>