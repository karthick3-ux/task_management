<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\Project;
use App\Models\Department;
use App\Models\User;
use App\Models\TaskUpdateHistory;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    // Search and Filter Properties
    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';
    public $projectFilter = '';
    public $departmentFilter = '';

    // Modal Properties
    public $showEditTaskModal = false;
    public $showViewTaskModal = false;
    public $showDeleteTaskModal = false;
    public $showUpdateTaskModal = false;
    public $showHistoryModal = false;

    // Form Properties
    public $taskName = '';
    public $selectedProjectId = '';
    public $selectedDepartmentId = '';
    public $selectedUsers = [];
    public $taskDeadline = '';
    public $taskPriority = 3;
    public $taskStatus = 'Pending';
    public $taskPercentage = 0;
    public $taskFeedback = '';

    // Selected Items
    public $selectedTask = null;
    public $selectedTaskId = null;
    public $taskHistories = [];

    // Data Collections
    public $departments = [];
    public $users = [];
    public $projects = [];

    // Statistics
    public $pendingTasksCount = 0;
    public $progressTasksCount = 0;
    public $completedTasksCount = 0;
    public $overdueTasksCount = 0;

    protected $listeners = [
        'refreshTasks' => '$refresh',
    ];

    protected $rules = [
        'taskName' => 'required|string|max:255',
        'selectedProjectId' => 'required|exists:projects,id',
        'selectedDepartmentId' => 'required|exists:departments,id',
        'selectedUsers' => 'required|array|min:1',
        'selectedUsers.*' => 'exists:users,id',
        'taskDeadline' => 'required|date',
        'taskPriority' => 'required|integer|min:1|max:5',
        'taskStatus' => 'required|in:Pending,Progress,Completed',
        'taskPercentage' => 'required|numeric|min:0|max:100',
        'taskFeedback' => 'nullable|string|max:1000',
    ];

    protected $messages = [
        'taskName.required' => 'Task name is required.',
        'selectedProjectId.required' => 'Please select a project.',
        'selectedDepartmentId.required' => 'Please select a department.',
        'selectedUsers.required' => 'Please select at least one user.',
        'taskDeadline.required' => 'Deadline is required.',
        'taskPriority.required' => 'Priority is required.',
        'taskStatus.required' => 'Status is required.',
        'taskPercentage.required' => 'Percentage is required.',
        'taskPercentage.min' => 'Percentage must be at least 0.',
        'taskPercentage.max' => 'Percentage cannot exceed 100.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter()
    {
        $this->resetPage();
    }

    public function updatingProjectFilter()
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->authorize('view_tasks');
        $this->loadData();
        $this->updateStatistics();
    }

    public function render()
    {
        $tasksQuery = Task::with(['project', 'department', 'users'])
            ->when($this->search, function ($query) {
                $query->where('task_name', 'like', '%' . $this->search . '%')
                      ->orWhere('feedback', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('task_status', $this->statusFilter);
            })
            ->when($this->priorityFilter !== '', function ($query) {
                $query->where('priority', $this->priorityFilter);
            })
            ->when($this->projectFilter !== '', function ($query) {
                $query->where('project_id', $this->projectFilter);
            })
            ->when($this->departmentFilter !== '', function ($query) {
                $query->where('department_id', $this->departmentFilter);
            });

        // If not super admin, show only tasks assigned to current user
        if (!auth()->user()->isSuperAdmin()) {
            $tasksQuery->whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            });
        }

        $tasks = $tasksQuery->orderBy('priority', 'desc')
                           ->orderBy('deadline', 'asc')
                           ->paginate(15);

        $this->updateStatistics();

        return view('livewire.tasks.index', [
            'tasks' => $tasks,
        ]);
    }

    public function openEditTaskModal($taskId)
    {
        $this->authorize('edit_tasks');
        
        $this->selectedTask = Task::with(['project', 'department', 'users'])->findOrFail($taskId);
        $this->selectedTaskId = $taskId;
        
        $this->taskName = $this->selectedTask->task_name;
        $this->selectedProjectId = $this->selectedTask->project_id;
        $this->selectedDepartmentId = $this->selectedTask->department_id;
        $this->selectedUsers = $this->selectedTask->users->pluck('id')->toArray();
        $this->taskDeadline = $this->selectedTask->deadline->format('Y-m-d');
        $this->taskPriority = $this->selectedTask->priority;
        $this->taskStatus = $this->selectedTask->task_status;
        $this->taskPercentage = $this->selectedTask->percentage;
        $this->taskFeedback = $this->selectedTask->feedback;
        
        $this->showEditTaskModal = true;
    }

    public function updateTask()
    {
        $this->authorize('edit_tasks');
        
        $this->validate();

        try {
            $oldStatus = $this->selectedTask->task_status;
            $oldPercentage = $this->selectedTask->percentage;
            
            $this->selectedTask->update([
                'task_name' => $this->taskName,
                'project_id' => $this->selectedProjectId,
                'department_id' => $this->selectedDepartmentId,
                'deadline' => $this->taskDeadline,
                'priority' => $this->taskPriority,
                'task_status' => $this->taskStatus,
                'percentage' => $this->taskPercentage,
                'feedback' => $this->taskFeedback,
                'date_of_completion' => $this->taskStatus === 'Completed' ? Carbon::today() : null,
            ]);

            // Update user assignments
            $this->selectedTask->users()->sync($this->selectedUsers);

            // Add update history
            $changes = [];
            if ($oldStatus !== $this->taskStatus) {
                $changes[] = "Status changed from {$oldStatus} to {$this->taskStatus}";
            }
            if ($oldPercentage != $this->taskPercentage) {
                $changes[] = "Percentage updated from {$oldPercentage}% to {$this->taskPercentage}%";
            }
            
            if (!empty($changes)) {
                $this->selectedTask->addUpdateHistory(implode(', ', $changes));
            }

            $this->showEditTaskModal = false;
            $this->resetForm();
            
            session()->flash('success', 'Task updated successfully!');
            $this->dispatch('refreshTasks');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update task. Please try again.');
        }
    }

    public function openViewTaskModal($taskId)
    {
        $this->authorize('view_tasks');
        
        $this->selectedTask = Task::with(['project', 'department', 'users', 'updateHistories.user'])
            ->findOrFail($taskId);
        
        $this->showViewTaskModal = true;
    }

    public function openUpdateTaskModal($taskId)
    {
        $this->authorize('edit_tasks');
        
        $this->selectedTask = Task::findOrFail($taskId);
        $this->selectedTaskId = $taskId;
        $this->taskStatus = $this->selectedTask->task_status;
        $this->taskPercentage = $this->selectedTask->percentage;
        $this->taskFeedback = $this->selectedTask->feedback ?? '';
        
        $this->showUpdateTaskModal = true;
    }

    public function quickUpdateTask()
    {
        $this->authorize('edit_tasks');
        
        $this->validate([
            'taskStatus' => 'required|in:Pending,Progress,Completed',
            'taskPercentage' => 'required|numeric|min:0|max:100',
            'taskFeedback' => 'nullable|string|max:1000',
        ]);

        try {
            $oldStatus = $this->selectedTask->task_status;
            $oldPercentage = $this->selectedTask->percentage;
            $oldFeedback = $this->selectedTask->feedback;
            
            $this->selectedTask->update([
                'task_status' => $this->taskStatus,
                'percentage' => $this->taskPercentage,
                'feedback' => $this->taskFeedback,
                'date_of_completion' => $this->taskStatus === 'Completed' ? Carbon::today() : null,
            ]);

            // Add update history
            $changes = [];
            if ($oldStatus !== $this->taskStatus) {
                $changes[] = "Status changed from {$oldStatus} to {$this->taskStatus}";
            }
            if ($oldPercentage != $this->taskPercentage) {
                $changes[] = "Percentage updated from {$oldPercentage}% to {$this->taskPercentage}%";
            }
            if ($oldFeedback !== $this->taskFeedback) {
                $changes[] = $oldFeedback ? 'Feedback updated' : 'Feedback added';
            }
            
            if (!empty($changes)) {
                $this->selectedTask->addUpdateHistory(implode(', ', $changes));
            }

            $this->showUpdateTaskModal = false;
            $this->resetForm();
            
            session()->flash('success', 'Task updated successfully!');
            $this->dispatch('refreshTasks');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update task. Please try again.');
        }
    }

    public function openDeleteTaskModal($taskId)
    {
        $this->authorize('delete_tasks');
        
        $this->selectedTask = Task::with(['project', 'department', 'users'])->findOrFail($taskId);
        $this->selectedTaskId = $taskId;
        $this->showDeleteTaskModal = true;
    }

    public function deleteTask()
    {
        $this->authorize('delete_tasks');
        
        try {
            $this->selectedTask->delete();
            
            $this->showDeleteTaskModal = false;
            $this->resetForm();
            
            session()->flash('success', 'Task deleted successfully!');
            $this->dispatch('refreshTasks');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete task. Please try again.');
        }
    }

    public function openHistoryModal($taskId)
    {
        $this->authorize('view_tasks');
        
        $this->selectedTask = Task::findOrFail($taskId);
        $this->taskHistories = TaskUpdateHistory::with('user')
            ->where('task_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->showHistoryModal = true;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->priorityFilter = '';
        $this->projectFilter = '';
        $this->departmentFilter = '';
        $this->resetPage();
    }

    private function loadData()
    {
        $this->departments = Department::active()->get();
        $this->projects = Project::where('status', 'active')->get();
        
        if (auth()->user()->isSuperAdmin()) {
            $this->users = User::where('is_active', true)->get();
        }
    }

    private function resetForm()
    {
        $this->taskName = '';
        $this->selectedProjectId = '';
        $this->selectedDepartmentId = '';
        $this->selectedUsers = [];
        $this->taskDeadline = '';
        $this->taskPriority = 3;
        $this->taskStatus = 'Pending';
        $this->taskPercentage = 0;
        $this->taskFeedback = '';
        $this->selectedTask = null;
        $this->selectedTaskId = null;
        $this->resetValidation();
    }

    private function updateStatistics()
    {
        $query = Task::query();
        
        // If not super admin, only count tasks assigned to current user
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('users', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }
        
        $this->pendingTasksCount = (clone $query)->where('task_status', 'Pending')->count();
        $this->progressTasksCount = (clone $query)->where('task_status', 'Progress')->count();
        $this->completedTasksCount = (clone $query)->where('task_status', 'Completed')->count();
        $this->overdueTasksCount = (clone $query)->where('deadline', '<', Carbon::today())
                                                 ->whereNotIn('task_status', ['Completed'])
                                                 ->count();
    }
}