<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Task;
use App\Models\Department;
use App\Models\User;
use App\Models\TaskUpdateHistory;
use App\Services\TaskHistoryService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class Index extends Component
{

     use WithPagination;
        protected $paginationTheme = 'bootstrap';

    // Search and Filter Properties
    public $search = '';
    public $statusFilter = '';

    // Modal Properties
    public $showCreateProjectModal = false;
    public $showEditProjectModal = false;
    public $showViewProjectModal = false;
    public $showDeleteProjectModal = false;
    public $showCreateTaskModal = false;

    // Form Properties - Project
    public $projectName = '';
    public $projectDescription = '';
    public $projectStatus = 'active';

    // Task Form Properties
    public $taskName = '';
    public $selectedProjectId = '';
    
    // Task Assignment Rows
    public $taskAssignments = [];
    
    // Selected Items
    public $selectedProject = null;
    public $selectedProjectIdForEdit = null;

    // Data Collections
    public $users = [];
    public $projects_all = [];

    // Statistics
    public $activeProjectsCount = 0;
    public $inactiveProjectsCount = 0;

    protected $listeners = [
        'refreshProjects' => '$refresh',
    ];

    protected $rules = [
        'projectName' => 'required|string|max:255',
        'projectDescription' => 'nullable|string|max:1000',
        'projectStatus' => 'required|in:active,inactive',
        
        // Task validation rules
        'taskName' => 'required|string|max:255',
        'selectedProjectId' => 'required|exists:projects,id',
        'taskAssignments' => 'required|array|min:1',
        'taskAssignments.*.user_id' => 'required|exists:users,id',
        'taskAssignments.*.work_description' => 'required|string|max:500',
        'taskAssignments.*.start_date' => 'required|date|after_or_equal:today',
        'taskAssignments.*.expected_date' => 'required|date|after:taskAssignments.*.start_date',
        'taskAssignments.*.deadline' => 'required|date|after_or_equal:taskAssignments.*.expected_date',
        'taskAssignments.*.status' => 'required|in:Pending,Inprogress,Reassigned,Completed,Not Completed',
    ];

    protected $messages = [
        'projectName.required' => 'Project name is required.',
        'projectName.max' => 'Project name cannot exceed 255 characters.',
        'projectDescription.max' => 'Description cannot exceed 1000 characters.',
        'projectStatus.required' => 'Status is required.',
        
        'taskName.required' => 'Task name is required.',
        'selectedProjectId.required' => 'Please select a project.',
        'taskAssignments.required' => 'At least one assignment is required.',
        'taskAssignments.*.user_id.required' => 'Please select a user for this assignment.',
        'taskAssignments.*.work_description.required' => 'Work description is required.',
        'taskAssignments.*.start_date.required' => 'Start date is required.',
        'taskAssignments.*.start_date.after_or_equal' => 'Start date cannot be in the past.',
        'taskAssignments.*.expected_date.required' => 'Expected completion date is required.',
        'taskAssignments.*.expected_date.after' => 'Expected date must be after start date.',
        'taskAssignments.*.deadline.after_or_equal' => 'Deadline must be on or after expected date.',
    ];


     protected TaskHistoryService $taskHistoryService;

    public function boot(TaskHistoryService $taskHistoryService)
    {
        $this->taskHistoryService = $taskHistoryService;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->authorize('view_projects');
        $this->loadData();
        $this->updateStatistics();
    }

    public function render()
    {
        $projects = Project::
            when($this->search, function ($query) {
                $query->where('project_name', 'like', '%' . $this->search . '%')
                      ->orWhere('project_description', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->withCount(['tasks', 'tasks as pending_tasks_count' => function ($query) {
                $query->where('task_status', 'Pending');
            }, 'tasks as progress_tasks_count' => function ($query) {
                $query->where('task_status', 'Progress');
            }, 'tasks as completed_tasks_count' => function ($query) {
                $query->where('task_status', 'Completed');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $this->updateStatistics();

        return view('livewire.projects.index', [
            'projects' => $projects,
        ])->layout('layouts.app');
    }

    // Project CRUD Methods
    public function openCreateProjectModal()
    {
        $this->authorize('create_projects');
        $this->resetProjectForm();
        $this->showCreateProjectModal = true;
    }

    public function createProject()
    {
        $this->authorize('create_projects');
        
        $this->validate([
            'projectName' => 'required|string|max:255',
            'projectDescription' => 'nullable|string|max:1000',
            'projectStatus' => 'required|in:active,inactive',
        ]);

        try {
            Project::create([
                'project_name' => $this->projectName,
                'project_description' => $this->projectDescription,
                'status' => $this->projectStatus,
            ]);

            $this->showCreateProjectModal = false;
            $this->resetProjectForm();
            
            session()->flash('success', 'Project created successfully!');
            $this->dispatch('refreshProjects');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create project. Please try again.');
        }
    }

    public function openEditProjectModal($projectId)
    {
        $this->authorize('edit_projects');
        
        $this->selectedProject = Project::findOrFail($projectId);
        $this->selectedProjectIdForEdit = $projectId;
        
        $this->projectName = $this->selectedProject->project_name;
        $this->projectDescription = $this->selectedProject->project_description;
        $this->projectStatus = $this->selectedProject->status;
        
        $this->showEditProjectModal = true;
    }

    public function updateProject()
    {
        $this->authorize('edit_projects');
        
        $this->validate([
            'projectName' => 'required|string|max:255',
            'projectDescription' => 'nullable|string|max:1000',
            'projectStatus' => 'required|in:active,inactive',
        ]);

        try {
            $this->selectedProject->update([
                'project_name' => $this->projectName,
                'project_description' => $this->projectDescription,
                'status' => $this->projectStatus,
            ]);

            $this->showEditProjectModal = false;
            $this->resetProjectForm();
            
            session()->flash('success', 'Project updated successfully!');
            $this->dispatch('refreshProjects');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update project. Please try again.');
        }
    }

    public function openViewProjectModal($projectId)
    {
        $this->authorize('view_projects');
        
        $this->selectedProject = Project::with(['tasks.users', 'tasks.department'])
            ->withCount(['tasks', 'tasks as pending_tasks_count' => function ($query) {
                $query->where('task_status', 'Pending');
            }, 'tasks as progress_tasks_count' => function ($query) {
                $query->where('task_status', 'Progress');
            }, 'tasks as completed_tasks_count' => function ($query) {
                $query->where('task_status', 'Completed');
            }])
            ->findOrFail($projectId);
        
        $this->showViewProjectModal = true;
    }

    public function openDeleteProjectModal($projectId)
    {
        $this->authorize('delete_projects');
        
        $this->selectedProject = Project::withCount('tasks')->findOrFail($projectId);
        $this->selectedProjectIdForEdit = $projectId;
        $this->showDeleteProjectModal = true;
    }

    public function deleteProject()
    {
        $this->authorize('delete_projects');
        
        try {
            if ($this->selectedProject->tasks_count > 0) {
                session()->flash('error', 'Cannot delete project with existing tasks.');
                return;
            }

            $this->selectedProject->delete();
            
            $this->showDeleteProjectModal = false;
            $this->resetProjectForm();
            
            session()->flash('success', 'Project deleted successfully!');
            $this->dispatch('refreshProjects');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete project. Please try again.');
        }
    }

    public function toggleProjectStatus($projectId)
    {
        $this->authorize('edit_projects');
        
        try {
            $project = Project::findOrFail($projectId);
            $newStatus = $project->status === 'active' ? 'inactive' : 'active';
            $project->update(['status' => $newStatus]);
            
            session()->flash('success', "Project {$newStatus} successfully!");
            $this->dispatch('refreshProjects');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update project status. Please try again.');
        }
    }

    // Task CRUD Methods
    public function openCreateTaskModal()
    {
        $this->authorize('create_tasks');
        $this->resetTaskForm();
        $this->loadData();
        $this->showCreateTaskModal = true;

    }


   

    // Utility Methods


     public function createTask()
    {
        $this->authorize('create_tasks');
        
        // If not authorized to assign others, ensure all assignments are for current user
        if (!auth()->user()->can('assign_task_users')) {
            foreach ($this->taskAssignments as &$assignment) {
                $assignment['user_id'] = auth()->id();
            }
        }
        
        $this->validate();

        try {
            $task = Task::create([
                'task_name' => $this->taskName,
                'project_id' => $this->selectedProjectId,
                'task_status' => 'pending',
                'feedback' => null,
            ]);

            // Create task assignments
            foreach ($this->taskAssignments as $index => $assignmentData) {
                $assignmentData['sequence_number'] = $index + 1;
                $assignmentData['no_of_days'] = Carbon::parse($assignmentData['start_date'])
                    ->diffInDays(Carbon::parse($assignmentData['deadline'])) + 1;
                
                $task->assignments()->create($assignmentData);
            }

                        $this->taskHistoryService->logTaskCreation($task, auth()->user());

            $this->showCreateTaskModal = false;
            $this->resetTaskForm();
            
            session()->flash('success', 'Task created successfully with ' . count($this->taskAssignments) . ' assignments!');
            $this->dispatch('refreshProjects');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create task: ' . $e->getMessage());
        }
    }

    // Task Assignment Management Methods
    public function addTaskAssignment($afterIndex = null)
    {
        $newAssignment = [
            'user_id' => auth()->user()->can('assign_task_users') ? '' : auth()->id(),
            'work_description' => '',
            'start_date' => '',
            'expected_date' => '',
            'deadline' => '',
            'status' => 'Pending',
        ];

        if ($afterIndex !== null) {
            // Insert after specific index
            array_splice($this->taskAssignments, $afterIndex + 1, 0, [$newAssignment]);
        } else {
            // Add to end
            $this->taskAssignments[] = $newAssignment;
        }
        
        $this->resetValidation();
    }

    public function removeTaskAssignment($index)
    {
        if (count($this->taskAssignments) > 1) {
            unset($this->taskAssignments[$index]);
            $this->taskAssignments = array_values($this->taskAssignments); // Re-index array
        }
        
        $this->resetValidation();
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

    private function resetTaskForm()
    {
        $this->taskName = '';
        $this->selectedProjectId = '';
        
        // Initialize with one assignment row
        $this->taskAssignments = [[
            'user_id' => auth()->user()->can('assign_task_users') ? '' : auth()->id(),
            'work_description' => '',
            'start_date' => '',
            'expected_date' => '',
            'deadline' => '',
            'status' => 'Pending',
        ]];
        
        $this->resetValidation();
        $this->dispatch('closeModal');
    }

    // Utility Methods (mostly unchanged)
    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    private function loadData()
    {
        $this->users = User::get();
        $this->projects_all = Project::where('status', 'active')->get();
    }

    private function resetProjectForm()
    {
        $this->projectName = '';
        $this->projectDescription = '';
        $this->projectStatus = 'active';
        $this->selectedProject = null;
        $this->selectedProjectIdForEdit = null;
        $this->resetValidation();
    }

    private function updateStatistics()
    {
        $this->activeProjectsCount = Project::where('status', 'active')->count();
        $this->inactiveProjectsCount = Project::where('status', 'inactive')->count();
    }





}