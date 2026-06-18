<?php

namespace App\Livewire\Reports;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter Properties
    public $dateFrom = '';
    public $dateTo = '';
    public $projectFilter = '';
    public $statusFilter = '';
    public $reportType = 'analytics'; // summary, detailed, analytics

    // Data Properties
    public $projects = [];
    public $projectStats = [];
    public $selectedProject = null;
    public $showProjectModal = false;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadProjects();
    }

    public function render()
    {
        $reportData = $this->generateReportData();

        return view('livewire.reports.project-report', [
            'reportData' => $reportData,
        ])->layout('layouts.app');
    }

    private function loadProjects()
    {
        $this->projects = Project::where('status', 'active')->get();
    }

    private function generateReportData()
    {
        $query = Project::with(['tasks.assignments.user'])
            ->when($this->projectFilter, function ($q) {
                $q->where('id', $this->projectFilter);
            }) ->whereHas('tasks', function ($query) {
        $query->whereHas('assignments', function ($q) {
            $q->where('is_admin', 0);
        });
    });

        if ($this->reportType === 'summary') {
            return $this->generateSummaryReport($query);
        } elseif ($this->reportType === 'detailed') {
            return $this->generateDetailedReport($query);
        } else {
            return $this->generateAnalyticsReport($query);
        }
    }

    private function generateSummaryReport($query)
    {
        $projects = $query->get();
        $summaryData = [];

        foreach ($projects as $project) {
            $tasks = $project->tasks()
                ->when($this->dateFrom, function ($q) {
                    $q->whereHas('assignments', function ($sq) {
                        $sq->whereDate('start_date', '>=', $this->dateFrom);
                    });
                })
                ->when($this->dateTo, function ($q) {
                    $q->whereHas('assignments', function ($sq) {
                        $sq->whereDate('deadline', '<=', $this->dateTo);
                    });
                })
                ->when($this->statusFilter, function ($q) {
                    $q->where('task_status', $this->statusFilter);
                })
                ->get();

            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('task_status', 'completed')->count();
            $inProgressTasks = $tasks->where('task_status', 'in progress')->count();
            $pendingTasks = $tasks->where('task_status', 'pending')->count();
            $onHoldTasks = $tasks->where('task_status', 'on hold')->count();

            // Assignment statistics
            $assignments = $tasks->flatMap->assignments;
            $totalAssignments = $assignments->count();
            $completedAssignments = $assignments->where('status', 'Completed')->count();
            $overdue = $assignments->filter(function ($assignment) {
                return $assignment->deadline < Carbon::today() && 
                       !in_array($assignment->status, ['Completed', 'Reassigned']);
            })->count();

            // Calculate progress
            $overallProgress = $totalAssignments > 0 ? 
                round(($completedAssignments / $totalAssignments) * 100, 1) : 0;

            // Team statistics
            $uniqueUsers = $assignments->pluck('user_id')->unique()->count();
            
            // Time analysis
            $avgTaskDuration = $assignments->avg('no_of_days') ?? 0;
            
            // Efficiency metrics
            $onTimeCompletion = $assignments->filter(function ($assignment) {
                return $assignment->status === 'Completed' && 
                       $assignment->updated_at <= $assignment->deadline;
            })->count();
            
            $efficiencyRate = $completedAssignments > 0 ? 
                round(($onTimeCompletion / $completedAssignments) * 100, 1) : 0;

            $summaryData[] = [
                'project' => $project,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'in_progress_tasks' => $inProgressTasks,
                'pending_tasks' => $pendingTasks,
                'on_hold_tasks' => $onHoldTasks,
                'total_assignments' => $totalAssignments,
                'completed_assignments' => $completedAssignments,
                'overdue_assignments' => $overdue,
                'overall_progress' => $overallProgress,
                'team_size' => $uniqueUsers,
                'avg_task_duration' => round($avgTaskDuration, 1),
                'efficiency_rate' => $efficiencyRate,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
            ];
        }
         $this->dispatch('report_type_changed', 'summary');

        return [
            'type' => 'summary',
            'data' => collect($summaryData),
            'totals' => $this->calculateTotals($summaryData),
        ];
    }

    private function generateDetailedReport($query)
    {
        $projects = $query->paginate(10);
        $detailedData = [];

        foreach ($projects as $project) {
            $tasks = $project->tasks()
                ->with(['assignments.user'])
                ->when($this->dateFrom && $this->dateTo, function ($q) {
                    $q->whereHas('assignments', function ($sq) {
                        $sq->whereBetween('start_date', [$this->dateFrom, $this->dateTo])
                          ->orWhereBetween('deadline', [$this->dateFrom, $this->dateTo]);
                    });
                })
                ->when($this->statusFilter, function ($q) {
                    $q->where('task_status', $this->statusFilter);
                })
                ->get();

            $taskDetails = [];
            foreach ($tasks as $task) {
                $assignmentDetails = [];
                foreach ($task->assignments as $assignment) {
                    $assignmentDetails[] = [
                        'sequence' => $assignment->sequence_number,
                        'user' => $assignment->user,
                        'work_description' => $assignment->work_description,
                        'start_date' => $assignment->start_date,
                        'expected_date' => $assignment->expected_date,
                        'deadline' => $assignment->deadline,
                        'status' => $assignment->status,
                        'days' => $assignment->no_of_days,
                        'is_overdue' => $assignment->is_overdue,
                        'days_remaining' => $assignment->days_remaining,
                    ];
                }

                $taskDetails[] = [
                    'task' => $task,
                    'assignments' => $assignmentDetails,
                    'progress' => $task->overall_progress,
                ];
            }

            $detailedData[] = [
                'project' => $project,
                'tasks' => $taskDetails,
                'task_count' => $tasks->count(),
            ];
           
        }

         $this->dispatch('report_type_changed', 'detailed');
        return [
            'type' => 'detailed',
            'data' => $detailedData,
            'pagination' => $projects,
        ];
               

    }

    private function generateAnalyticsReport($query)
    {
        $projects = $query->get();
        $analyticsData = [];

        foreach ($projects as $project) {
            // Time-based analysis
            $monthlyProgress = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $monthlyTasks = $project->tasks()
                    ->whereHas('assignments', function ($q) use ($monthStart, $monthEnd) {
                        $q->whereBetween('start_date', [$monthStart, $monthEnd])
                          ->orWhereBetween('deadline', [$monthStart, $monthEnd]);
                    })
                    ->count();

                $monthlyCompleted = $project->tasks()
                    ->where('task_status', 'completed')
                    ->whereHas('assignments', function ($q) use ($monthStart, $monthEnd) {
                        $q->whereBetween('start_date', [$monthStart, $monthEnd])
                          ->orWhereBetween('deadline', [$monthStart, $monthEnd]);
                    })
                    ->count();

                $monthlyProgress[] = [
                    'month' => $date->format('M Y'),
                    'tasks' => $monthlyTasks,
                    'completed' => $monthlyCompleted,
                    'completion_rate' => $monthlyTasks > 0 ? round(($monthlyCompleted / $monthlyTasks) * 100, 1) : 0,
                ];
            }

            // User performance analysis
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

            // Status distribution
            $statusDistribution = [
                'pending' => $project->tasks->where('task_status', 'pending')->count(),
                'in_progress' => $project->tasks->where('task_status', 'in progress')->count(),
                'on_hold' => $project->tasks->where('task_status', 'on hold')->count(),
                'completed' => $project->tasks->where('task_status', 'completed')->count(),
                'not_completed' => $project->tasks->where('task_status', 'not completed')->count(),
            ];

            $analyticsData[] = [
                'project' => $project,
                'monthly_progress' => $monthlyProgress,
                'user_stats' => collect($userStats)->sortByDesc('completion_rate'),
                'status_distribution' => $statusDistribution,
                'key_metrics' => [
                    'total_tasks' => $project->tasks->count(),
                    'completion_rate' => $project->tasks->count() > 0 ? 
                        round(($project->tasks->where('task_status', 'completed')->count() / $project->tasks->count()) * 100, 1) : 0,
                    'avg_task_duration' => round($assignments->avg('no_of_days'), 1),
                    'overdue_rate' => $assignments->count() > 0 ? 
                        round(($assignments->filter->is_overdue->count() / $assignments->count()) * 100, 1) : 0,
                ],
            ];
        }

         $this->dispatch('report_type_changed', 'analytics');

        return [
            'type' => 'analytics',
            'data' => $analyticsData,
        ];
    }

    private function calculateTotals($summaryData)
    {
        return [
            'total_projects' => count($summaryData),
            'total_tasks' => array_sum(array_column($summaryData, 'total_tasks')),
            'total_completed' => array_sum(array_column($summaryData, 'completed_tasks')),
            'total_in_progress' => array_sum(array_column($summaryData, 'in_progress_tasks')),
            'total_pending' => array_sum(array_column($summaryData, 'pending_tasks')),
            'total_assignments' => array_sum(array_column($summaryData, 'total_assignments')),
            'avg_progress' => count($summaryData) > 0 ? 
                round(array_sum(array_column($summaryData, 'overall_progress')) / count($summaryData), 1) : 0,
            'avg_efficiency' => count($summaryData) > 0 ? 
                round(array_sum(array_column($summaryData, 'efficiency_rate')) / count($summaryData), 1) : 0,
        ];
    }

    public function exportReport($format = 'excel')
    {
        $reportData = $this->generateReportData();
        
        // This would integrate with Laravel Excel or similar
        // For now, we'll just trigger a download
        $this->dispatch('downloadReport', [
            'type' => 'project',
            'format' => $format,
            'data' => $reportData,
            'filters' => [
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'project' => $this->projectFilter,
                'status' => $this->statusFilter,
                'reportType' => $this->reportType,
            ]
        ]);
    }

    public function viewProjectDetails($projectId)
    {
        $this->selectedProject = Project::with(['tasks.assignments.user'])->find($projectId);
        $this->showProjectModal = true;
    }

    public function closeProjectModal()
    {
        $this->showProjectModal = false;
        $this->selectedProject = null;
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

    public function updatedProjectFilter()
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
        $this->projectFilter = '';
        $this->statusFilter = '';
        $this->reportType = 'analytics';
        $this->resetPage();
    }
}