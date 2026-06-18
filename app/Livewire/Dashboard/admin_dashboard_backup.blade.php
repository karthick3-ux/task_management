<?php

namespace App\Livewire\Dashboard;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Models\Department;
use App\Models\TaskAssignment;
use App\Models\TaskUpdateHistory;
use App\Services\TaskHistoryService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class AdminDashboard extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    // Search and Filter Properties
    public $search = '';
    public $projectFilter = '';
    public $userFilter = '';
    public $statusFilter = '';
    public $urgencyFilter = '';
    public $dateFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    // Modal Properties
    public $showUserTasksModal = false;
    public $selectedUser = null;
    public $selectedDate = '';
    public $userTasks = [];

    // Edit Modal Properties
    public $showEditModal = false;
    public $editingTask = null;
    public $editForm = [
        'task_name' => '',
        'project_id' => '',
        'task_status' => '',
        'feedback' => ''
    ];
    public $originalTaskData = [];

    // View Modal Properties
    public $showViewModal = false;
    public $viewingTask = null;

    // Update History Modal Properties
    public $showUpdateHistoryModal = false;
    public $taskUpdateHistory = [];
    public $historyTaskId = null;

    // Create Task Modal Properties with Assignments
    public $showCreateTaskModal = false;
    public $taskName = '';
    public $selectedProjectId = '';
    public $taskAssignments = [];

    // Data Collections
    public $projects = [];
    public $projects_all = [];
    public $users = [];
    public $departments = [];

    // Calendar Events
    public $calendarEvents = [];

    // Statistics
    public $totalTasks = 0;
    public $pendingTasks = 0;
    public $progressTasks = 0;
    public $completedTasks = 0;
    public $overdueTasks = 0;

    // Display Mode
    public $displayMode = 'split'; // 'split', 'table-full', 'calendar-full'

    protected $listeners = [
        'refreshDashboard' => '$refresh',
        'showUserTasks' => 'openUserTasksModal'
    ];

    protected TaskHistoryService $taskHistoryService;


      public $showAssignmentManagementModal = false;
    public $managingTask = null;
    public $assignmentData = [];
    public $originalAssignmentData = [];



    public function boot(TaskHistoryService $taskHistoryService)
    {
        $this->taskHistoryService = $taskHistoryService;
    }



    protected $rules = [
    'taskName' => 'required|string|max:255',
    'selectedProjectId' => 'required|exists:projects,id',
    'taskAssignments' => 'required|array|min:1',
    'taskAssignments.*.user_id' => 'required|exists:users,id',
    'taskAssignments.*.work_description' => 'required|string|max:500',
    'taskAssignments.*.no_of_days' => 'nullable|integer|min:0',
    'taskAssignments.*.start_date' => 'nullable|date',
    'taskAssignments.*.expected_date' => 'nullable|date',
    'taskAssignments.*.deadline' => 'nullable|date',
    'taskAssignments.*.status' => 'required|in:Pending,Inprogress,Reassigned,Completed,Not Completed',
];

/**
 * Custom validation messages
 */
protected $messages = [
    'taskAssignments.*.no_of_days.required' => 'Number of days is required for each assignment.',
    'taskAssignments.*.no_of_days.min' => 'Number of days must be at least 1.',
];
    public function mount()
    {
        $this->authorize('view_tasks');
        $this->loadData();
        $this->initializeTaskAssignments();
        $this->updateStatistics();
        $this->loadCalendarEvents();
    }

    public function render()
    {
        $tasksQuery = Task::with(['project', 'assignments.user'])
            ->when($this->search, function ($query) {
                $query->where('task_name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('project', function ($q) {
                          $q->where('project_name', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->projectFilter, function ($query) {
                $query->where('project_id', $this->projectFilter);
            })
            ->when($this->userFilter, function ($query) {
                $query->whereHas('assignments', function ($q) {
                    $q->where('user_id', $this->userFilter);
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('task_status', $this->statusFilter);
            })
            ->when($this->dateFilter, function ($query) {
                switch ($this->dateFilter) {
                    case 'today':
                        $query->whereHas('assignments', function ($q) {
                            $q->whereDate('deadline', Carbon::today());
                        });
                        break;
                    case 'this_week':
                        $query->whereHas('assignments', function ($q) {
                            $q->whereBetween('deadline', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                        });
                        break;
                    case 'this_month':
                        $query->whereHas('assignments', function ($q) {
                            $q->whereMonth('deadline', Carbon::now()->month);
                        });
                        break;
                    case 'overdue':
                        $query->whereHas('assignments', function ($q) {
                            $q->where('deadline', '<', Carbon::today())
                              ->whereNotIn('status', ['Completed']);
                        });
                        break;
                }
            })->whereHas('assignments.user.roles', function ($q) {
            $q->where('name', 'super_admin');
        }) ->whereDoesntHave('assignments.user.roles', function ($q) {
            $q->where('name', '!=', 'super_admin');
        });

        $tasks = $tasksQuery->orderBy($this->sortBy, $this->sortDirection)
                           ->paginate($this->displayMode === 'table-full' ? 15 : 6);

        $this->updateStatistics();
        $this->loadCalendarEvents();

        return view('livewire.dashboard.admin-dashboard', [
            'tasks' => $tasks,
        ])->layout('layouts.app');
    }

    // Display Mode Methods
    public function setDisplayMode($mode)
    {
        $this->displayMode = $mode;
    }



   

    public function moveAssignmentUp($index)
    {
        if ($index > 0) {
            $temp = $this->taskAssignments[$index];
            $this->taskAssignments[$index] = $this->taskAssignments[$index - 1];
            $this->taskAssignments[$index - 1] = $temp;
        }
    }

    public function moveAssignmentDown($index)
    {
        if ($index < count($this->taskAssignments) - 1) {
            $temp = $this->taskAssignments[$index];
            $this->taskAssignments[$index] = $this->taskAssignments[$index + 1];
            $this->taskAssignments[$index + 1] = $temp;
        }
    }

    // Create Task Methods
    public function openCreateTaskModal()
    {
        $this->authorize('create_tasks');
        $this->resetTaskForm();
        $this->loadData();
        $this->showCreateTaskModal = true;
    }

   

    private function resetTaskForm()
    {
        $this->taskName = '';
        $this->selectedProjectId = '';
        $this->initializeTaskAssignments();
        $this->resetValidation();
        
        // Emit event to reset Select2
        $this->dispatch('closeModal');
    }

    // Assignment Management Methods
    public function removeUserFromAssignment($taskId, $assignmentId)
    {
        $this->authorize('edit_tasks');
        
        $task = Task::with('assignments')->findOrFail($taskId);
        
        // Check if user can edit this task
        if (!auth()->user()->isSuperAdmin()) {
            $hasAccess = $task->assignments->contains(function ($assignment) {
                return $assignment->user_id == auth()->id();
            });
            if (!$hasAccess) {
                session()->flash('error', 'You can only modify tasks assigned to you.');
                return;
            }
        }

        try {
            $task->removeAssignment($assignmentId);
            
            session()->flash('success', 'Assignment removed successfully!');
            $this->dispatch('refreshDashboard');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove assignment.');
        }
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->projectFilter = '';
        $this->userFilter = '';
        $this->statusFilter = '';
        $this->urgencyFilter = '';
        $this->dateFilter = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function openUserTasksModal($userId, $date)
    {
        $this->selectedUser = User::findOrFail($userId);
        $this->selectedDate = $date;
        
        $this->userTasks = Task::with(['project', 'assignments'])
            ->whereHas('assignments', function ($query) use ($userId, $date) {
                $query->where('user_id', $userId)
                      ->whereDate('start_date', '<=', $date)
                      ->whereDate('deadline', '>=', $date);
            })
            ->get();

        $this->showUserTasksModal = true;
    }

    public function editTask($taskId)
    {
        $this->authorize('edit_tasks');
        
        $task = Task::with(['project', 'assignments'])->findOrFail($taskId);
        
        // Check if user can edit this task
        if (!auth()->user()->isSuperAdmin()) {
            $hasAccess = $task->assignments->contains(function ($assignment) {
                return $assignment->user_id == auth()->id();
            });
            if (!$hasAccess) {
                session()->flash('error', 'You can only edit tasks assigned to you.');
                return;
            }
        }

        $this->editingTask = $task;
        $this->editForm = [
            'task_name' => $task->task_name,
            'project_id' => $task->project_id,
            'task_status' => $task->task_status,
            'feedback' => $task->feedback ?? ''
        ];

        // Store original data for comparison
        $this->originalTaskData = $this->editForm;
        $this->showEditModal = true;
    }

    public function updateTask()
    {
        $this->validate([
            'editForm.task_name' => 'required|string|max:255',
            'editForm.project_id' => 'required|exists:projects,id',
            'editForm.task_status' => 'required|in:pending,in progress,on hold,completed,not completed',
            'editForm.feedback' => 'nullable|string|max:1000',
        ]);

        $task = $this->editingTask;
        
        // Use the service to track changes
        $this->taskHistoryService->trackTaskChanges($task, $this->originalTaskData, $this->editForm);

        // Update task
        $task->update([
            'task_name' => $this->editForm['task_name'],
            'project_id' => $this->editForm['project_id'],
            'task_status' => $this->editForm['task_status'],
            'feedback' => $this->editForm['feedback']
        ]);

        $this->showEditModal = false;
        $this->reset(['editingTask', 'editForm', 'originalTaskData']);
        session()->flash('success', 'Task updated successfully!');
    }

    public function viewTask($taskId)
    {
        $this->authorize('view_tasks');
        
        $task = Task::with(['project', 'assignments.user'])->findOrFail($taskId);
        
        // Check if user can view this task
        if (!auth()->user()->isSuperAdmin()) {
            $hasAccess = $task->assignments->contains(function ($assignment) {
                return $assignment->user_id == auth()->id();
            });
            if (!$hasAccess) {
                session()->flash('error', 'You can only view tasks assigned to you.');
                return;
            }
        }

        $this->viewingTask = $task;
        $this->showViewModal = true;
    }

    public function deleteTask($taskId)
    {
        $this->authorize('delete_tasks');
        
        $task = Task::with('assignments')->findOrFail($taskId);
        
        // Check if user can delete this task
        if (!auth()->user()->isSuperAdmin()) {
            $hasAccess = $task->assignments->contains(function ($assignment) {
                return $assignment->user_id == auth()->id();
            });
            if (!$hasAccess) {
                session()->flash('error', 'You can only delete tasks assigned to you.');
                return;
            }
        }

        // Log deletion using service
        $this->taskHistoryService->logTaskDeletion($task);

        $task->delete();
        session()->flash('success', 'Task deleted successfully!');
    }

    public function showUpdateHistory($taskId)
    {
        $this->authorize('view_tasks');
        
        $task = Task::with('assignments')->findOrFail($taskId);
        
        // Check if user can view this task history
        if (!auth()->user()->isSuperAdmin()) {
            $hasAccess = $task->assignments->contains(function ($assignment) {
                return $assignment->user_id == auth()->id();
            });
            if (!$hasAccess) {
                session()->flash('error', 'You can only view update history for tasks assigned to you.');
                return;
            }
        }

        $this->historyTaskId = $taskId;
        $this->taskUpdateHistory = $this->taskHistoryService->getTaskHistory($taskId);
        
        $this->showUpdateHistoryModal = true;
    }

    private function loadData()
    {
        $this->projects = Project::where('status', 'active')->get();
        $this->projects_all = Project::where('status', 'active')->get();
        $this->users = User::get();
        $this->departments = Department::where('is_active', true)->get();
    }

    private function updateStatistics()
    {
        $query = Task::query();
        
        // If not super admin, only count tasks assigned to current user
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('assignments', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }
        
        $this->totalTasks = (clone $query)->count();
        $this->pendingTasks = (clone $query)->where('task_status', 'pending')->count();
        $this->progressTasks = (clone $query)->where('task_status', 'in progress')->count();
        $this->completedTasks = (clone $query)->where('task_status', 'completed')->count();
        $this->overdueTasks = (clone $query)->whereHas('assignments', function ($q) {
            $q->where('deadline', '<', Carbon::today())
              ->whereNotIn('status', ['Completed']);
        })->count();
    }

   private function loadCalendarEvents()
{
    $startDate = Carbon::now()->startOfMonth()->subWeeks(2);
    $endDate = Carbon::now()->endOfMonth()->addWeeks(2);

    // Get all assignments with their tasks within the date range
    $assignments = TaskAssignment::with(['task.project', 'user'])
        ->whereBetween('deadline', [$startDate, $endDate])
         ->whereHas('task', function ($query) {
        $query->whereHas('assignments.user.roles', function ($q) {
            $q->where('name', 'super_admin');
        })
        ->whereDoesntHave('assignments.user.roles', function ($q) {
            $q->where('name', '!=', 'super_admin');
        });
    })
        ->get();

    // Group assignments by date and task
    $tasksByDate = [];
    $events = [];
    
    foreach ($assignments as $assignment) {
        $startDateStr = $assignment->deadline->format('Y-m-d');
        $task = $assignment->task;
        
        if (!isset($tasksByDate[$startDateStr])) {
            $tasksByDate[$startDateStr] = [];
        }
        
        if (!isset($tasksByDate[$startDateStr][$task->id])) {
            $tasksByDate[$startDateStr][$task->id] = [
                'task' => $task,
                'assignments' => [],
                'users' => [],
                'user_colors' => [],
                'active_users' => [] // Users with pending/in-progress assignments
            ];
        }
        
        $tasksByDate[$startDateStr][$task->id]['assignments'][] = $assignment;
        $tasksByDate[$startDateStr][$task->id]['users'][] = $assignment->user;
        
        // Only include user colors for pending/in-progress assignments
        // if (in_array($assignment->status, ['Pending', 'Inprogress'])) {
            $tasksByDate[$startDateStr][$task->id]['user_colors'][] = $assignment->user->color;
            $tasksByDate[$startDateStr][$task->id]['active_users'][] = $assignment->user;
       // }
    }
    
    // Create calendar events from grouped data
    foreach ($tasksByDate as $date => $tasks) {
        foreach ($tasks as $taskId => $taskData) {
            $task = $taskData['task'];
            $assignmentCount = count($taskData['assignments']);
            $activeUserColors = array_unique($taskData['user_colors']);
            $activeUsers = collect($taskData['active_users'])->unique('id');
            
            // Determine if any assignment is overdue
            $hasOverdue = false;
            foreach ($taskData['assignments'] as $assignment) {
                if ($assignment->deadline < Carbon::today() && 
                    !in_array($assignment->status, ['Completed', 'Reassigned'])) {
                    $hasOverdue = true;
                    break;
                }
            }
            
            // Create event background color (default gray, red if overdue)
            $backgroundColor = $hasOverdue ? '#dc3545' : '#6c757d';
            $borderColor = $hasOverdue ? '#dc3545' : '#6c757d';
            
            // Create the event
            if(count($taskData['user_colors'])>0){
            $events[] = [
                'id' => $date . '_task_' . $taskId,
                'title' => $task->task_name,
                'start' => $date,
                'backgroundColor' => $backgroundColor,
                'borderColor' => $borderColor,
                'textColor' => '#fff',
                'extendedProps' => [
                    'taskId' => $task->id,
                    'taskName' => $task->task_name,
                    'projectName' => $task->project->project_name,
                    'assignmentCount' => $assignmentCount,
                    'hasOverdue' => $hasOverdue,
                    'activeUserColors' => array_values($activeUserColors),
                    'activeUsers' => $activeUsers->pluck('name')->toArray(),
                    'allUsers' => collect($taskData['users'])->unique('id')->pluck('name')->toArray(),
                    'taskStatus' => $task->task_status,
                    'total_task'=>count($taskData['user_colors']),
                    'assignmentStatuses' => collect($taskData['assignments'])->pluck('status')->unique()->toArray()
                ]
            ];
        }
        }
    }

    $this->calendarEvents = $events;
}
    // Rest of the existing methods remain the same...
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingProjectFilter()
    {
        $this->resetPage();
    }

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingUrgencyFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function updatingSortBy()
    {
        $this->resetPage();
    }

    public function updatingSortDirection()
    {
        $this->resetPage();
    }

      public function openAssignmentModal($taskId)
    {
        $this->authorize('view_tasks');
        
        $task = Task::with(['project', 'assignments.user'])->findOrFail($taskId);
        
        // Check if user can manage this task
        if (!auth()->user()->isSuperAdmin()) {
            $hasAccess = $task->assignments->contains(function ($assignment) {
                return $assignment->user_id == auth()->id();
            });
            if (!$hasAccess) {
                session()->flash('error', 'You can only manage assignments for tasks assigned to you.');
                return;
            }
        }

        $this->managingTask = $task;
        $this->loadAssignmentData();
        $this->showAssignmentManagementModal = true;
    }

    public function closeAssignmentModal()
    {
        $this->showAssignmentManagementModal = false;
        $this->managingTask = null;
        $this->assignmentData = [];
        $this->originalAssignmentData = [];
        $this->resetValidation();
    }


  



    public function moveAssignmentUp2($index)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return;
        }

        if ($index > 0) {
            $temp = $this->assignmentData[$index];
            $this->assignmentData[$index] = $this->assignmentData[$index - 1];
            $this->assignmentData[$index - 1] = $temp;
            
            // Update sequence numbers
            $this->assignmentData[$index]['sequence_number'] = $index + 1;
            $this->assignmentData[$index - 1]['sequence_number'] = $index;
        }
    }

    public function moveAssignmentDown2($index)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return;
        }

        if ($index < count($this->assignmentData) - 1) {
            $temp = $this->assignmentData[$index];
            $this->assignmentData[$index] = $this->assignmentData[$index + 1];
            $this->assignmentData[$index + 1] = $temp;
            
            // Update sequence numbers
            $this->assignmentData[$index]['sequence_number'] = $index + 1;
            $this->assignmentData[$index + 1]['sequence_number'] = $index + 2;
        }
    }

   
   
    public function canAssignmentStart($index)
    {
        // Super admin can start any assignment
        if (auth()->user()->isSuperAdmin()) {
            return true;
        }

        // First assignment can always start
        if ($index === 0) {
            return true;
        }

        // Check if previous assignment is completed
        $previousAssignment = $this->assignmentData[$index - 1];
        return isset($previousAssignment['status']) && $previousAssignment['status'] === 'Completed';
    }

    

 
    // Method to check if user can edit specific assignment
    private function canEditAssignment($assignment)
    {
        // Super admin can edit any assignment
        if (auth()->user()->isSuperAdmin()) {
            return true;
        }

        // User can edit only their own assignments
        if (isset($assignment['user_id']) && $assignment['user_id'] == auth()->id()) {
            return true;
        }

        return false;
    }

    // Method to check if assignment can be modified based on status and task state
    private function canModifyAssignment($assignment)
    {
        // Cannot modify if task is on hold
        if ($this->managingTask->task_status === 'on hold') {
            return false;
        }

        // Cannot modify completed assignments
        if (isset($assignment['status']) && $assignment['status'] === 'Completed') {
            return false;
        }

        return true;
    }

    // Method to get assignment statistics
    public function getAssignmentStats()
    {
        $stats = [
            'total' => count($this->assignmentData),
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'reassigned' => 0,
            'not_completed' => 0,
            'overdue' => 0
        ];

        foreach ($this->assignmentData as $assignment) {
            $status = $assignment['status'] ?? 'Pending';
            
            switch ($status) {
                case 'Completed':
                    $stats['completed']++;
                    break;
                case 'Inprogress':
                    $stats['in_progress']++;
                    break;
                case 'Reassigned':
                    $stats['reassigned']++;
                    break;
                case 'Not Completed':
                    $stats['not_completed']++;
                    break;
                default:
                    $stats['pending']++;
            }

            // Check if overdue
            if (isset($assignment['deadline']) && 
                $assignment['deadline'] < now()->format('Y-m-d') && 
                !in_array($status, ['Completed', 'Reassigned'])) {
                $stats['overdue']++;
            }
        }

        return $stats;
    }

    // Handler for assignment updates
    public function handleAssignmentUpdate()
    {
        $this->loadData();
        $this->updateStatistics();
        $this->loadCalendarEvents();
    }

   public function saveAssignments()
    {
        // Validate assignment data
        $this->validateAssignments();

        try {
            \DB::transaction(function () {
                // Disable automatic history logging during bulk update
                TaskAssignment::$disableHistoryLogging = true;
                
              
                foreach ($this->assignmentData as $index => $assignmentData) {
                    if (isset($assignmentData['id']) && $assignmentData['id']) {
                        // Update existing assignment
                        $assignment = TaskAssignment::find($assignmentData['id']);
                        if ($assignment) {
                            // Check if user can edit this assignment
                            $canEdit = auth()->user()->isSuperAdmin() || 
                                      $assignment->user_id == auth()->id();
                             $originalValues = $assignment->getOriginal();

                            if ($canEdit) {
                                // Get original values before update
                              
                                if($assignmentData['status']=='Completed'){
                                    if($originalValues['status']!='Completed'){
                                       $doc=date('Y-m-d');
                                       $assignment->update(['doc'=>$doc]);
                                    }
                                  
                                }
                               

                                // Update the assignment normally
                                $assignment->update([
                                    'sequence_number' => $assignmentData['sequence_number'],
                                    'user_id' => $assignmentData['user_id'],
                                    'work_description' => $assignmentData['work_description'],
                                    'start_date' => !empty($assignmentData['start_date']) ? $assignmentData['start_date'] : null,
                                    'expected_date' =>!empty($assignmentData['expected_date']) ? $assignmentData['expected_date'] : null,
                                    'deadline' => !empty($assignmentData['deadline']) ? $assignmentData['deadline'] : null,
                                    'status' => $assignmentData['status'],
                                    'no_of_days' => !empty($assignmentData['no_of_days'])?$assignmentData['no_of_days']:null,

                                ]);

                                // Manual history logging for bulk update (only actual changes)
                                $this->logIndividualAssignmentChanges($assignment, $originalValues, $assignmentData);

                                // Recalculate days
                            }
                        }
                    } else {
                        // Create new assignment
                        if (auth()->user()->can('manage_task_assignments')) {
                            $newAssignment = $this->managingTask->addAssignment([
                                'sequence_number' => $assignmentData['sequence_number'],
                                'user_id' => $assignmentData['user_id'],
                                'work_description' => $assignmentData['work_description'],
                                 'start_date' => !empty($assignmentData['start_date']) ? $assignmentData['start_date'] : null,
                                    'expected_date' =>!empty($assignmentData['expected_date']) ? $assignmentData['expected_date'] : null,
                                    'deadline' => !empty($assignmentData['deadline']) ? $assignmentData['deadline'] : null,
                                'status' => $assignmentData['status'],
                                'no_of_days' => $assignmentData['no_of_days'],
                                'is_admin'=>1,

                            ]);
                            
                            // The creation is automatically logged by the model
                        }
                    }
                }

                // Re-enable automatic history logging
                TaskAssignment::$disableHistoryLogging = false;

                
                // Update overall task status
                $this->managingTask->updateOverallStatus();

                // // Log one summary entry for the bulk update
                // $this->taskHistoryService->logChange(
                //     $this->managingTask->id,
                //     'assignments_updated',
                //     'Task assignments were updated by ' . auth()->user()->name
                // );
            });

          

            session()->flash('success', 'Assignments updated successfully!');
            $this->closeAssignmentModal();
            $this->dispatch('refreshDashboard');

        } catch (\Exception $e) {
            // Re-enable history logging in case of error
            TaskAssignment::$disableHistoryLogging = false;
            session()->flash('error', 'Failed to update assignments: ' . $e->getMessage());
        }
    }

    // Helper method to log individual assignment changes with proper change detection
    private function logIndividualAssignmentChanges($assignment, $originalValues, $newData)
    {
        $historyService = $this->taskHistoryService;
        $changedBy = auth()->user();

        // Track status changes
        if (isset($originalValues['status']) && isset($newData['status']) && 
            $originalValues['status'] !== $newData['status']) {
            $historyService->logAssignmentStatusChange(
                $assignment, 
                $originalValues['status'], 
                $newData['status'], 
                $changedBy
            );
        }

        // Track user changes
        if (isset($originalValues['user_id']) && isset($newData['user_id']) && 
            $originalValues['user_id'] != $newData['user_id']) {
            $historyService->logAssignmentUserChange(
                $assignment,
                $originalValues['user_id'],
                $newData['user_id'],
                $changedBy
            );
        }

        // Track work description changes
        if (isset($originalValues['work_description']) && isset($newData['work_description']) && 
            trim($originalValues['work_description']) !== trim($newData['work_description'])) {
            $historyService->logAssignmentWorkDescriptionChange(
                $assignment,
                $originalValues['work_description'],
                $newData['work_description'],
                $changedBy
            );
        }

         if (empty($originalValues['start_date']) && !empty($newData['start_date'])) {
            $historyService->logAssignmentStart(
                $assignment,
            );
        }

        // Track date changes with proper date comparison
        $dateFields = ['start_date', 'expected_date', 'deadline'];
        foreach ($dateFields as $field) {
            if (isset($originalValues[$field]) && isset($newData[$field])) {
                // Normalize dates for comparison
                $originalDate = $this->normalizeDateForComparison($originalValues[$field]);
                $newDate = $this->normalizeDateForComparison($newData[$field]);
                
                if ($originalDate !== $newDate) {
                    $historyService->logAssignmentDateChange(
                        $assignment,
                        $field,
                        $originalValues[$field],
                        $newData[$field],
                        $changedBy
                    );
                }
            }
        }
    }

    // Helper method to normalize dates for comparison
    private function normalizeDateForComparison($date)
    {
        if (empty($date) || $date === null) {
            return null;
        }

        try {
            // Convert to Carbon and format as Y-m-d for comparison
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('Y-m-d');
            }
            
            if (is_string($date)) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function handleStatusChange($index, $newStatus)
    {
        // Check if user can edit this assignment
        $assignment = $this->assignmentData[$index];
        $canEdit = auth()->user()->isSuperAdmin() || 
                   (isset($assignment['user_id']) && $assignment['user_id'] == auth()->id());

        if (!$canEdit) {
            session()->flash('error', 'You can only edit your own assignments.');
            return;
        }

        // Check if task is on hold
        if ($this->managingTask->task_status === 'on hold') {
            session()->flash('error', 'Cannot change status when task is on hold.');
            return;
        }

        // Check if assignment is already completed
        // if (isset($assignment['status']) && $assignment['status'] === 'Completed') {
        //     session()->flash('error', 'Cannot change status of completed assignments.');
        //     return;
        // }

        // Check if previous assignment is completed (for non-superadmins)
        if (!auth()->user()->isSuperAdmin() && !$this->canAssignmentStart($index)) {
            session()->flash('error', 'Previous assignment must be completed first.');
            return;
        }

        // Store old status for history
        $oldStatus = $assignment['status'] ?? 'Pending';

        // Handle reassignment
        if ($newStatus === 'Reassigned') {
            $this->createReassignment($index);
            return;
        }

        // Update the status
        $this->assignmentData[$index]['status'] = $newStatus;

        // If this is an existing assignment, log the change immediately
        // if (isset($assignment['id']) && $assignment['id']) {
        //     $assignmentModel = TaskAssignment::find($assignment['id']);
        //     if ($assignmentModel) {
        //         // Log the status change
        //         $this->taskHistoryService->logAssignmentStatusChange(
        //             $assignmentModel,
        //             $oldStatus,
        //             $newStatus,
        //             auth()->user()
        //         );
        //     }
        // }

        //session()->flash('success', 'Status updated successfully.');
    }

    private function createReassignment($index)
    {
        $originalAssignment = $this->assignmentData[$index];
        
        // Mark original as reassigned
        $this->assignmentData[$index]['status'] = 'Reassigned';
        
        // Create new assignment with same details
        $nextSequence = count($this->assignmentData) + 1;

        $newAssignment = [
            'id' => null, // New assignment
            'sequence_number' => $nextSequence, // Next sequence
            'user_id' => $originalAssignment['user_id'],
            'work_description' => $originalAssignment['work_description'],
            'start_date' => $originalAssignment['start_date'],
            'expected_date' => $originalAssignment['expected_date'],
            'deadline' => $originalAssignment['deadline'],
            'status' => 'Pending',
            'no_of_days' => null,
        ];

        $this->assignmentData[] = $newAssignment;

        // If this is an existing assignment, log the reassignment
        if (isset($originalAssignment['id']) && $originalAssignment['id']) {
            $assignmentModel = TaskAssignment::find($originalAssignment['id']);
            if ($assignmentModel) {
                // Log the reassignment
                $this->taskHistoryService->logChange(
                    $this->managingTask->id,
                    'assignment_reassigned',
                    "Assignment #{$originalAssignment['sequence_number']} was reassigned by " . auth()->user()->name . ". New assignment #{$nextSequence} created."
                );
            }
        }

        session()->flash('success', 'Assignment reassigned successfully. New assignment created.');
    }

    // Enhanced method to track date changes in real-time
    public function updatedAssignmentData($value, $key)
    {
        // Parse the key to get index and field
        $keyParts = explode('.', $key);
        if (count($keyParts) === 2) {
            $index = $keyParts[0];
            $field = $keyParts[1];
            
            // Only track changes for existing assignments with dates
            if (isset($this->assignmentData[$index]['id']) && 
                $this->assignmentData[$index]['id'] && 
                in_array($field, ['start_date', 'expected_date', 'deadline'])) {
                
                $assignment = $this->assignmentData[$index];
                $originalValue = $this->originalAssignmentData[$index][$field] ?? null;
                
                // Check if user can edit this assignment
                $canEdit = auth()->user()->isSuperAdmin() || 
                           (isset($assignment['user_id']) && $assignment['user_id'] == auth()->id());
                
                if ($canEdit && $originalValue !== $value) {
                    $assignmentModel = TaskAssignment::find($assignment['id']);
                    if ($assignmentModel) {
                        // Log the date change
                        $this->taskHistoryService->logAssignmentDateChange(
                            $assignmentModel,
                            $field,
                            $originalValue,
                            $value,
                            auth()->user()
                        );
                    }
                }
            }
        }
    }

    // Method to get comprehensive assignment history
    public function getAssignmentHistory($taskId)
    {
        return $this->taskHistoryService->getTaskHistory($taskId)
            ->filter(function ($history) {
                return in_array($history->type, [
                    'assignment_added',
                    'assignment_removed',
                    'assignment_status_changed',
                    'assignment_user_changed',
                    'assignment_date_changed',
                    'assignment_work_description_changed',
                    'assignment_reassigned',
                    'assignment_completed',
                    'assignment_created',
                    'assignment_deleted'
                ]);
            });
    }

    // Method to get assignment statistics with history insights
    public function getDetailedAssignmentStats()
    {
        $stats = $this->getAssignmentStats();
        
        // Add history-based statistics
        $history = $this->getAssignmentHistory($this->managingTask->id);
        
        $stats['recent_changes'] = $history->take(5);
        $stats['total_changes'] = $history->count();
        $stats['reassignments'] = $history->where('type', 'assignment_reassigned')->count();
        $stats['status_changes'] = $history->where('type', 'assignment_status_changed')->count();
        $stats['date_changes'] = $history->where('type', 'assignment_date_changed')->count();
        
        return $stats;
    }

    // Helper methods for enhanced history display
    public function getHistoryBorderColor($type)
    {
        $colors = [
            'create' => 'success',
            'delete' => 'danger',
            'assignment_created' => 'success',
            'assignment_added' => 'success',
            'assignment_status_changed' => 'info',
            'assignment_reassigned' => 'warning',
            'assignment_completed' => 'success',
            'assignment_user_changed' => 'primary',
            'assignment_date_changed' => 'secondary',
            'assignment_work_description_changed' => 'info',
            'assignment_deleted' => 'danger',
            'assignment_removed' => 'danger',
            'status_change' => 'info',
            'update_task_name' => 'warning',
            'update_project_id' => 'primary',
            'update_feedback' => 'info',
            'assignments_updated' => 'success',
        ];
        
        return $colors[$type] ?? 'info';
    }

    public function getHistoryBadgeColor($type)
    {
        return $this->getHistoryBorderColor($type);
    }

    public function getHistoryIcon($type)
    {
        $icons = [
            'create' => 'plus-circle',
            'delete' => 'trash',
            'assignment_created' => 'plus-circle',
            'assignment_added' => 'plus-circle',
            'assignment_status_changed' => 'refresh',
            'assignment_reassigned' => 'reply',
            'assignment_completed' => 'check-circled',
            'assignment_user_changed' => 'user',
            'assignment_date_changed' => 'calendar',
            'assignment_work_description_changed' => 'edit',
            'assignment_deleted' => 'trash',
            'assignment_removed' => 'trash',
            'status_change' => 'refresh',
            'update_task_name' => 'edit',
            'update_project_id' => 'building',
            'update_feedback' => 'comment',
            'assignments_updated' => 'check-circled',
            'user_assigned' => 'user-plus',
            'user_unassigned' => 'user-minus',
        ];
        
        return $icons[$type] ?? 'info-circle';
    }

    public function getHistoryTypeLabel($type)
    {
        $labels = [
            'create' => 'Created',
            'delete' => 'Deleted',
            'assignment_created' => 'Assignment Created',
            'assignment_added' => 'Assignment Added',
            'assignment_status_changed' => 'Status Changed',
            'assignment_reassigned' => 'Reassigned',
            'assignment_completed' => 'Completed',
            'assignment_user_changed' => 'User Changed',
            'assignment_date_changed' => 'Date Changed',
            'assignment_work_description_changed' => 'Description Updated',
            'assignment_deleted' => 'Assignment Deleted',
            'assignment_removed' => 'Assignment Removed',
            'status_change' => 'Status Changed',
            'update_task_name' => 'Name Updated',
            'update_project_id' => 'Project Changed',
            'update_feedback' => 'Feedback Updated',
            'assignments_updated' => 'Assignments Updated',
            'user_assigned' => 'User Assigned',
            'user_unassigned' => 'User Removed',
        ];
        
        return $labels[$type] ?? ucfirst(str_replace(['_', 'update_'], [' ', ''], $type));
    }

    public function isAssignmentHistory($type)
    {
        return str_contains($type, 'assignment');
    }


    public function initializeTaskAssignments()
{
    $this->taskAssignments = [
        [
            'user_id' => auth()->user()->can('assign_task_users') ? '' : auth()->id(),
            'work_description' => '',
            'no_of_days' => '',
            'start_date' => '',
            'expected_date' => '',
            'deadline' => '',
            'status' => 'Pending'
        ]
    ];
}

/**
 * Add new task assignment
 */
public function addTaskAssignment($afterIndex = null)
{
    $newAssignment = [
        'user_id' => auth()->user()->can('assign_task_users') ? '' : auth()->id(),
        'work_description' => '',
        'no_of_days' => '',
        'start_date' => '',
        'expected_date' => '',
        'deadline' => '',
        'status' => 'Pending'
    ];

    if ($afterIndex !== null) {
        array_splice($this->taskAssignments, $afterIndex + 1, 0, [$newAssignment]);
    } else {
        $this->taskAssignments[] = $newAssignment;
    }
    
    // Recalculate all dates after adding new assignment
    $this->recalculateSequentialDates();
}

/**
 * Calculate dates when number of days is entered for any assignment
 */
public function calculateDatesFromDays($index)

{
    // dd($this->taskAssignments);
     if (!isset($this->taskAssignments[$index]['no_of_days']) || 
        $this->taskAssignments[$index]['no_of_days'] < 0) {
        return;
    }

    $days = (int) $this->taskAssignments[$index]['no_of_days'];
    
    if ($index === 0) {
        // First assignment: Keep existing dates if they exist, otherwise user needs to set them
        // The start date, expected date, and deadline should remain editable for first assignment
        
        // If first assignment has start date, set next row's start date
        if (!empty($this->taskAssignments[0]['expected_date']) && isset($this->taskAssignments[1])) {
            $startDate = Carbon::parse($this->taskAssignments[0]['expected_date']);
            $nextStartDate = $startDate->copy()->addDays($days);
            $this->taskAssignments[1]['expected_date'] = $nextStartDate->format('Y-m-d');
            
            // Copy start date and deadline from first assignment to next row
            if (!empty($this->taskAssignments[0]['start_date'])) {
                $this->taskAssignments[1]['start_date'] = $this->taskAssignments[0]['start_date'];
            }
            if (!empty($this->taskAssignments[0]['deadline'])) {
                $this->taskAssignments[1]['deadline'] = $this->taskAssignments[0]['deadline'];
            }
        }
        
        return;
    } else {
        // For subsequent assignments: Calculate start date from previous assignment
        $prevStartDate = null;
        
        // Get the previous assignment's start date + days
        for ($i = $index - 1; $i >= 0; $i--) {
            if (!empty($this->taskAssignments[$i]['expected_date']) && 
                !empty($this->taskAssignments[$i]['no_of_days'])) {
                $prevStart = Carbon::parse($this->taskAssignments[$i]['expected_date']);
                $prevDays = (int) $this->taskAssignments[$i]['no_of_days'];
                $prevStartDate = $prevStart->addDays($prevDays);
                break;
            }
           
              
        }
        
        if ($prevStartDate) {
            $this->taskAssignments[$index]['expected_date'] = $prevStartDate->format('Y-m-d');
            
            // Copy start date and deadline from first assignment
            if (!empty($this->taskAssignments[0]['start_date'])) {
                $this->taskAssignments[$index]['start_date'] = $this->taskAssignments[0]['start_date'];
            }
            if (!empty($this->taskAssignments[0]['deadline'])) {
                $this->taskAssignments[$index]['deadline'] = $this->taskAssignments[0]['deadline'];
            }
            
            // Set start date of next row if it exists
            if (isset($this->taskAssignments[$index + 1])) {
                $nextStartDate = $prevStartDate->copy()->addDays($days);
                $this->taskAssignments[$index + 1]['expected_date'] = $nextStartDate->format('Y-m-d');
                
                // Copy start date and deadline to next row
                if (!empty($this->taskAssignments[0]['start_date'])) {
                    $this->taskAssignments[$index + 1]['start_date'] = $this->taskAssignments[0]['start_date'];
                }
                if(!empty($this->taskAssignments[0]['deadline'])) {
                    $this->taskAssignments[$index + 1]['deadline'] = $this->taskAssignments[0]['deadline'];
                }
            }
        }
    }
    
    // Recalculate all subsequent assignments after the next one
    $this->recalculateSequentialDates();
    
   
}

/**
 * Recalculate all sequential dates when first assignment dates change
 */
public function recalculateSequentialDates()
{
    if (empty($this->taskAssignments) || empty($this->taskAssignments[0]['expected_date'])) {
        return;
    }
    
    // Propagate expected date and deadline from first assignment to all others
     $firstExpectedDate = $this->taskAssignments[0]['start_date'] ?? '';
    $firstDeadline = $this->taskAssignments[0]['deadline'] ?? '';
    
    // Recalculate start dates for all assignments after the first
    $this->recalculateSubsequentAssignments(1);
    
    // Copy start date and deadline from first assignment to all subsequent ones
    for ($i = 1; $i < count($this->taskAssignments); $i++) {
        if ($firstExpectedDate) {
            $this->taskAssignments[$i]['start_date'] = $firstExpectedDate;
        }
        if ($firstDeadline) {
            $this->taskAssignments[$i]['deadline'] = $firstDeadline;
        }
    }
}

/**
 * Recalculate subsequent assignments from a given index
 */
private function recalculateSubsequentAssignments($fromIndex)
{
    for ($i = $fromIndex; $i < count($this->taskAssignments); $i++) {
        if (!empty($this->taskAssignments[$i]['no_of_days'])) {
            $days = (int) $this->taskAssignments[$i]['no_of_days'];
            
            // Find the start date from the previous assignment
            $prevStartDate = null;
            for ($j = $i - 1; $j >= 0; $j--) {
                if (!empty($this->taskAssignments[$j]['expected_date']) && 
                    !empty($this->taskAssignments[$j]['no_of_days'])) {
                    $prevStart = Carbon::parse($this->taskAssignments[$j]['expected_date']);
                    $prevDays = (int) $this->taskAssignments[$j]['no_of_days'];
                    $prevStartDate = $prevStart->addDays($prevDays);
                    break;
                }
            }
            
            if ($prevStartDate) {
                $this->taskAssignments[$i]['expected_date'] = $prevStartDate->format('Y-m-d');
                
                // Copy start date and deadline from first assignment
                if (!empty($this->taskAssignments[0]['start_date'])) {
                    $this->taskAssignments[$i]['start_date'] = $this->taskAssignments[0]['expected_date'];
                }
                if (!empty($this->taskAssignments[0]['deadline'])) {
                    $this->taskAssignments[$i]['deadline'] = $this->taskAssignments[0]['deadline'];
                }
            }
        }
    }
}

/**
 * Validate and propagate expected date changes from first assignment
 */
public function validateAndPropagateExpectedDate()
{
    if (!empty($this->taskAssignments[0]['expected_date']) && 
        !empty($this->taskAssignments[0]['deadline'])) {
        
         $expectedDate = Carbon::parse($this->taskAssignments[0]['expected_date']);
        $deadline = Carbon::parse($this->taskAssignments[0]['deadline']);
        
        // Validate: start date can be more than expected date but not more than deadline
        if (!empty($this->taskAssignments[0]['start_date'])) {
            $startDate = Carbon::parse($this->taskAssignments[0]['start_date']);
            if ($startDate->gt($deadline)) {
                session()->flash('error', 'Start date cannot be later than deadline.');
                return;
            }
        }
        
        // Expected date cannot be later than deadline
        if ($expectedDate->gt($deadline)) {
            session()->flash('error', 'Expected date cannot be later than deadline.');
            $this->taskAssignments[0]['expected_date'] = $deadline->format('Y-m-d');
        }
    }
    
    // Propagate expected date to all subsequent assignments
    // $expectedDate = $this->taskAssignments[0]['expected_date'];
    // for ($i = 1; $i < count($this->taskAssignments); $i++) {
    //     $this->taskAssignments[$i]['expected_date'] = $expectedDate;
    // }
}

/**
 * Validate and propagate deadline changes from first assignment
 */
public function validateAndPropagateDeadline()
{
    if (!empty($this->taskAssignments[0]['deadline'])) {
        $deadline = Carbon::parse($this->taskAssignments[0]['deadline']);
        
        // Validate against start date
        if (!empty($this->taskAssignments[0]['start_date'])) {
            $startDate = Carbon::parse($this->taskAssignments[0]['start_date']);
            if ($startDate->gt($deadline)) {
                session()->flash('error', 'Start date cannot be later than deadline.');
                return;
            }
        }
        
        // Validate against expected date
        if (!empty($this->taskAssignments[0]['expected_date'])) {
            $expectedDate = Carbon::parse($this->taskAssignments[0]['expected_date']);
            if ($expectedDate->gt($deadline)) {
                // Adjust expected date to match deadline
                $this->taskAssignments[0]['expected_date'] = $deadline->format('Y-m-d');
            }
        }
    }
    
    // Propagate deadline to all subsequent assignments
    $deadline = $this->taskAssignments[0]['deadline'];
  //  $expectedDate = $this->taskAssignments[0]['expected_date'];
    
    for ($i = 1; $i < count($this->taskAssignments); $i++) {
        $this->taskAssignments[$i]['deadline'] = $deadline;
        // $this->taskAssignments[$i]['expected_date'] = $expectedDate;
    }
}

/**
 * Remove task assignment and recalculate sequential dates
 */
public function removeTaskAssignment($index)
{
    if (count($this->taskAssignments) > 1) {
        array_splice($this->taskAssignments, $index, 1);
        
        // If we removed the first assignment, clear all calculated dates
        // as the new first assignment becomes manually editable
        if ($index === 0) {
            // Reset all auto-calculated dates since first assignment changed
            for ($i = 1; $i < count($this->taskAssignments); $i++) {
                $this->taskAssignments[$i]['start_date'] = '';
                $this->taskAssignments[$i]['expected_date'] = '';
                $this->taskAssignments[$i]['deadline'] = '';
            }
        } else {
            // Recalculate all subsequent assignments
            $this->recalculateSequentialDates();
        }
    }
}

/**
 * Enhanced validation rules
 */


/**
 * Create task with enhanced validation
 */
public function createTask()
{
    $this->authorize('create_tasks');
    
    // Custom validation for first assignment
    if (empty($this->taskAssignments[0]['start_date'])) {
        session()->flash('error', 'Start date is required for the first assignment.');
        return;
    }
    
    if (empty($this->taskAssignments[0]['deadline'])) {
        session()->flash('error', 'Deadline is required for the first assignment.');
        return;
    }
    
    // Validate date logic for first assignment
    $startDate = Carbon::parse($this->taskAssignments[0]['start_date']);
    $deadline = Carbon::parse($this->taskAssignments[0]['deadline']);
    
    if ($startDate->gt($deadline)) {
        session()->flash('error', 'Start date cannot be later than deadline.');
        return;
    }
    
    if (!empty($this->taskAssignments[0]['expected_date'])) {
        $expectedDate = Carbon::parse($this->taskAssignments[0]['expected_date']);
        if ($expectedDate->gt($deadline)) {
            session()->flash('error', 'Expected date cannot be later than deadline.');
            return;
        }
    }
    
    $this->validate();

    try {
        $task = Task::create([
            'task_name' => $this->taskName,
            'project_id' => $this->selectedProjectId,
            'task_status' => 'pending',
            'feedback' => '',
        ]);

       

        // Create task assignments
        foreach ($this->taskAssignments as $index => $assignmentData) {
               if($assignmentData['status']=='Completed'){
                                       $doc=date('Y-m-d');
                                 
                                  
                                }
           else
            $doc=null;
            $task->addAssignment([
                'user_id' => $assignmentData['user_id'],
                'work_description' => $assignmentData['work_description'],
                'start_date' => !empty($assignmentData['start_date']) ? $assignmentData['start_date'] : null,
                'expected_date' =>!empty($assignmentData['expected_date']) ? $assignmentData['expected_date'] : null,
                'deadline' => !empty($assignmentData['deadline']) ? $assignmentData['deadline'] : null,
                'status' => $assignmentData['status'],
                'no_of_days' => $assignmentData['no_of_days'],
                'doc'=>$doc,
                'is_admin'=>1,
                'sequence_number' => $index + 1,
            ]);
        }

        // Update overall task status
        $task->updateOverallStatus();

        $this->showCreateTaskModal = false;
        $this->resetTaskForm();
        
        session()->flash('success', 'Task created successfully with ' . count($this->taskAssignments) . ' assignment(s)!');
        $this->dispatch('refreshDashboard');

    } catch (\Exception $e) {
        session()->flash('error', 'Failed to create task: ' . $e->getMessage());
    }
}


private function loadAssignmentData()
{
    $this->assignmentData = $this->managingTask->assignments->map(function ($assignment) {
        return [
            'id' => $assignment->id,
            'sequence_number' => $assignment->sequence_number,
            'user_id' => $assignment->user_id,
            'work_description' => $assignment->work_description,
            'no_of_days' => $assignment->no_of_days,
            'start_date' => $assignment->start_date?$assignment->start_date->format('Y-m-d'):'',
            'expected_date' => $assignment->expected_date?$assignment->expected_date->format('Y-m-d'):'',
            'deadline' => $assignment->deadline?$assignment->deadline->format('Y-m-d'):'',
            'status' => $assignment->status,
        ];
    })->toArray();

    // Store original data for comparison
    $this->originalAssignmentData = $this->assignmentData;
}

/**
 * Add new assignment row with sequential logic
 */
public function addAssignmentRow()
{
    if (!auth()->user()->can('manage_task_assignments')) {
        session()->flash('error', 'You do not have permission to add assignments.');
        return;
    }

    if ($this->managingTask->task_status === 'on hold') {
        session()->flash('error', 'Cannot add assignments when task is on hold.');
        return;
    }

    $nextSequence = count($this->assignmentData) + 1;
    
    $newAssignment = [
        'id' => null, // New assignment
        'sequence_number' => $nextSequence,
        'user_id' => '',
        'work_description' => '',
        'no_of_days' => '',
        'start_date' => '',
        'expected_date' => '',
        'deadline' => '',
        'status' => 'Pending',
    ];
    
    $this->assignmentData[] = $newAssignment;
    
    // Apply sequential logic to new assignment
    $this->recalculateAssignmentDates();
}
public function removeAssignmentRow($index)
{
    if (!auth()->user()->can('manage_task_assignments') || !auth()->user()->isSuperAdmin()) {
        session()->flash('error', 'You do not have permission to remove assignments.');
        return;
    }

    if (count($this->assignmentData) <= 1) {
        session()->flash('error', 'Task must have at least one assignment.');
        return;
    }

    // Check if assignment is completed
    if (isset($this->assignmentData[$index]['status']) && $this->assignmentData[$index]['status'] === 'Completed') {
        session()->flash('error', 'Cannot remove completed assignments.');
        return;
    }

        TaskAssignment::where('id',$this->assignmentData[$index]['id'])->delete();

    unset($this->assignmentData[$index]);
    
    $this->assignmentData = array_values($this->assignmentData); // Re-index array



    
    // Update sequence numbers
    foreach ($this->assignmentData as $key => $assignment) {
        $this->assignmentData[$key]['sequence_number'] = $key + 1;
    }

    // If we removed the first assignment, clear all calculated dates
    // as the new first assignment becomes manually editable
    if ($index === 0) {
        // Reset all auto-calculated dates since first assignment changed
        for ($i = 1; $i < count($this->assignmentData); $i++) {
            $this->assignmentData[$i]['start_date'] = '';
            $this->assignmentData[$i]['expected_date'] = '';
            $this->assignmentData[$i]['deadline'] = '';
        }
    } else {
        // Recalculate all subsequent assignments
        $this->recalculateAssignmentDates();
    }
}

/**
 * Calculate dates when no_of_days changes for assignments in modal
 */
public function calculateAssignmentDatesFromDays($index)
{
    if (!isset($this->assignmentData[$index]['no_of_days']) || 
        $this->assignmentData[$index]['no_of_days'] < 0) {
        return;
    }

    $days = (int) $this->assignmentData[$index]['no_of_days'];
    
    if ($index === 0) {
        // First assignment: Keep existing dates if they exist, otherwise user needs to set them
        // The start date, expected date, and deadline should remain editable for first assignment
        
        // If first assignment has start date, set next row's start date
        if (!empty($this->assignmentData[0]['expected_date']) && isset($this->assignmentData[1])) {
            $startDate = Carbon::parse($this->assignmentData[0]['expected_date']);
            $nextStartDate = $startDate->copy()->addDays($days);
            $this->assignmentData[1]['expected_date'] = $nextStartDate->format('Y-m-d');
            
            // Copy start date and deadline from first assignment to next row
            if (!empty($this->assignmentData[0]['start_date'])) {
                $this->assignmentData[1]['start_date'] = $this->assignmentData[0]['start_date'];
            }
            if (!empty($this->assignmentData[0]['deadline'])) {
                $this->assignmentData[1]['deadline'] = $this->assignmentData[0]['deadline'];
            }
        }
        
        return;
    } else {
        // For subsequent assignments: Calculate start date from previous assignment
        $prevStartDate = null;
        
        // Get the previous assignment's start date + days
        for ($i = $index - 1; $i >= 0; $i--) {
            if (!empty($this->assignmentData[$i]['expected_date']) && 
                !empty($this->assignmentData[$i]['no_of_days'])) {
                $prevStart = Carbon::parse($this->assignmentData[$i]['expected_date']);
                $prevDays = (int) $this->assignmentData[$i]['no_of_days'];
                $prevStartDate = $prevStart->addDays($prevDays);
                break;
            }
        }
        
        if ($prevStartDate) {
            $this->assignmentData[$index]['expected_date'] = $prevStartDate->format('Y-m-d');
            
            // Copy start date and deadline from first assignment
            if (!empty($this->assignmentData[0]['start_date'])) {
                $this->assignmentData[$index]['start_date'] = $this->assignmentData[0]['start_date'];
            }
            if (!empty($this->assignmentData[0]['deadline'])) {
                $this->assignmentData[$index]['deadline'] = $this->assignmentData[0]['deadline'];
            }
            
            // Set start date of next row if it exists
            if (isset($this->assignmentData[$index + 1])) {
                $nextStartDate = $prevStartDate->copy()->addDays($days);
                $this->assignmentData[$index + 1]['expected_date'] = $nextStartDate->format('Y-m-d');
                
                // Copy start date and deadline to next row
                if (!empty($this->assignmentData[0]['start_date'])) {
                    $this->assignmentData[$index + 1]['start_date'] = $this->assignmentData[0]['start_date'];
                }
                if(!empty($this->assignmentData[0]['deadline'])) {
                    $this->assignmentData[$index + 1]['deadline'] = $this->assignmentData[0]['deadline'];
                }
            }
        }
    }
    
    // Recalculate all subsequent assignments after the next one
    $this->recalculateSubsequentAssignmentDates($index + 2);
}

/**
 * Recalculate assignment dates when first assignment changes
 */
public function recalculateAssignmentDates()
{
    if (empty($this->assignmentData) || empty($this->assignmentData[0]['expected_date'])) {
        return;
    }
    
    // Propagate start date and deadline from first assignment
     $firstStartDate = $this->assignmentData[0]['start_date'] ?? '';
    $firstDeadline = $this->assignmentData[0]['deadline'] ?? '';
    
    // Recalculate all assignments after the first
    $this->recalculateSubsequentAssignmentDates(1);
    
    // Copy start date and deadline to all subsequent assignments
    for ($i = 1; $i < count($this->assignmentData); $i++) {
        if ($firstStartDate) {
            $this->assignmentData[$i]['start_date'] = $firstStartDate;
        }
        if ($firstDeadline) {
            $this->assignmentData[$i]['deadline'] = $firstDeadline;
        }
    }
}

/**
 * Recalculate subsequent assignment dates from a given index
 */
private function recalculateSubsequentAssignmentDates($fromIndex)
{
    for ($i = $fromIndex; $i < count($this->assignmentData); $i++) {
        if (!empty($this->assignmentData[$i]['no_of_days'])) {
            $days = (int) $this->assignmentData[$i]['no_of_days'];
            
            // Find the start date from the previous assignment
            $prevStartDate = null;
            for ($j = $i - 1; $j >= 0; $j--) {
                if (!empty($this->assignmentData[$j]['expected_date']) && 
                    !empty($this->assignmentData[$j]['no_of_days'])) {
                    $prevStart = Carbon::parse($this->assignmentData[$j]['expected_date']);
                    $prevDays = (int) $this->assignmentData[$j]['no_of_days'];
                    $prevStartDate = $prevStart->addDays($prevDays);
                    break;
                }
            }
            
            if ($prevStartDate) {
                $this->assignmentData[$i]['expected_date'] = $prevStartDate->format('Y-m-d');
                
                // Copy start date and deadline from first assignment
                if (!empty($this->assignmentData[0]['start_date'])) {
                    $this->assignmentData[$i]['start_date'] = $this->assignmentData[0]['start_date'];
                }
                if (!empty($this->assignmentData[0]['deadline'])) {
                    $this->assignmentData[$i]['deadline'] = $this->assignmentData[0]['deadline'];
                }
            }
        }
    }
}

/**
 * Validate and propagate expected date changes in assignment modal
 */
public function validateAssignmentExpectedDate()
{
    if (!empty($this->assignmentData[0]['expected_date']) && 
        !empty($this->assignmentData[0]['deadline'])) {
        
        $expectedDate = Carbon::parse($this->assignmentData[0]['expected_date']);
        $deadline = Carbon::parse($this->assignmentData[0]['deadline']);
        
        // Validate: start date can be more than expected date but not more than deadline
        if (!empty($this->assignmentData[0]['start_date'])) {
            $startDate = Carbon::parse($this->assignmentData[0]['start_date']);
            if ($startDate->gt($deadline)) {
                session()->flash('error', 'Start date cannot be later than deadline.');
                return;
            }
        }
        
        // Expected date cannot be later than deadline
        if ($expectedDate->gt($deadline)) {
            session()->flash('error', 'Expected date cannot be later than deadline.');
            $this->assignmentData[0]['expected_date'] = $deadline->format('Y-m-d');
        }
    }
    
    // Propagate expected date to all subsequent assignments
    // $expectedDate = $this->assignmentData[0]['expected_date'];
    // for ($i = 1; $i < count($this->assignmentData); $i++) {
    //     $this->assignmentData[$i]['expected_date'] = $expectedDate;
    // }
}

public function validateAssignmentStartDate()
{
    if (!empty($this->assignmentData[0]['start_date']) && 
        !empty($this->assignmentData[0]['deadline'])) {
        
        $startDate = Carbon::parse($this->assignmentData[0]['start_date']);
        $deadline = Carbon::parse($this->assignmentData[0]['deadline']);
        
        // Validate: start date can be more than expected date but not more than deadline
        if (!empty($this->assignmentData[0]['start_date'])) {
            $startDate = Carbon::parse($this->assignmentData[0]['start_date']);
            if ($startDate->gt($deadline)) {
                session()->flash('error', 'Start date cannot be later than deadline.');
                return;
            }
        }
        
      
    }
    
    //Propagate start date to all subsequent assignments
    $startDate = $this->assignmentData[0]['start_date'];
    for ($i = 1; $i < count($this->assignmentData); $i++) {
        $this->assignmentData[$i]['start_date'] = $startDate;
    }
}

/**
 * Validate and propagate deadline changes in assignment modal
 */
public function validateAssignmentDeadline()
{
    if (!empty($this->assignmentData[0]['deadline'])) {
        $deadline = Carbon::parse($this->assignmentData[0]['deadline']);
        
        // Validate against start date
        if (!empty($this->assignmentData[0]['start_date'])) {
            $startDate = Carbon::parse($this->assignmentData[0]['start_date']);
            if ($startDate->gt($deadline)) {
                session()->flash('error', 'Start date cannot be later than deadline.');
                return;
            }
        }
        
        // Validate against expected date
        if (!empty($this->assignmentData[0]['expected_date'])) {
            $expectedDate = Carbon::parse($this->assignmentData[0]['expected_date']);
            if ($expectedDate->gt($deadline)) {
                $this->assignmentData[0]['expected_date'] = $deadline->format('Y-m-d');
            }
        }
    }
    
    // Propagate deadline and expected date to all subsequent assignments
    $deadline = $this->assignmentData[0]['deadline'];
    $expectedDate = $this->assignmentData[0]['expected_date'];
    
    for ($i = 1; $i < count($this->assignmentData); $i++) {
        $this->assignmentData[$i]['deadline'] = $deadline;
        $this->assignmentData[$i]['expected_date'] = $expectedDate;
    }
}

/**
 * Enhanced validation for assignments
 */
private function validateAssignments()
{
    $rules = [];
    $messages = [];

    foreach ($this->assignmentData as $index => $assignment) {
        $rules["assignmentData.{$index}.user_id"] = 'required|exists:users,id';
        $rules["assignmentData.{$index}.work_description"] = 'required|string|max:500';
        $rules["assignmentData.{$index}.no_of_days"] = 'nullable|integer|min:0';
        $rules["assignmentData.{$index}.start_date"] = 'nullable|date';
        $rules["assignmentData.{$index}.expected_date"] = 'nullable|date';
        $rules["assignmentData.{$index}.deadline"] = 'nullable|date';
        $rules["assignmentData.{$index}.status"] = 'required|in:Pending,Inprogress,Reassigned,Completed,Not Completed';

        $messages["assignmentData.{$index}.user_id.required"] = "User is required for assignment " . ($index + 1);
        $messages["assignmentData.{$index}.work_description.required"] = "Work description is required for assignment " . ($index + 1);
        $messages["assignmentData.{$index}.no_of_days.min"] = "Number of days must be at least 1 for assignment " . ($index + 1);
    }

    $this->validate($rules, $messages);
    
    // Additional date validation - only if dates are provided
    foreach ($this->assignmentData as $index => $assignment) {
        if (!empty($assignment['start_date']) && !empty($assignment['deadline'])) {
            $startDate = Carbon::parse($assignment['start_date']);
            $deadline = Carbon::parse($assignment['deadline']);
            
            if ($startDate->gt($deadline)) {
                throw new \Exception("Start date cannot be later than deadline for assignment " . ($index + 1));
            }
        }
        
        if (!empty($assignment['expected_date']) && !empty($assignment['deadline'])) {
            $expectedDate = Carbon::parse($assignment['expected_date']);
            $deadline = Carbon::parse($assignment['deadline']);
            
            if ($expectedDate->gt($deadline)) {
                throw new \Exception("Expected date cannot be later than deadline for assignment " . ($index + 1));
            }
        }
    }
}
}