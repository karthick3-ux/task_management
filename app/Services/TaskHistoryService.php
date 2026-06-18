<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskUpdateHistory;
use App\Models\TaskAssignment;
use App\Models\Project;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TaskHistoryService
{
    /**
     * Track changes between original and updated task data
     */
    public function trackTaskChanges(Task $task, array $originalData, array $newData): void
    {
        $changes = $this->detectChanges($originalData, $newData);
        
        // Handle user assignment changes separately
        if (isset($originalData['users']) && isset($newData['users'])) {
            $this->logUserAssignment($task, $originalData['users'], $newData['users']);
        }
        
        foreach ($changes as $change) {
            $this->logChange($task->id, $change['type'], $change['message']);
        }
    }

    /**
     * Track assignment changes for the new assignment system
     */
    public function trackAssignmentChanges(Task $task, array $originalAssignments, array $newAssignments): void
    {
        // Track new assignments
        $this->trackNewAssignments($task, $originalAssignments, $newAssignments);
        
        // Track modified assignments
        $this->trackModifiedAssignments($task, $originalAssignments, $newAssignments);
        
        // Track removed assignments
        $this->trackRemovedAssignments($task, $originalAssignments, $newAssignments);
    }

    /**
     * Track new assignments added
     */
    private function trackNewAssignments(Task $task, array $originalAssignments, array $newAssignments): void
    {
        $originalIds = collect($originalAssignments)->pluck('id')->filter()->toArray();
        
        foreach ($newAssignments as $assignment) {
            // New assignment if it doesn't have an ID or ID not in original
            if (!isset($assignment['id']) || !in_array($assignment['id'], $originalIds)) {
                $user = User::find($assignment['user_id']);
                if ($user) {
                    $this->logChange(
                        $task->id,
                        'assignment_added',
                        "New assignment #{$assignment['sequence_number']} added for {$user->name}: {$assignment['work_description']}"
                    );
                }
            }
        }
    }

    /**
     * Track modifications to existing assignments
     */
    private function trackModifiedAssignments(Task $task, array $originalAssignments, array $newAssignments): void
    {
        $originalById = collect($originalAssignments)->keyBy('id');
        
        foreach ($newAssignments as $newAssignment) {
            if (isset($newAssignment['id']) && $originalById->has($newAssignment['id'])) {
                $original = $originalById->get($newAssignment['id']);
                $this->trackSingleAssignmentChanges($task, $original, $newAssignment);
            }
        }
    }

    /**
     * Track changes in a single assignment
     */
    private function trackSingleAssignmentChanges(Task $task, array $original, array $new): void
    {
        $assignmentSeq = $new['sequence_number'] ?? $original['sequence_number'];
        $user = User::find($new['user_id'] ?? $original['user_id']);
        $userName = $user ? $user->name : 'Unknown User';

        // Track user changes
        if (($original['user_id'] ?? null) !== ($new['user_id'] ?? null)) {
            $oldUser = User::find($original['user_id'] ?? null);
            $newUser = User::find($new['user_id'] ?? null);
            
            $this->logChange(
                $task->id,
                'assignment_user_changed',
                "Assignment #{$assignmentSeq} user changed from {($oldUser->name ?? 'None')} to {($newUser->name ?? 'None')}"
            );
        }

        // Track work description changes
        if (($original['work_description'] ?? '') !== ($new['work_description'] ?? '')) {
            $this->logChange(
                $task->id,
                'assignment_work_description_changed',
                "Assignment #{$assignmentSeq} work description updated by {$userName}"
            );
        }

        // Track status changes
        if (($original['status'] ?? '') !== ($new['status'] ?? '')) {
            $oldStatus = $original['status'] ?? 'Pending';
            $newStatus = $new['status'] ?? 'Pending';
            
            $this->logChange(
                $task->id,
                'assignment_status_changed',
                "Assignment #{$assignmentSeq} status changed from '{$oldStatus}' to '{$newStatus}' by {$userName}"
            );

            // Special handling for reassignment
            if ($newStatus === 'Reassigned') {
                $this->logChange(
                    $task->id,
                    'assignment_reassigned',
                    "Assignment #{$assignmentSeq} was reassigned by {$userName}"
                );
            }
        }

        // Track date changes
        $this->trackAssignmentDateChanges($task, $original, $new, $assignmentSeq, $userName);
    }

    /**
     * Track date changes in assignments
     */
    private function trackAssignmentDateChanges(Task $task, array $original, array $new, $assignmentSeq, string $userName): void
    {
        $dateFields = [
            'start_date' => 'Start date',
            'expected_date' => 'Expected date',
            'deadline' => 'Deadline'
        ];

        foreach ($dateFields as $field => $label) {
            if (($original[$field] ?? null) !== ($new[$field] ?? null)) {
                $oldDate = isset($original[$field]) ? \Carbon\Carbon::parse($original[$field])->format('M d, Y') : 'Not set';
                $newDate = isset($new[$field]) ? \Carbon\Carbon::parse($new[$field])->format('M d, Y') : 'Not set';
                
                $this->logChange(
                    $task->id,
                    'assignment_date_changed',
                    "Assignment #{$assignmentSeq} {$label} changed from '{$oldDate}' to '{$newDate}' by {$userName}"
                );
            }
        }
    }

    /**
     * Track removed assignments
     */
    private function trackRemovedAssignments(Task $task, array $originalAssignments, array $newAssignments): void
    {
        $newIds = collect($newAssignments)->pluck('id')->filter()->toArray();
        
        foreach ($originalAssignments as $original) {
            if (isset($original['id']) && !in_array($original['id'], $newIds)) {
                $user = User::find($original['user_id']);
                $userName = $user ? $user->name : 'Unknown User';
                
                $this->logChange(
                    $task->id,
                    'assignment_removed',
                    "Assignment #{$original['sequence_number']} for {$userName} was removed"
                );
            }
        }
    }

    /**
     * Log assignment creation (when new assignment is created)
     */
    public function logAssignmentCreation(TaskAssignment $assignment): void
    {
        $user = $assignment->user;
        $userName = $user ? $user->name : 'Unknown User';
        
        $this->logChange(
            $assignment->task_id,
            'assignment_created',
            "Assignment #{$assignment->sequence_number} created for {$userName}: {$assignment->work_description}"
        );
    }

     public function logAssignmentStart(TaskAssignment $assignment): void
    {
        $user = $assignment->user;
        $userName = $user ? $user->name : 'Unknown User';
       $newDateFormatted = \Carbon\Carbon::parse($assignment->start_date)->format('M d, Y');

        
        // $this->logChange(
        //     $assignment->task_id,
        //     'assignment_started',
        //     "Assignment #{$assignment->sequence_number} has started on {$newDateFormatted} for {$userName}: {$assignment->work_description}"
        // );
    }
      public function logNextAssignmentStart(TaskAssignment $assignment): void
    {
        $user = $assignment->user;
        $userName = $user ? $user->name : 'Unknown User';
       $newDateFormatted = \Carbon\Carbon::parse($assignment->start_date)->format('M d, Y');

        
        TaskUpdateHistory::create([
            'task_id' => $assignment->task_id,
            'user_id' => $user->id,
            'type' =>  'assignment_started',
            'message' => "Assignment #{$assignment->sequence_number} has started on {$newDateFormatted} for {$userName}: {$assignment->work_description}",
        ]);
    
    }

    /**
     * Log assignment status change
     */
   
    /**
     * Log assignment date changes with better validation
     */
    public function logAssignmentDateChange(TaskAssignment $assignment, string $dateField, $oldDate, $newDate, User $changedBy = null): void
    {
        // Normalize dates for comparison
        $normalizedOldDate = $this->normalizeDateForComparison($oldDate);
        $normalizedNewDate = $this->normalizeDateForComparison($newDate);
        
        // Only log if dates are actually different
        if ($normalizedOldDate === $normalizedNewDate) {
            return; // No change, don't log
        }
        
        $user = $assignment->user;
        $userName = $user ? $user->name : 'Unknown User';
        $changedByName = $changedBy ? $changedBy->name : auth()->user()?->name ?? 'System';
        
        $dateLabels = [
            'start_date' => 'Start date',
            'expected_date' => 'Expected date',
            'deadline' => 'Deadline'
        ];
        
        $label = $dateLabels[$dateField] ?? ucfirst(str_replace('_', ' ', $dateField));
        $oldDateFormatted = $oldDate ? \Carbon\Carbon::parse($oldDate)->format('M d, Y') : 'Not set';
        $newDateFormatted = $newDate ? \Carbon\Carbon::parse($newDate)->format('M d, Y') : 'Not set';
        
        // Check for recent duplicate entries to prevent spam
        $recentSimilarEntry = TaskUpdateHistory::where('task_id', $assignment->task_id)
            ->where('type', 'assignment_date_changed')
            ->where('message', 'like', "%Assignment #{$assignment->sequence_number}% {$label} changed%")
            ->where('created_at', '>', now()->subMinutes(1)) // Within last minute
            ->exists();
            
        if ($recentSimilarEntry) {
            return; // Prevent duplicate logging
        }
        
        $this->logChange(
            $assignment->task_id,
            'assignment_date_changed',
            "Assignment #{$assignment->sequence_number} {$label} changed from '{$oldDateFormatted}' to '{$newDateFormatted}' by {$changedByName} (assigned to {$userName})"
        );
    }

    /**
     * Log assignment status change with duplicate prevention
     */
    public function logAssignmentStatusChange(TaskAssignment $assignment, string $oldStatus, string $newStatus, User $changedBy = null): void
    {
        // Don't log if status hasn't actually changed
        if ($oldStatus === $newStatus) {
            return;
        }
        
        $user = $assignment->user;
        $userName = $user ? $user->name : 'Unknown User';
        $changedByName = $changedBy ? $changedBy->name : auth()->user()?->name ?? 'System';
        
        // Check for recent duplicate entries
        $recentSimilarEntry = TaskUpdateHistory::where('task_id', $assignment->task_id)
            ->where('type', 'assignment_status_changed')
            ->where('message', 'like', "%Assignment #{$assignment->sequence_number}% status changed%")
            ->where('created_at', '>', now()->subMinutes(1))
            ->exists();
            
        if ($recentSimilarEntry) {
            return;
        }
        
        $this->logChange(
            $assignment->task_id,
            'assignment_status_changed',
            "Assignment #{$assignment->sequence_number} status changed from '{$oldStatus}' to '{$newStatus}' by {$changedByName} (assigned to {$userName})"
        );

        // Special logging for completed assignments
        if ($newStatus === 'Completed') {
            $this->logChange(
                $assignment->task_id,
                'assignment_completed',
                "Assignment #{$assignment->sequence_number} completed by {$userName}"
            );
        }
    }

    /**
     * Log assignment user change with duplicate prevention
     */
    public function logAssignmentUserChange(TaskAssignment $assignment, $oldUserId, $newUserId, User $changedBy = null): void
    {
        // Don't log if user hasn't actually changed
        if ($oldUserId == $newUserId) {
            return;
        }
        
        $oldUser = User::find($oldUserId);
        $newUser = User::find($newUserId);
        $changedByName = $changedBy ? $changedBy->name : auth()->user()?->name ?? 'System';
        
        $oldUserName = $oldUser ? $oldUser->name : 'Unknown User';
        $newUserName = $newUser ? $newUser->name : 'Unknown User';
        
        // Check for recent duplicate entries
        $recentSimilarEntry = TaskUpdateHistory::where('task_id', $assignment->task_id)
            ->where('type', 'assignment_user_changed')
            ->where('message', 'like', "%Assignment #{$assignment->sequence_number}% reassigned%")
            ->where('created_at', '>', now()->subMinutes(1))
            ->exists();
            
        if ($recentSimilarEntry) {
            return;
        }
        
        $this->logChange(
            $assignment->task_id,
            'assignment_user_changed',
            "Assignment #{$assignment->sequence_number} reassigned from {$oldUserName} to {$newUserName} by {$changedByName}"
        );
    }

    /**
     * Log assignment work description change with duplicate prevention
     */
    public function logAssignmentWorkDescriptionChange(TaskAssignment $assignment, string $oldDescription, string $newDescription, User $changedBy = null): void
    {
        // Don't log if description hasn't actually changed (trim whitespace)
        if (trim($oldDescription) === trim($newDescription)) {
            return;
        }
        
        $user = $assignment->user;
        $userName = $user ? $user->name : 'Unknown User';
        $changedByName = $changedBy ? $changedBy->name : auth()->user()?->name ?? 'System';
        
        // Check for recent duplicate entries
        $recentSimilarEntry = TaskUpdateHistory::where('task_id', $assignment->task_id)
            ->where('type', 'assignment_work_description_changed')
            ->where('message', 'like', "%Assignment #{$assignment->sequence_number}% work description updated%")
            ->where('created_at', '>', now()->subMinutes(1))
            ->exists();
            
        if ($recentSimilarEntry) {
            return;
        }
        
        $this->logChange(
            $assignment->task_id,
            'assignment_work_description_changed',
            "Assignment #{$assignment->sequence_number} work description updated by {$changedByName} (assigned to {$userName})"
        );
    }

    /**
     * Helper method to normalize dates for comparison
     */
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

    /**
     * Log assignment deletion
     */
    public function logAssignmentDeletion(TaskAssignment $assignment, User $deletedBy = null): void
    {
        $user = $assignment->user;
        $userName = $user ? $user->name : 'Unknown User';
        $deletedByName = $deletedBy ? $deletedBy->name : auth()->user()?->name ?? 'System';
        
        $this->logChange(
            $assignment->task_id,
            'assignment_deleted',
            "Assignment #{$assignment->sequence_number} for {$userName} was deleted by {$deletedByName}"
        );
    }

    /**
     * Log assignment reassignment creation
     */
    public function logAssignmentReassignment(TaskAssignment $originalAssignment, TaskAssignment $newAssignment, User $reassignedBy = null): void
    {
        $user = $originalAssignment->user;
        $userName = $user ? $user->name : 'Unknown User';
        $reassignedByName = $reassignedBy ? $reassignedBy->name : auth()->user()?->name ?? 'System';
        
        $this->logChange(
            $originalAssignment->task_id,
            'assignment_reassigned',
            "Assignment #{$originalAssignment->sequence_number} was reassigned by {$reassignedByName}. New assignment #{$newAssignment->sequence_number} created for {$userName}"
        );
    }

    /**
     * Log a specific change to the task
     */
    public function logChange(int $taskId, string $type, string $message): void
    {
        TaskUpdateHistory::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Log task creation
     */
    public function logTaskCreation(Task $task, User $user = null): void
    {
        $userId = $user ? $user->id : Auth::id();
        
        TaskUpdateHistory::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'type' => 'create',
            'message' => "Task '{$task->task_name}' was created",
        ]);
    }

    /**
     * Log task deletion
     */
    public function logTaskDeletion(Task $task): void
    {
        $this->logChange(
            $task->id,
            'delete',
            "Task '{$task->task_name}' was deleted"
        );
    }

    /**
     * Log status change specifically
     */
    public function logStatusChange(Task $task, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus !== $newStatus) {
            $this->logChange(
                $task->id,
                'status_change',
                "Task status changed from '{$oldStatus}' to '{$newStatus}'"
            );
        }
    }

    /**
     * Log priority change specifically
     */
    public function logPriorityChange(Task $task, int $oldPriority, int $newPriority): void
    {
        if ($oldPriority !== $newPriority) {
            $this->logChange(
                $task->id,
                'priority_change',
                "Task priority changed from 'P{$oldPriority}' to 'P{$newPriority}'"
            );
        }
    }

    /**
     * Log user assignment changes
     */
    public function logUserAssignment(Task $task, array $oldUserIds, array $newUserIds): void
    {
        $added = array_diff($newUserIds, $oldUserIds);
        $removed = array_diff($oldUserIds, $newUserIds);

        foreach ($added as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->logChange(
                    $task->id,
                    'user_assigned',
                    "User '{$user->name}' was assigned to the task"
                );
            }
        }

        foreach ($removed as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->logChange(
                    $task->id,
                    'user_unassigned',
                    "User '{$user->name}' was removed from the task"
                );
            }
        }
    }

    /**
     * Log user addition to task (for individual user management)
     */
    public function logUserAddition(Task $task, User $addedUser, User $actionUser): void
    {
        TaskUpdateHistory::create([
            'task_id' => $task->id,
            'user_id' => $actionUser->id,
            'type' => 'user_assigned',
            'message' => "User '{$addedUser->name}' was assigned to the task",
        ]);
    }

    /**
     * Log user removal from task (for individual user management)
     */
    public function logUserRemoval(Task $task, User $removedUser, User $actionUser): void
    {
        TaskUpdateHistory::create([
            'task_id' => $task->id,
            'user_id' => $actionUser->id,
            'type' => 'user_unassigned',
            'message' => "User '{$removedUser->name}' was removed from the task",
        ]);
    }

    /**
     * Detect changes between original and new data
     */
    private function detectChanges(array $originalData, array $newData): array
    {
        $changes = [];
        $trackableFields = [
            'task_name' => 'Task name',
            'project_id' => 'Project',
            'department_id' => 'Department',
            'expected_date' => 'Expected date',
            'deadline' => 'Deadline',
            'start_date' => 'Start date',
            'priority' => 'Priority',
            'task_status' => 'Status',
            'feedback' => 'Feedback',
            'percentage' => 'Progress percentage'
        ];

        foreach ($trackableFields as $field => $label) {
            if (isset($originalData[$field]) && isset($newData[$field])) {
                $oldValue = $originalData[$field];
                $newValue = $newData[$field];

                if ($oldValue != $newValue) {
                    $changes[] = [
                        'type' => 'update_'.$field,
                        'message' => $this->formatChangeMessage($field, $label, $oldValue, $newValue)
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Format change message based on field type
     */
    private function formatChangeMessage(string $field, string $label, $oldValue, $newValue): string
    {
        switch ($field) {
            case 'project_id':
                $oldProject = Project::find($oldValue)->project_name ?? 'Unknown';
                $newProject = Project::find($newValue)->project_name ?? 'Unknown';
                return "{$label} changed from '{$oldProject}' to '{$newProject}'";

            case 'department_id':
                $oldDepartment = Department::find($oldValue)->name ?? 'Unknown';
                $newDepartment = Department::find($newValue)->name ?? 'Unknown';
                return "{$label} changed from '{$oldDepartment}' to '{$newDepartment}'";

            case 'priority':
                return "{$label} changed from 'P{$oldValue}' to 'P{$newValue}'";

            case 'expected_date':
            case 'deadline':
            case 'start_date':
                $oldDate = \Carbon\Carbon::parse($oldValue)->format('M d, Y');
                $newDate = \Carbon\Carbon::parse($newValue)->format('M d, Y');
                return "{$label} changed from '{$oldDate}' to '{$newDate}'";

            case 'percentage':
                return "{$label} changed from '{$oldValue}%' to '{$newValue}%'";

            case 'feedback':
                $oldFeedback = $oldValue ?: 'Empty';
                $newFeedback = $newValue ?: 'Empty';
                return "{$label} updated from '{$oldFeedback}' to '{$newFeedback}'";

            default:
                return "{$label} changed from '{$oldValue}' to '{$newValue}'";
        }
    }

    /**
     * Get task update history with formatted data
     */
    public function getTaskHistory(int $taskId): \Illuminate\Database\Eloquent\Collection
    {
        return TaskUpdateHistory::with(['user','task'])
            ->where('task_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($history) {
                $history->formatted_date = $history->created_at->format('M d, Y \a\t g:i A');
                $history->time_ago = $history->created_at->diffForHumans();
                return $history;
            });
    }

    /**
     * Get recent updates for a user
     */
    public function getUserRecentUpdates(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return TaskUpdateHistory::with(['task', 'user'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($history) {
                $history->formatted_date = $history->created_at->format('M d, Y \a\t g:i A');
                $history->time_ago = $history->created_at->diffForHumans();
                return $history;
            });
    }

    /**
     * Get recent task activities for dashboard
     */
    public function getRecentActivities(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return TaskUpdateHistory::with(['user', 'task'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($history) {
                $history->formatted_date = $history->created_at->format('M d, Y \a\t g:i A');
                $history->time_ago = $history->created_at->diffForHumans();
                return $history;
            });
    }

    /**
     * Get task statistics
     */
    public function getTaskStatistics(int $taskId): array
    {
        $history = TaskUpdateHistory::where('task_id', $taskId)->get();

        return [
            'total_updates' => $history->count(),
            'last_updated' => $history->max('created_at'),
            'update_types' => $history->groupBy('type')->map->count(),
            'contributors' => $history->pluck('user_id')->unique()->count()
        ];
    }

    /**
     * Get task statistics for a specific period
     */
    public function getTaskStatisticsByPeriod($startDate = null, $endDate = null): array
    {
        $query = TaskUpdateHistory::query();
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return [
            'total_activities' => $query->count(),
            'created_tasks' => $query->clone()->where('type', 'create')->count(),
            'updated_tasks' => $query->clone()->whereNotIn('type', ['create', 'delete'])->count(),
            'deleted_tasks' => $query->clone()->where('type', 'delete')->count(),
            'user_assignments' => $query->clone()->where('type', 'user_assigned')->count(),
            'user_removals' => $query->clone()->where('type', 'user_unassigned')->count(),
        ];
    }

    /**
     * Get user activity summary
     */
    public function getUserActivitySummary(int $userId): array
    {
        $activities = TaskUpdateHistory::where('user_id', $userId)->get();

        return [
            'total_activities' => $activities->count(),
            'tasks_created' => $activities->where('type', 'create')->count(),
            'tasks_updated' => $activities->whereNotIn('type', ['create', 'delete'])->count(),
            'tasks_deleted' => $activities->where('type', 'delete')->count(),
            'last_activity' => $activities->max('created_at'),
            'activity_types' => $activities->groupBy('type')->map->count(),
        ];
    }

    /**
     * Clean old history records (optional maintenance method)
     */
    public function cleanOldHistory(int $daysToKeep = 365): int
    {
        $cutoffDate = \Carbon\Carbon::now()->subDays($daysToKeep);
        
        return TaskUpdateHistory::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Log bulk user assignment (for mass assignment operations)
     */
    public function logBulkUserAssignment(Task $task, array $userIds, string $action = 'assigned'): void
    {
        $users = User::whereIn('id', $userIds)->get();
        $userNames = $users->pluck('name')->toArray();
        
        $message = count($userNames) === 1 
            ? "User '{$userNames[0]}' was {$action} to the task"
            : count($userNames) . " users were {$action}: " . implode(', ', $userNames);

        $this->logChange(
            $task->id,
            $action === 'assigned' ? 'user_assigned' : 'user_unassigned',
            $message
        );
    }
}