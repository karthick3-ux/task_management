<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\TaskHistoryService;
use Carbon\Carbon;

class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'sequence_number',
        'work_description',
        'start_date',
        'expected_date',
        'deadline',
        'status',
        'no_of_days',
        'doc',
        'is_admin'
    ];

    // Prevent these from being mass-assigned
    protected $guarded = [
        '_originalValues',
        'originalValues'
    ];

    // Temporary property to store original values (not a database column)
    public $originalValues = [];
    
    // Flag to disable automatic history logging (for bulk updates)
    public static $disableHistoryLogging = false;


    protected $casts = [
        'start_date' => 'date',
        'expected_date' => 'date',
        'deadline' => 'date',
        'doc' => 'date',
        'no_of_days' => 'integer',
        'sequence_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'Inprogress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeNotCompleted($query)
    {
        return $query->where('status', 'Not Completed');
    }

    public function scopeReassigned($query)
    {
        return $query->where('status', 'Reassigned');
    }

    public function scopeOnHold($query)
    {
        return $query->where('status', 'On Hold');
    }

    public function scopeBySequence($query)
    {
        return $query->orderBy('sequence_number');
    }

    // Accessors
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'Pending' => 'bg-warning',
            'Inprogress' => 'bg-info',
            'Reassigned' => 'bg-secondary',
            'Completed' => 'bg-success',
            'Not Completed' => 'bg-danger',
        ];
        
        $class = $badges[$this->status] ?? 'bg-secondary';
        return "<span class=\"badge {$class}\">{$this->status}</span>";
    }

    public function getIsOverdueAttribute(): bool
    {
        return ($this->deadline < Carbon::today() || $this->expected_date > $this->deadline)  && !in_array($this->status, ['Completed', 'Reassigned']);
    }

    public function getDaysRemainingAttribute(): int
    {
        if (in_array($this->status, ['Completed', 'Reassigned'])) {
            return 0;
        }
        
        return Carbon::today()->diffInDays($this->deadline, false);
    }

    public function getCalculatedDaysAttribute(): int
    {
        if($this->start_date)
        return $this->start_date->diffInDays($this->deadline) + 1;
    else
        return 0;
    }

    // Methods
    public function updateStatus(string $newStatus, int $userId = null): void
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();
        
        // Log status change
        $historyService = app(TaskHistoryService::class);
        $changedBy = $userId ? \App\Models\User::find($userId) : auth()->user();
       // $historyService->logAssignmentStatusChange($this, $oldStatus, $newStatus, $changedBy);
    }

    public function calculateDays(): void
    {
        $this->no_of_days = $this->getCalculatedDaysAttribute();
        $this->save();
    }

    // Boot method with enhanced history tracking
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assignment) {
            // Auto-calculate days if not provided
            if (!$assignment->no_of_days ||  $assignment->no_of_days =='') {
                $assignment->no_of_days = null;
            }
        });

        static::created(function ($assignment) {
            // Log assignment creation
            $historyService = app(TaskHistoryService::class);
            $historyService->logAssignmentCreation($assignment);
        });

        static::updating(function ($assignment) {
            // Store original values for comparison (use proper property name)
            $assignment->originalValues = $assignment->getOriginal();
        });

        static::updated(function ($assignment) {
            // Skip automatic history logging if disabled (for bulk updates)
            if (self::$disableHistoryLogging) {
                return;
            }
            
            $historyService = app(TaskHistoryService::class);
            $original = $assignment->originalValues ?? [];
            $changedBy = auth()->user();

            // Track status changes
            // if (isset($original['status']) && $original['status'] !== $assignment->status) {
            //     $historyService->logAssignmentStatusChange(
            //         $assignment, 
            //         $original['status'], 
            //         $assignment->status, 
            //         $changedBy
            //     );
            // }

            // Track user changes
            if (isset($original['user_id']) && $original['user_id'] !== $assignment->user_id) {
                $historyService->logAssignmentUserChange(
                    $assignment,
                    $original['user_id'],
                    $assignment->user_id,
                    $changedBy
                );
            }

            // Track work description changes
            if (isset($original['work_description']) && $original['work_description'] !== $assignment->work_description) {
                $historyService->logAssignmentWorkDescriptionChange(
                    $assignment,
                    $original['work_description'],
                    $assignment->work_description,
                    $changedBy
                );
            }

            // Track date changes
            $dateFields = ['start_date', 'expected_date', 'deadline'];
            foreach ($dateFields as $field) {
                if (isset($original[$field]) && $original[$field] !== $assignment->{$field}) {
                    $historyService->logAssignmentDateChange(
                        $assignment,
                        $field,
                        $original[$field],
                        $assignment->{$field},
                        $changedBy
                    );
                }
            }

            // Update overall task status when assignment status changes
            if (isset($original['status']) && $assignment->isDirty('status')) {
                $assignment->task->updateOverallStatus();
            }
        });

        static::deleting(function ($assignment) {
            // Log assignment deletion
            //    if (self::$disableHistoryLogging) {
            //     return;
            // }
            $historyService = app(TaskHistoryService::class);
            $deletedBy = auth()->user();
            $historyService->logAssignmentDeletion($assignment, $deletedBy);
        });
    }

    /**
     * Create a reassignment from this assignment
     */
    public function createReassignment(): TaskAssignment
    {
        // Mark current assignment as reassigned
        $this->updateStatus('Reassigned');

        // Create new assignment with same details
        $newAssignment = $this->task->addAssignment([
            'user_id' => $this->user_id,
            'work_description' => $this->work_description,
            'start_date' => $this->start_date,
            'expected_date' => $this->expected_date,
            'deadline' => $this->deadline,
            'status' => 'Pending',
            'sequence_number' => $this->task->assignments()->max('sequence_number') + 1,
        ]);

        // Log the reassignment
        $historyService = app(TaskHistoryService::class);
        $historyService->logAssignmentReassignment($this, $newAssignment, auth()->user());

        return $newAssignment;
    }

    /**
     * Check if this assignment can be started
     */
    public function canStart(): bool
    {
        // Super admin can start any assignment
        if (auth()->user() && auth()->user()->isSuperAdmin()) {
            return true;
        }

        // First assignment can always start
        if ($this->sequence_number === 1) {
            return true;
        }

        // Check if previous assignment is completed
        $previousAssignment = $this->task->assignments()
            ->where('sequence_number', $this->sequence_number - 1)
            ->first();

        return $previousAssignment && $previousAssignment->status === 'Completed';
    }

    /**
     * Check if this assignment can be edited by the current user
     */
    public function canEdit(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Super admin can edit any assignment
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can edit only their own assignments
        if ($this->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if this assignment can be modified
     */
    public function canModify(): bool
    {
        // Cannot modify if task is on hold
        if ($this->task->task_status === 'on hold') {
            return false;
        }

        // Cannot modify completed assignments
        if ($this->status === 'Completed') {
            return false;
        }

        return true;
    }

    /**
     * Get the next assignment in sequence
     */
    public function getNextAssignment(): ?TaskAssignment
    {
        return $this->task->assignments()
            ->where('sequence_number', $this->sequence_number + 1)
            ->first();
    }

    /**
     * Get the previous assignment in sequence
     */
    public function getPreviousAssignment(): ?TaskAssignment
    {
        return $this->task->assignments()
            ->where('sequence_number', $this->sequence_number - 1)
            ->first();
    }

    /**
     * Update multiple fields and log changes efficiently
     */
    public function updateWithHistory(array $data, $changedBy = null): bool
    {
        $original = $this->getOriginal();
        $changedBy = $changedBy ?: auth()->user();
        
        // Update the model normally
        $updated = $this->update($data);
        
        if ($updated) {
            $historyService = app(TaskHistoryService::class);
            
            // Log specific changes manually since we're bypassing the model events
            foreach ($data as $field => $newValue) {
                if (isset($original[$field]) && $original[$field] != $newValue) {
                    switch ($field) {
                        case 'status':
                            $historyService->logAssignmentStatusChange(
                                $this, 
                                $original[$field], 
                                $newValue, 
                                $changedBy
                            );
                            break;
                        
                        case 'user_id':
                            $historyService->logAssignmentUserChange(
                                $this,
                                $original[$field],
                                $newValue,
                                $changedBy
                            );
                            break;
                        
                        case 'work_description':
                            $historyService->logAssignmentWorkDescriptionChange(
                                $this,
                                $original[$field],
                                $newValue,
                                $changedBy
                            );
                            break;
                        
                        case 'start_date':
                        case 'expected_date':
                        case 'deadline':
                            $historyService->logAssignmentDateChange(
                                $this,
                                $field,
                                $original[$field],
                                $newValue,
                                $changedBy
                            );
                            break;
                    }
                }
            }
        }
        
        return $updated;
    }
}