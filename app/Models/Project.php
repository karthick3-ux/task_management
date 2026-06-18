<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_name',
        'project_description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getStatusBadgeAttribute(): string
    {
        $class = $this->status === 'active' ? 'bg-success' : 'bg-danger';
        $text = ucfirst($this->status);
        
        return "<span class=\"badge {$class}\">{$text}</span>";
    }

    // Methods
    public function getTasksCount(): int
    {
        return $this->tasks()->count();
    }

    public function getPendingTasksCount(): int
    {
        return $this->tasks()->where('task_status', 'pending')->count();
    }

    public function getProgressTasksCount(): int
    {
        return $this->tasks()->where('task_status', 'in progress')->count();
    }

    public function getOnHoldTasksCount(): int
    {
        return $this->tasks()->where('task_status', 'on hold')->count();
    }

    public function getCompletedTasksCount(): int
    {
        return $this->tasks()->where('task_status', 'completed')->count();
    }

    public function getNotCompletedTasksCount(): int
    {
        return $this->tasks()->where('task_status', 'not completed')->count();
    }

    public function getProjectProgress(): float
    {
        $totalTasks = $this->getTasksCount();
        if ($totalTasks === 0) {
            return 0;
        }
        
        $completedTasks = $this->getCompletedTasksCount();
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    public function getTotalAssignmentsCount(): int
    {
        return $this->tasks()
            ->join('task_assignments', 'tasks.id', '=', 'task_assignments.task_id')
            ->count();
    }

    public function getCompletedAssignmentsCount(): int
    {
        return $this->tasks()
            ->join('task_assignments', 'tasks.id', '=', 'task_assignments.task_id')
            ->where('task_assignments.status', 'Completed')
            ->count();
    }

    public function getAssignmentProgress(): float
    {
        $totalAssignments = $this->getTotalAssignmentsCount();
        if ($totalAssignments === 0) {
            return 0;
        }
        
        $completedAssignments = $this->getCompletedAssignmentsCount();
        return round(($completedAssignments / $totalAssignments) * 100, 2);
    }

    public function getUniqueUsersCount(): int
    {
        return $this->tasks()
            ->join('task_assignments', 'tasks.id', '=', 'task_assignments.task_id')
            ->distinct('task_assignments.user_id')
            ->count('task_assignments.user_id');
    }

    public function getOverdueAssignmentsCount(): int
    {
        return $this->tasks()
            ->join('task_assignments', 'tasks.id', '=', 'task_assignments.task_id')
            ->where('task_assignments.deadline', '<', now()->toDateString())
            ->whereNotIn('task_assignments.status', ['Completed', 'Reassigned'])
            ->count();
    }

    public function getTasksByStatus(): array
    {
        return [
            'pending' => $this->getPendingTasksCount(),
            'in_progress' => $this->getProgressTasksCount(),
            'on_hold' => $this->getOnHoldTasksCount(),
            'completed' => $this->getCompletedTasksCount(),
            'not_completed' => $this->getNotCompletedTasksCount(),
        ];
    }

    public function getAssignmentsByStatus(): array
    {
        $assignments = $this->tasks()
            ->join('task_assignments', 'tasks.id', '=', 'task_assignments.task_id')
            ->select('task_assignments.status')
            ->get()
            ->groupBy('status')
            ->map->count();

        return [
            'pending' => $assignments->get('Pending', 0),
            'in_progress' => $assignments->get('Inprogress', 0),
            'reassigned' => $assignments->get('Reassigned', 0),
            'completed' => $assignments->get('Completed', 0),
            'not_completed' => $assignments->get('Not Completed', 0),
        ];
    }

    public function canBeDeleted(): bool
    {
        return $this->tasks()->count() === 0;
    }

    public function getProjectSummary(): array
    {
        return [
            'basic_info' => [
                'name' => $this->project_name,
                'description' => $this->project_description,
                'status' => $this->status,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'task_statistics' => [
                'total_tasks' => $this->getTasksCount(),
                'tasks_by_status' => $this->getTasksByStatus(),
                'task_progress' => $this->getProjectProgress(),
            ],
            'assignment_statistics' => [
                'total_assignments' => $this->getTotalAssignmentsCount(),
                'assignments_by_status' => $this->getAssignmentsByStatus(),
                'assignment_progress' => $this->getAssignmentProgress(),
                'unique_users' => $this->getUniqueUsersCount(),
                'overdue_assignments' => $this->getOverdueAssignmentsCount(),
            ]
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($project) {
            if (!$project->canBeDeleted()) {
                throw new \Exception('Cannot delete project with existing tasks.');
            }
        });
    }
}