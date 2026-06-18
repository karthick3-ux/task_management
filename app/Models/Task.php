<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_name',
        'project_id',
        'task_status',
        'feedback',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class)->orderBy('sequence_number');
    }

    public function updateHistories(): HasMany
    {
        return $this->hasMany(TaskUpdateHistory::class)->orderBy('created_at', 'desc');
    }

    // Accessor for backward compatibility (users through assignments)
    public function getUsersAttribute()
    {
        return $this->assignments->map(function ($assignment) {
            return $assignment->user;
        })->unique('id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('task_status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('task_status', 'in progress');
    }

    public function scopeOnHold($query)
    {
        return $query->where('task_status', 'on hold');
    }

    public function scopeCompleted($query)
    {
        return $query->where('task_status', 'completed');
    }

    public function scopeNotCompleted($query)
    {
        return $query->where('task_status', 'not completed');
    }

    // Accessors
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'bg-warning',
            'in progress' => 'bg-info',
            'on hold' => 'bg-secondary',
            'completed' => 'bg-success',
            'not completed' => 'bg-danger',
        ];
        
        $class = $badges[$this->task_status] ?? 'bg-secondary';
        $text = ucwords($this->task_status);
        return "<span class=\"badge {$class}\">{$text}</span>";
    }

    public function getOverallProgressAttribute(): float
    {
        $totalAssignments = $this->assignments->count();
        if ($totalAssignments === 0) {
            return 0;
        }
        
        $completedAssignments = $this->assignments->where('status', 'Completed')->count();
        return round(($completedAssignments / $totalAssignments) * 100, 2);
    }

    public function getFormattedUsersAttribute(): string
    {
        return $this->assignments->map(function ($assignment) {
            return $assignment->user->name;
        })->unique()->join(', ');
    }

    public function getEarliestStartDateAttribute()
    {
        return $this->assignments->min('start_date');
    }

    public function getLatestDeadlineAttribute()
    {
        return $this->assignments->max('deadline');
    }

    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->task_status, ['completed', 'not completed'])) {
            return false;
        }
        
        $latestDeadline = $this->getLatestDeadlineAttribute();
        return $latestDeadline && $latestDeadline < Carbon::today();
    }

    // Methods
    public function addUpdateHistory(string $message, int $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        
        $this->updateHistories()->create([
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    public function updateOverallStatus(): void
    {
        // $assignments = $this->assignments;
        
        // if ($assignments->isEmpty()) {
        //     $this->task_status = 'pending';
        //     $this->save();
        //     return;
        // }

        // $statuses = $assignments->pluck('status')->toArray();
        
        // // Logic to determine overall task status based on assignment statuses
        // if (in_array('Inprogress', $statuses)) {
        //     $newStatus = 'in progress';
        // } elseif (all_assignments_completed($statuses)) {
        //     $newStatus = 'completed';
        // } elseif (in_array('Not Completed', $statuses)) {
        //     $newStatus = 'not completed';
        // } elseif (in_array('Reassigned', $statuses)) {
        //     $newStatus = 'in progress'; // Reassigned means work is ongoing
        // } else {
        //     $newStatus = 'pending';
        // }

        // if ($this->task_status !== $newStatus) {
        //     $oldStatus = $this->task_status;
        //     $this->task_status = $newStatus;
        //     $this->save();
            
        //     $this->addUpdateHistory(
        //         "Task status automatically updated from {$oldStatus} to {$newStatus} based on assignment statuses"
        //     );
        // }
    }

    public function addAssignment(array $assignmentData): TaskAssignment
    {
        // Get next sequence number
        // $nextSequence = $this->assignments()->max('sequence_number') + 1;
        // $assignmentData['sequence_number'] = $nextSequence;
        
        return $this->assignments()->create($assignmentData);
    }

    public function insertAssignmentAfter(int $afterSequence, array $assignmentData): TaskAssignment
    {
        // Increment sequence numbers for all assignments after the specified position
        $this->assignments()
             ->where('sequence_number', '>', $afterSequence)
             ->increment('sequence_number');
        
        $assignmentData['sequence_number'] = $afterSequence + 1;
        
        return $this->assignments()->create($assignmentData);
    }

    public function removeAssignment(int $assignmentId): void
    {
        $assignment = $this->assignments()->findOrFail($assignmentId);
        $sequenceToRemove = $assignment->sequence_number;
        
        $assignment->delete();
        
        // Decrement sequence numbers for all assignments after the removed one
        $this->assignments()
             ->where('sequence_number', '>', $sequenceToRemove)
             ->decrement('sequence_number');
        
        // Update overall task status
        $this->updateOverallStatus();
    }

    public function reorderAssignments(): void
    {
        $assignments = $this->assignments()->orderBy('sequence_number')->get();
        
        foreach ($assignments as $index => $assignment) {
            $assignment->update(['sequence_number' => $index + 1]);
        }
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($task) {
            $task->addUpdateHistory(
                "Task '{$task->task_name}' created",
                auth()->id()
            );
        });

        static::updated(function ($task) {
            // if ($task->isDirty('task_status')) {
            //     $oldStatus = $task->getOriginal('task_status');
            //     $newStatus = $task->task_status;
                
            //     $task->addUpdateHistory(
            //         "Task status changed from {$oldStatus} to {$newStatus}"
            //     );
            // }
        });
    }
}

// Helper function
if (!function_exists('all_assignments_completed')) {
    function all_assignments_completed(array $statuses): bool
    {
        return !empty($statuses) && 
               !in_array('Pending', $statuses) && 
               !in_array('Inprogress', $statuses) && 
               !in_array('Reassigned', $statuses) &&
               (in_array('Completed', $statuses) || in_array('Not Completed', $statuses));
    }
}